<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\ApprovalNotifier;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Support\Formatter;
use Modules\Finance\Events\CustomerInvoiceApproved;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Services\Concerns\EnforcesSegregationOfDuties;
use Modules\Workflow\Services\WorkflowService;

class CustomerInvoiceService
{
    use EnforcesSegregationOfDuties;

    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService
    ) {}

    public function createInvoice(array $data): CustomerInvoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = CustomerInvoice::create([
                'company_id' => $data['company_id'],
                'customer_id' => $data['customer_id'],
                'invoice_number' => $data['invoice_number'] ?? $this->documentNumber->generateNumber($data['company_id'], 'CI'),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? Customer::findOrFail($data['customer_id'])->dueDateFor(Carbon::parse($data['invoice_date']))->toDateString(),
                'currency_id' => $data['currency_id'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $data['currency_id'] ?? null, $data['invoice_date']),
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => 0,
                'outstanding_amount' => 0,
                'status' => CustomerInvoice::STATUS_DRAFT,
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
                    'tax_amount' => 0,
                    'discount_amount' => $lineData['discount_amount'] ?? 0,
                    'total_amount' => 0,
                ]);

            }

            app(DocumentTaxService::class)->recalculate($invoice);

            $this->audit->logCreate('Finance', 'CustomerInvoice', $invoice->id, $invoice->toArray());

            try {
                app(WorkflowService::class)->createInstance(
                    'customer_invoice',
                    $invoice->id,
                    CustomerInvoice::STATUS_DRAFT,
                    $invoice->company_id
                );
            } catch (\Throwable $e) {
                // Workflow definitions may not be seeded yet
            }

            return $invoice;
        });
    }

    public function postInvoice(CustomerInvoice $invoice): CustomerInvoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = CustomerInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (! $invoice->isApproved()) {
                throw new InvalidAccountingTransactionException('Only approved invoices can be posted');
            }

            if ($invoice->lines->isEmpty()) {
                throw new InvalidAccountingTransactionException('Invoice must have at least one line item to post.');
            }

            $customer = $invoice->customer;
            $company = $invoice->company;

            $documentTax = app(DocumentTaxService::class);
            $journalLines = app(DocumentJournalBuilder::class)->sales(
                $invoice,
                $customer->receivable_account_id ?? $this->getDefaultReceivableAccount($company->id),
                "Invoice {$invoice->invoice_number} to {$customer->name}",
            )['lines'];

            $journal = $this->journalService->postFromSource([
                'company_id' => $invoice->company_id,
                'journal_date' => $invoice->invoice_date->toDateString(),
                'reference_type' => 'customer_invoice',
                'reference_id' => $invoice->id,
                'description' => "Customer Invoice #{$invoice->invoice_number}",
                'currency_id' => $invoice->currency_id,
                'exchange_rate' => $invoice->exchange_rate,
                'lines' => $journalLines,
            ]);

            $documentTax->recordTransactions($invoice, $journal->id);

            $invoice->update([
                'status' => CustomerInvoice::STATUS_POSTED,
                'journal_id' => $journal->id,
            ]);

            $this->audit->logCustom('Finance', 'CustomerInvoice', $invoice->id, 'POST', [
                'journal_id' => $journal->id,
            ]);

            try {
                app(WorkflowService::class)->transitionInstance(
                    'customer_invoice',
                    $invoice->id,
                    CustomerInvoice::STATUS_POSTED
                );
            } catch (\Throwable $e) {
                // Workflow definitions may not be seeded yet
            }

            return $invoice->fresh();
        });
    }

    public function submitInvoice(CustomerInvoice $invoice): CustomerInvoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft invoices can be submitted');
        }

        $this->ensureWithinCreditLimit($invoice);

        $invoice->update(['status' => CustomerInvoice::STATUS_SUBMITTED]);

        $this->audit->logCustom('Finance', 'CustomerInvoice', $invoice->id, 'SUBMIT', [
            'previous_status' => CustomerInvoice::STATUS_DRAFT,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'customer_invoice',
                $invoice->id,
                CustomerInvoice::STATUS_SUBMITTED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        app(ApprovalNotifier::class)->documentSubmitted($invoice->company_id, 'finance.customer-invoices.approve', __('Customer invoice'), $invoice->invoice_number, route('finance.customer-invoices.show', $invoice->id), (string) $invoice->total_amount, $invoice->currency?->code);

        return $invoice->fresh();
    }

    public function approveInvoice(CustomerInvoice $invoice): CustomerInvoice
    {
        if (! $invoice->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted invoices can be approved');
        }

        $this->ensureApproverIsNotCreator($invoice, 'customer invoice');

        $invoice->update(['status' => CustomerInvoice::STATUS_APPROVED]);

        $this->audit->logCustom('Finance', 'CustomerInvoice', $invoice->id, 'APPROVE', [
            'previous_status' => CustomerInvoice::STATUS_SUBMITTED,
        ]);

        event(new CustomerInvoiceApproved($invoice));

        try {
            app(WorkflowService::class)->transitionInstance(
                'customer_invoice',
                $invoice->id,
                CustomerInvoice::STATUS_APPROVED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $invoice->fresh();
    }

    public function rejectInvoice(CustomerInvoice $invoice, ?string $reason = null): CustomerInvoice
    {
        if (! $invoice->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted invoices can be rejected');
        }

        $invoice->update(['status' => CustomerInvoice::STATUS_REJECTED]);

        $this->audit->logCustom('Finance', 'CustomerInvoice', $invoice->id, 'REJECT', [
            'previous_status' => CustomerInvoice::STATUS_SUBMITTED,
            'reason' => $reason,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'customer_invoice',
                $invoice->id,
                CustomerInvoice::STATUS_REJECTED,
                $reason
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $invoice->fresh();
    }

    protected function getDefaultReceivableAccount(int $companyId): int
    {
        return app(DefaultAccountService::class)->getReceivableAccount($companyId);
    }

    public function cancelInvoice(CustomerInvoice $invoice): CustomerInvoice
    {
        if ($invoice->isPosted()) {
            throw new InvalidAccountingTransactionException('Posted invoices cannot be cancelled directly');
        }

        $invoice->update(['status' => CustomerInvoice::STATUS_CANCELLED]);

        return $invoice->fresh();
    }

    public function getOutstandingInvoices(int $customerId): array
    {
        return CustomerInvoice::where('customer_id', $customerId)
            ->pending()
            ->orderBy('due_date')
            ->get()
            ->toArray();
    }

    public function updateInvoice(CustomerInvoice $invoice, array $data): CustomerInvoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft invoices can be updated');
        }

        $invoice->update([
            'customer_id' => $data['customer_id'],
            'invoice_number' => $data['invoice_number'] ?? $invoice->invoice_number,
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'] ?? Customer::findOrFail($data['customer_id'])->dueDateFor(Carbon::parse($data['invoice_date']))->toDateString(),
            'currency_id' => $data['currency_id'] ?? null,
            'exchange_rate' => $data['exchange_rate'] ?? app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $data['currency_id'] ?? null, $data['invoice_date']),
            'discount_amount' => $data['discount_amount'] ?? 0,
            'description' => $data['description'] ?? null,
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

    /**
     * Refuse (or, in "warn" mode, flag) an invoice that takes the customer's open receivables above their credit
     * limit. Amounts are compared in the functional currency.
     */
    public function ensureWithinCreditLimit(CustomerInvoice $invoice): void
    {
        $mode = config('finance.controls.credit_limit', 'block');
        $customer = $invoice->customer;

        if ($mode === 'off' || $customer?->credit_limit === null) {
            return;
        }

        $open = app(ReceiptService::class)->getARAging((int) $invoice->company_id, $customer->id)['total'];
        $exposure = bcadd($open, bcmul((string) $invoice->total_amount, (string) ($invoice->exchange_rate ?: 1), 4), 4);

        if (bccomp($exposure, (string) $customer->credit_limit, 4) <= 0) {
            return;
        }

        $message = __(':customer would owe :exposure, above the credit limit of :limit.', [
            'customer' => $customer->name,
            'exposure' => Formatter::amount($exposure),
            'limit' => Formatter::amount($customer->credit_limit),
        ]);

        if ($mode === 'block') {
            throw new InvalidAccountingTransactionException($message);
        }

        session()->flash('warning', $message);
    }
}
