<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Finance\Events\SupplierInvoiceApproved;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\Concerns\EnforcesSegregationOfDuties;
use Modules\Workflow\Services\WorkflowService;

class SupplierInvoiceService
{
    use EnforcesSegregationOfDuties;

    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService,
        protected DefaultAccountService $defaultAccounts
    ) {}

    public function createInvoice(array $data): SupplierInvoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = SupplierInvoice::create([
                'company_id' => $data['company_id'],
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'] ?? $this->documentNumber->generateNumber($data['company_id'], 'SI'),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'currency_id' => $data['currency_id'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $data['currency_id'] ?? null, $data['invoice_date']),
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => 0,
                'outstanding_amount' => 0,
                'status' => SupplierInvoice::STATUS_DRAFT,
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['lines'] ?? [] as $lineData) {
                $invoice->lines()->create([
                    'account_id' => $lineData['account_id'],
                    'description' => $lineData['description'],
                    'quantity' => $lineData['quantity'] ?? 1,
                    'unit_price' => $lineData['unit_price'] ?? 0,
                    'subtotal' => 0,
                    'tax_id' => $lineData['tax_id'] ?? null,
                    'supply_type' => $lineData['supply_type'] ?? null,
                    'is_reverse_charge' => (bool) ($lineData['is_reverse_charge'] ?? false),
                    'supply_type' => $lineData['supply_type'] ?? null,
                    'is_reverse_charge' => (bool) ($lineData['is_reverse_charge'] ?? false),
                    'tax_amount' => 0,
                    'discount_amount' => $lineData['discount_amount'] ?? 0,
                    'total_amount' => 0,
                ]);

            }

            app(DocumentTaxService::class)->recalculate($invoice);

            $this->audit->logCreate('Finance', 'SupplierInvoice', $invoice->id, $invoice->toArray());

            try {
                app(WorkflowService::class)->createInstance(
                    'supplier_invoice',
                    $invoice->id,
                    SupplierInvoice::STATUS_DRAFT,
                    $invoice->company_id
                );
            } catch (\Throwable $e) {
                // Workflow definitions may not be seeded yet
            }

            return $invoice;
        });
    }

    public function postInvoice(SupplierInvoice $invoice): SupplierInvoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = SupplierInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (! $invoice->isApproved()) {
                throw new InvalidAccountingTransactionException('Only approved invoices can be posted');
            }

            if ($invoice->lines->isEmpty()) {
                throw new InvalidAccountingTransactionException('Invoice must have at least one line item to post.');
            }

            $supplier = $invoice->supplier;
            $company = $invoice->company;

            $documentTax = app(DocumentTaxService::class);
            $journalLines = app(DocumentJournalBuilder::class)->purchase(
                $invoice,
                $supplier->payable_account_id ?? $this->defaultAccounts->getPayableAccount($company->id),
                "Payable to {$supplier->name}",
            )['lines'];

            $journal = $this->journalService->postFromSource([
                'company_id' => $invoice->company_id,
                'journal_date' => $invoice->invoice_date->toDateString(),
                'reference_type' => 'supplier_invoice',
                'reference_id' => $invoice->id,
                'description' => "Supplier Invoice #{$invoice->invoice_number}",
                'currency_id' => $invoice->currency_id,
                'exchange_rate' => $invoice->exchange_rate,
                'lines' => $journalLines,
            ]);

            $documentTax->recordTransactions($invoice, $journal->id);

            $invoice->update([
                'status' => SupplierInvoice::STATUS_POSTED,
                'journal_id' => $journal->id,
            ]);

            $this->audit->logCustom('Finance', 'SupplierInvoice', $invoice->id, 'POST', [
                'journal_id' => $journal->id,
            ]);

            try {
                app(WorkflowService::class)->transitionInstance(
                    'supplier_invoice',
                    $invoice->id,
                    SupplierInvoice::STATUS_POSTED
                );
            } catch (\Throwable $e) {
                // Workflow definitions may not be seeded yet
            }

            return $invoice->fresh();
        });
    }

    public function submitInvoice(SupplierInvoice $invoice): SupplierInvoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft invoices can be submitted');
        }

        $invoice->update(['status' => SupplierInvoice::STATUS_SUBMITTED]);

        $this->audit->logCustom('Finance', 'SupplierInvoice', $invoice->id, 'SUBMIT', [
            'previous_status' => SupplierInvoice::STATUS_DRAFT,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'supplier_invoice',
                $invoice->id,
                SupplierInvoice::STATUS_SUBMITTED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $invoice->fresh();
    }

    public function approveInvoice(SupplierInvoice $invoice): SupplierInvoice
    {
        if (! $invoice->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted invoices can be approved');
        }

        $this->ensureApproverIsNotCreator($invoice, 'supplier invoice');

        $invoice->update(['status' => SupplierInvoice::STATUS_APPROVED]);

        $this->audit->logCustom('Finance', 'SupplierInvoice', $invoice->id, 'APPROVE', [
            'previous_status' => SupplierInvoice::STATUS_SUBMITTED,
        ]);

        event(new SupplierInvoiceApproved($invoice));

        try {
            app(WorkflowService::class)->transitionInstance(
                'supplier_invoice',
                $invoice->id,
                SupplierInvoice::STATUS_APPROVED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $invoice->fresh();
    }

    public function rejectInvoice(SupplierInvoice $invoice, ?string $reason = null): SupplierInvoice
    {
        if (! $invoice->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted invoices can be rejected');
        }

        $invoice->update(['status' => SupplierInvoice::STATUS_REJECTED]);

        $this->audit->logCustom('Finance', 'SupplierInvoice', $invoice->id, 'REJECT', [
            'previous_status' => SupplierInvoice::STATUS_SUBMITTED,
            'reason' => $reason,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'supplier_invoice',
                $invoice->id,
                SupplierInvoice::STATUS_REJECTED,
                $reason
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $invoice->fresh();
    }

    public function cancelInvoice(SupplierInvoice $invoice): SupplierInvoice
    {
        if ($invoice->isPosted()) {
            throw new InvalidAccountingTransactionException('Posted invoices cannot be cancelled directly');
        }

        $invoice->update(['status' => SupplierInvoice::STATUS_CANCELLED]);

        return $invoice->fresh();
    }

    public function getOutstandingInvoices(int $supplierId): array
    {
        return SupplierInvoice::where('supplier_id', $supplierId)
            ->pending()
            ->orderBy('due_date')
            ->get()
            ->toArray();
    }

    public function updateInvoice(SupplierInvoice $invoice, array $data): SupplierInvoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft invoices can be updated');
        }

        $invoice->update([
            'supplier_id' => $data['supplier_id'],
            'invoice_number' => $data['invoice_number'],
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'],
            'currency_id' => $data['currency_id'] ?? null,
            'exchange_rate' => $data['exchange_rate'] ?? app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $data['currency_id'] ?? null, $data['invoice_date']),
            'discount_amount' => $data['discount_amount'] ?? 0,
            'description' => $data['description'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        $invoice->lines()->delete();

        foreach ($data['lines'] ?? [] as $lineData) {
            $invoice->lines()->create([
                'account_id' => $lineData['account_id'],
                'description' => $lineData['description'],
                'quantity' => $lineData['quantity'] ?? 1,
                'unit_price' => $lineData['unit_price'] ?? 0,
                'subtotal' => 0,
                'tax_id' => $lineData['tax_id'] ?? null,
                'supply_type' => $lineData['supply_type'] ?? null,
                'is_reverse_charge' => (bool) ($lineData['is_reverse_charge'] ?? false),
                'tax_amount' => 0,
                'discount_amount' => $lineData['discount_amount'] ?? 0,
                'total_amount' => 0,
            ]);

        }

        app(DocumentTaxService::class)->recalculate($invoice);

        return $invoice->fresh();
    }
}
