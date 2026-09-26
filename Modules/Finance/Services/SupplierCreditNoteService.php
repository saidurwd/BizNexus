<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\ApprovalNotifier;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Finance\Models\SupplierCreditNote;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\Concerns\EnforcesSegregationOfDuties;

/**
 * Supplier credit notes: recorded with lines and tax codes, approved by someone other than their creator, and
 * posted as the mirror of a supplier invoice (debit the payable, credit cost and input tax).
 */
class SupplierCreditNoteService
{
    use EnforcesSegregationOfDuties;

    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService,
        protected DocumentTaxService $documentTax,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupplierCreditNote
    {
        return DB::transaction(function () use ($data) {
            $creditNote = SupplierCreditNote::create([
                'company_id' => $data['company_id'],
                'credit_note_number' => $data['credit_note_number'] ?? $this->documentNumber->generateNumber($data['company_id'], 'SCN'),
                'status' => SupplierCreditNote::STATUS_DRAFT,
                'subtotal' => 0,
                'total_amount' => 0,
                'created_by' => Auth::id(),
                ...$this->headerAttributes($data),
            ]);

            $this->replaceLines($creditNote, $data['lines']);
            $this->audit->logCreate('Finance', 'SupplierCreditNote', $creditNote->id, $creditNote->toArray());

            return $creditNote->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SupplierCreditNote $creditNote, array $data): SupplierCreditNote
    {
        $this->requireStatus($creditNote, [SupplierCreditNote::STATUS_DRAFT, SupplierCreditNote::STATUS_REJECTED], 'edited');

        return DB::transaction(function () use ($creditNote, $data) {
            $creditNote->update([
                'credit_note_number' => $data['credit_note_number'] ?? $creditNote->credit_note_number,
                'status' => SupplierCreditNote::STATUS_DRAFT,
                'rejection_reason' => null,
                'updated_by' => Auth::id(),
                ...$this->headerAttributes($data),
            ]);

            $this->replaceLines($creditNote, $data['lines']);

            return $creditNote->fresh();
        });
    }

    public function submit(SupplierCreditNote $creditNote): SupplierCreditNote
    {
        $this->requireStatus($creditNote, [SupplierCreditNote::STATUS_DRAFT], 'submitted');

        if ($creditNote->lines()->doesntExist() || bccomp((string) $creditNote->total_amount, '0', 4) <= 0) {
            throw new InvalidAccountingTransactionException('A credit note needs at least one line and a positive total.');
        }

        $submitted = $this->transition($creditNote, SupplierCreditNote::STATUS_SUBMITTED, 'SUBMIT');
        app(ApprovalNotifier::class)->documentSubmitted($submitted->company_id, 'finance.supplier-credit-notes.approve', __('Supplier credit note'), $submitted->credit_note_number, route('finance.supplier-credit-notes.show', $submitted->id), (string) $submitted->total_amount, $submitted->currency?->code);

        return $submitted;
    }

    public function approve(SupplierCreditNote $creditNote): SupplierCreditNote
    {
        $this->requireStatus($creditNote, [SupplierCreditNote::STATUS_SUBMITTED], 'approved');
        $this->ensureApproverIsNotCreator($creditNote, 'supplier credit note');

        $creditNote->approved_by = Auth::id();

        return $this->transition($creditNote, SupplierCreditNote::STATUS_APPROVED, 'APPROVE');
    }

    public function reject(SupplierCreditNote $creditNote, ?string $reason): SupplierCreditNote
    {
        $this->requireStatus($creditNote, [SupplierCreditNote::STATUS_SUBMITTED], 'rejected');

        $creditNote->rejection_reason = $reason;

        return $this->transition($creditNote, SupplierCreditNote::STATUS_REJECTED, 'REJECT', ['reason' => $reason]);
    }

    public function cancel(SupplierCreditNote $creditNote): SupplierCreditNote
    {
        $this->requireStatus($creditNote, [SupplierCreditNote::STATUS_DRAFT, SupplierCreditNote::STATUS_SUBMITTED, SupplierCreditNote::STATUS_APPROVED, SupplierCreditNote::STATUS_REJECTED], 'cancelled');

        return $this->transition($creditNote, SupplierCreditNote::STATUS_CANCELLED, 'CANCEL');
    }

    /**
     * Post the reversal of cost and input tax against the payable, record negative input tax for the return,
     * and apply the credit to the invoice it was received against (up to what is still owed on it).
     */
    public function post(SupplierCreditNote $creditNote): SupplierCreditNote
    {
        return DB::transaction(function () use ($creditNote) {
            $creditNote = SupplierCreditNote::whereKey($creditNote->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($creditNote, [SupplierCreditNote::STATUS_APPROVED], 'posted');

            $supplier = $creditNote->supplier;
            $journalLines = app(DocumentJournalBuilder::class)->purchase(
                $creditNote,
                $supplier->payable_account_id ?? app(DefaultAccountService::class)->getPayableAccount($creditNote->company_id),
                "Credit note {$creditNote->credit_note_number} from {$supplier->name}",
                isCredit: true,
            )['lines'];

            $journal = $this->journalService->postFromSource([
                'company_id' => $creditNote->company_id,
                'journal_date' => $creditNote->credit_note_date->toDateString(),
                'reference_type' => 'supplier_credit_note',
                'reference_id' => $creditNote->id,
                'description' => "Supplier Credit Note #{$creditNote->credit_note_number}",
                'currency_id' => $creditNote->currency_id,
                'exchange_rate' => $creditNote->exchange_rate,
                'lines' => $journalLines,
            ]);

            $this->documentTax->recordTransactions($creditNote, $journal->id);

            $creditNote->forceFill([
                'status' => SupplierCreditNote::STATUS_POSTED,
                'journal_id' => $journal->id,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ])->save();
            $this->applyToInvoice($creditNote);

            $this->audit->logCustom('Finance', 'SupplierCreditNote', $creditNote->id, 'POST', ['journal_id' => $journal->id]);

            return $creditNote->fresh();
        });
    }

    /**
     * Reduce what is owed on the credited invoice by up to the credit's total. The remainder stays as unapplied
     * credit with the supplier.
     */
    protected function applyToInvoice(SupplierCreditNote $creditNote): void
    {
        if (! $creditNote->supplier_invoice_id) {
            return;
        }

        $invoice = SupplierInvoice::whereKey($creditNote->supplier_invoice_id)->lockForUpdate()->firstOrFail();
        $applied = bccomp((string) $creditNote->total_amount, (string) $invoice->outstanding_amount, 4) > 0
            ? (string) $invoice->outstanding_amount
            : (string) $creditNote->total_amount;

        $creditNote->forceFill(['applied_amount' => $applied])->save();

        $invoice->calculateOutstanding();
        $invoice->save();
    }

    /**
     * Currency and rate follow the credited invoice so the payable clears at the rate it was booked at.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function headerAttributes(array $data): array
    {
        $invoice = isset($data['supplier_invoice_id']) ? SupplierInvoice::findOrFail($data['supplier_invoice_id']) : null;

        if ($invoice && (int) $invoice->supplier_id !== (int) $data['supplier_id']) {
            throw new InvalidAccountingTransactionException('The credited invoice belongs to another supplier.');
        }

        $currencyId = $invoice ? $invoice->currency_id : ($data['currency_id'] ?? null);

        return [
            'supplier_id' => $data['supplier_id'],
            'supplier_invoice_id' => $invoice?->id,
            'credit_note_date' => $data['credit_note_date'],
            'currency_id' => $currencyId,
            'exchange_rate' => $invoice
                ? $invoice->exchange_rate
                : app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $currencyId, $data['credit_note_date']),
            'discount_amount' => $data['discount_amount'] ?? 0,
            'reason' => $data['reason'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function replaceLines(SupplierCreditNote $creditNote, array $lines): void
    {
        $creditNote->lines()->delete();

        foreach ($lines as $line) {
            $creditNote->lines()->create([
                'account_id' => $line['account_id'],
                'description' => $line['description'],
                'quantity' => $line['quantity'] ?? 1,
                'unit_price' => $line['unit_price'] ?? 0,
                'discount_amount' => $line['discount_amount'] ?? 0,
                'tax_id' => $line['tax_id'] ?? null,
                'supply_type' => $line['supply_type'] ?? null,
                'is_reverse_charge' => (bool) ($line['is_reverse_charge'] ?? false),
            ]);
        }

        $this->documentTax->recalculate($creditNote);
    }

    /**
     * @param  array<int, string>  $allowed
     */
    protected function requireStatus(SupplierCreditNote $creditNote, array $allowed, string $action): void
    {
        if (! in_array($creditNote->status, $allowed, true)) {
            throw new InvalidAccountingTransactionException("A {$creditNote->status} credit note cannot be {$action}.");
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function transition(SupplierCreditNote $creditNote, string $status, string $action, array $context = []): SupplierCreditNote
    {
        $previous = $creditNote->status;
        $creditNote->status = $status;
        $creditNote->updated_by = Auth::id();
        $creditNote->save();

        $this->audit->logCustom('Finance', 'SupplierCreditNote', $creditNote->id, $action, ['previous_status' => $previous, ...$context]);

        return $creditNote->fresh();
    }
}
