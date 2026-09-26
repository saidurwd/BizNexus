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
use Modules\Finance\Contracts\SalesCostOfGoods;
use Modules\Finance\Models\CustomerCreditNote;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Services\Concerns\EnforcesSegregationOfDuties;

/**
 * Customer credit notes: drafted with lines and tax codes, approved by someone other than their creator, and
 * posted as the mirror of a sales invoice (debit revenue and output tax, credit the receivable).
 */
class CustomerCreditNoteService
{
    use EnforcesSegregationOfDuties;

    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService,
        protected DocumentTaxService $documentTax,
    ) {}

    /**
     * @param  array{company_id: int, customer_id: int, customer_invoice_id?: int|null, note_number?: string|null, note_date: string, currency_id?: int|null, discount_amount?: numeric-string|float|null, description?: string|null, lines: array<int, array<string, mixed>>}  $data
     */
    public function create(array $data): CustomerCreditNote
    {
        return DB::transaction(function () use ($data) {
            $creditNote = CustomerCreditNote::create([
                'company_id' => $data['company_id'],
                'note_number' => $data['note_number'] ?? $this->documentNumber->generateNumber($data['company_id'], 'CN'),
                'status' => CustomerCreditNote::STATUS_DRAFT,
                'created_by' => Auth::id(),
                ...$this->headerAttributes($data),
            ]);

            $this->replaceLines($creditNote, $data['lines']);
            $this->audit->logCreate('Finance', 'CustomerCreditNote', $creditNote->id, $creditNote->toArray());

            return $creditNote->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CustomerCreditNote $creditNote, array $data): CustomerCreditNote
    {
        $this->requireStatus($creditNote, [CustomerCreditNote::STATUS_DRAFT, CustomerCreditNote::STATUS_REJECTED], 'edited');

        return DB::transaction(function () use ($creditNote, $data) {
            $creditNote->update([
                'note_number' => $data['note_number'] ?? $creditNote->note_number,
                'status' => CustomerCreditNote::STATUS_DRAFT,
                'rejection_reason' => null,
                'updated_by' => Auth::id(),
                ...$this->headerAttributes($data),
            ]);

            $this->replaceLines($creditNote, $data['lines']);

            return $creditNote->fresh();
        });
    }

    public function submit(CustomerCreditNote $creditNote): CustomerCreditNote
    {
        $this->requireStatus($creditNote, [CustomerCreditNote::STATUS_DRAFT], 'submitted');

        if ($creditNote->lines()->doesntExist() || bccomp((string) $creditNote->total_amount, '0', 4) <= 0) {
            throw new InvalidAccountingTransactionException('A credit note needs at least one line and a positive total.');
        }

        $submitted = $this->transition($creditNote, CustomerCreditNote::STATUS_SUBMITTED, 'SUBMIT');
        app(ApprovalNotifier::class)->documentSubmitted($submitted->company_id, 'finance.customer-credit-notes.approve', __('Customer credit note'), $submitted->note_number, route('finance.customer-credit-notes.show', $submitted->id), (string) $submitted->total_amount, $submitted->currency?->code);

        return $submitted;
    }

    public function approve(CustomerCreditNote $creditNote): CustomerCreditNote
    {
        $this->requireStatus($creditNote, [CustomerCreditNote::STATUS_SUBMITTED], 'approved');
        $this->ensureApproverIsNotCreator($creditNote, 'customer credit note');

        $creditNote->approved_by = Auth::id();

        return $this->transition($creditNote, CustomerCreditNote::STATUS_APPROVED, 'APPROVE');
    }

    public function reject(CustomerCreditNote $creditNote, ?string $reason): CustomerCreditNote
    {
        $this->requireStatus($creditNote, [CustomerCreditNote::STATUS_SUBMITTED], 'rejected');

        $creditNote->rejection_reason = $reason;

        return $this->transition($creditNote, CustomerCreditNote::STATUS_REJECTED, 'REJECT', ['reason' => $reason]);
    }

    public function cancel(CustomerCreditNote $creditNote): CustomerCreditNote
    {
        $this->requireStatus($creditNote, [CustomerCreditNote::STATUS_DRAFT, CustomerCreditNote::STATUS_SUBMITTED, CustomerCreditNote::STATUS_APPROVED, CustomerCreditNote::STATUS_REJECTED], 'cancelled');

        return $this->transition($creditNote, CustomerCreditNote::STATUS_CANCELLED, 'CANCEL');
    }

    /**
     * Post the reversal of revenue and output tax against the receivable, record negative output tax for the
     * return, and apply the credit to the invoice it was issued against (up to what is still owed on it).
     */
    public function post(CustomerCreditNote $creditNote): CustomerCreditNote
    {
        return DB::transaction(function () use ($creditNote) {
            $creditNote = CustomerCreditNote::whereKey($creditNote->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($creditNote, [CustomerCreditNote::STATUS_APPROVED], 'posted');

            $customer = $creditNote->customer;
            $journalLines = app(DocumentJournalBuilder::class)->sales(
                $creditNote,
                $customer->receivable_account_id ?? app(DefaultAccountService::class)->getReceivableAccount($creditNote->company_id),
                "Credit note {$creditNote->note_number} to {$customer->name}",
                isCredit: true,
            )['lines'];

            $journal = $this->journalService->postFromSource([
                'company_id' => $creditNote->company_id,
                'journal_date' => $creditNote->note_date->toDateString(),
                'reference_type' => 'customer_credit_note',
                'reference_id' => $creditNote->id,
                'description' => "Customer Credit Note #{$creditNote->note_number}",
                'currency_id' => $creditNote->currency_id,
                'exchange_rate' => $creditNote->exchange_rate,
                'lines' => $journalLines,
            ]);

            $this->documentTax->recordTransactions($creditNote, $journal->id);

            $creditNote->forceFill([
                'status' => CustomerCreditNote::STATUS_POSTED,
                'journal_id' => $journal->id,
                'posted_at' => now(),
            ])->save();
            $this->applyToInvoice($creditNote);
            app(SalesCostOfGoods::class)->creditNotePosted($creditNote);

            $this->audit->logCustom('Finance', 'CustomerCreditNote', $creditNote->id, 'POST', ['journal_id' => $journal->id]);

            return $creditNote->fresh();
        });
    }

    /**
     * Reduce what is owed on the credited (posted) invoice by up to the credit's total. The remainder stays on
     * the customer's account as unapplied credit.
     */
    protected function applyToInvoice(CustomerCreditNote $creditNote): void
    {
        if (! $creditNote->customer_invoice_id) {
            return;
        }

        $invoice = CustomerInvoice::whereKey($creditNote->customer_invoice_id)->lockForUpdate()->firstOrFail();
        $applied = bccomp((string) $creditNote->total_amount, (string) $invoice->outstanding_amount, 4) > 0
            ? (string) $invoice->outstanding_amount
            : (string) $creditNote->total_amount;

        $creditNote->forceFill(['applied_amount' => $applied])->save();

        $invoice->calculateOutstanding();
        $invoice->save();
    }

    /**
     * Currency and rate follow the credited invoice so the receivable clears at the rate it was booked at.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function headerAttributes(array $data): array
    {
        $invoice = isset($data['customer_invoice_id']) ? CustomerInvoice::findOrFail($data['customer_invoice_id']) : null;

        if ($invoice && (int) $invoice->customer_id !== (int) $data['customer_id']) {
            throw new InvalidAccountingTransactionException('The credited invoice belongs to another customer.');
        }

        $currencyId = $invoice ? $invoice->currency_id : ($data['currency_id'] ?? null);

        return [
            'customer_id' => $data['customer_id'],
            'customer_invoice_id' => $invoice?->id,
            'note_date' => $data['note_date'],
            'currency_id' => $currencyId,
            'exchange_rate' => $invoice
                ? $invoice->exchange_rate
                : app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $currencyId, $data['note_date']),
            'discount_amount' => $data['discount_amount'] ?? 0,
            'description' => $data['description'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function replaceLines(CustomerCreditNote $creditNote, array $lines): void
    {
        $creditNote->lines()->delete();

        foreach ($lines as $line) {
            $creditNote->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'warehouse_id' => $line['warehouse_id'] ?? null,
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
    protected function requireStatus(CustomerCreditNote $creditNote, array $allowed, string $action): void
    {
        if (! in_array($creditNote->status, $allowed, true)) {
            throw new InvalidAccountingTransactionException("A {$creditNote->status} credit note cannot be {$action}.");
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function transition(CustomerCreditNote $creditNote, string $status, string $action, array $context = []): CustomerCreditNote
    {
        $previous = $creditNote->status;
        $creditNote->status = $status;
        $creditNote->updated_by = Auth::id();
        $creditNote->save();

        $this->audit->logCustom('Finance', 'CustomerCreditNote', $creditNote->id, $action, ['previous_status' => $previous, ...$context]);

        return $creditNote->fresh();
    }
}
