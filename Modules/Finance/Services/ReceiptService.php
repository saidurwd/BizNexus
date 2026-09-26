<?php

namespace Modules\Finance\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Finance\Events\ReceiptApproved;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\CustomerReceipt;
use Modules\Finance\Models\ReceiptAllocation;
use Modules\Finance\Services\Concerns\AgesOpenInvoices;
use Modules\Finance\Services\Concerns\EnforcesSegregationOfDuties;
use Modules\Workflow\Services\WorkflowService;

class ReceiptService
{
    use AgesOpenInvoices;
    use EnforcesSegregationOfDuties;

    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService
    ) {}

    public function createReceipt(array $data): CustomerReceipt
    {
        return DB::transaction(function () use ($data) {
            $receipt = CustomerReceipt::create([
                'company_id' => $data['company_id'],
                'customer_id' => $data['customer_id'],
                'receipt_number' => $data['receipt_number'] ?? $this->documentNumber->generateNumber($data['company_id'], 'RV'),
                'receipt_date' => $data['receipt_date'],
                'currency_id' => $data['currency_id'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $data['currency_id'] ?? null, $data['receipt_date']),
                'amount' => $data['amount'],
                'receipt_method' => $data['receipt_method'] ?? 'BANK_TRANSFER',
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => CustomerReceipt::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            $totalAllocated = 0;
            foreach ($data['allocations'] ?? [] as $allocation) {
                $invoice = CustomerInvoice::where('id', $allocation['invoice_id'])
                    ->where('customer_id', $data['customer_id'])
                    ->firstOrFail();

                $outstanding = $invoice->outstanding_amount - $invoice->allocations()
                    ->where('customer_receipt_id', '!=', $receipt->id)
                    ->sum('amount');

                if ($allocation['amount'] > $outstanding) {
                    throw new InvalidAccountingTransactionException(
                        "Allocation amount ({$allocation['amount']}) exceeds outstanding amount ({$outstanding}) for invoice {$invoice->invoice_number}"
                    );
                }

                $totalAllocated = bcadd($totalAllocated, $allocation['amount'], 4);
                $receipt->allocations()->create([
                    'customer_invoice_id' => $allocation['invoice_id'],
                    'amount' => $allocation['amount'],
                ]);
            }

            if (bccomp($totalAllocated, $data['amount'], 4) > 0) {
                throw new InvalidAccountingTransactionException(
                    "Total allocated amount ({$totalAllocated}) exceeds receipt amount ({$data['amount']})"
                );
            }

            $this->audit->logCreate('Finance', 'CustomerReceipt', $receipt->id, $receipt->toArray());

            try {
                app(WorkflowService::class)->createInstance(
                    'customer_receipt',
                    $receipt->id,
                    CustomerReceipt::STATUS_DRAFT,
                    $receipt->company_id
                );
            } catch (\Throwable $e) {
                // Workflow definitions may not be seeded yet
            }

            return $receipt;
        });
    }

    public function postReceipt(CustomerReceipt $receipt): CustomerReceipt
    {
        return DB::transaction(function () use ($receipt) {
            $receipt = CustomerReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if (! $receipt->isApproved()) {
                throw new InvalidAccountingTransactionException('Only approved receipts can be posted');
            }

            $customer = $receipt->customer;
            $company = $receipt->company;

            $journalLines = [];

            if ($receipt->bank_account_id) {
                $bankAccount = $receipt->bankAccount;
                $journalLines[] = [
                    'account_id' => $bankAccount->gl_account_id,
                    'description' => "Bank Receipt #{$receipt->receipt_number}",
                    'debit' => $receipt->amount,
                    'credit' => 0,
                ];
            } else {
                $journalLines[] = [
                    'account_id' => $this->getDefaultCashAccount($company->id),
                    'description' => "Cash Receipt #{$receipt->receipt_number}",
                    'debit' => $receipt->amount,
                    'credit' => 0,
                ];
            }

            $receivableAccountId = $customer->receivable_account_id ?? $this->getDefaultReceivableAccount($company->id);

            $journalLines[] = [
                'account_id' => $receivableAccountId,
                'description' => "Receipt from {$customer->name}",
                'debit' => 0,
                'credit' => $receipt->amount,
            ];

            $journalLines = [...$journalLines, ...app(RealizedExchangeDifferenceService::class)->settlementLines(
                $company,
                $receipt->allocations()->with('invoice')->get(),
                $receipt->currency_id,
                (string) $receipt->exchange_rate,
                $receivableAccountId,
                RealizedExchangeDifferenceService::SIDE_RECEIVABLE,
            )];

            $journal = $this->journalService->postFromSource([
                'company_id' => $receipt->company_id,
                'journal_date' => $receipt->receipt_date->toDateString(),
                'reference_type' => 'customer_receipt',
                'reference_id' => $receipt->id,
                'description' => "Customer Receipt #{$receipt->receipt_number}",
                'currency_id' => $receipt->currency_id,
                'exchange_rate' => $receipt->exchange_rate,
                'lines' => $journalLines,
            ]);

            $receipt->update([
                'status' => CustomerReceipt::STATUS_POSTED,
                'journal_id' => $journal->id,
            ]);

            foreach ($receipt->allocations as $allocation) {
                $allocation->invoice->calculateOutstanding();
                $allocation->invoice->save();
            }

            $this->audit->logCustom('Finance', 'CustomerReceipt', $receipt->id, 'POST', [
                'journal_id' => $journal->id,
            ]);

            try {
                app(WorkflowService::class)->transitionInstance(
                    'customer_receipt',
                    $receipt->id,
                    CustomerReceipt::STATUS_POSTED
                );
            } catch (\Throwable $e) {
                // Workflow definitions may not be seeded yet
            }

            event(new ReceiptApproved($receipt));

            return $receipt->fresh();
        });
    }

    public function submitReceipt(CustomerReceipt $receipt): CustomerReceipt
    {
        if (! $receipt->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft receipts can be submitted');
        }

        $receipt->update(['status' => CustomerReceipt::STATUS_SUBMITTED]);

        $this->audit->logCustom('Finance', 'CustomerReceipt', $receipt->id, 'SUBMIT', [
            'previous_status' => CustomerReceipt::STATUS_DRAFT,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'customer_receipt',
                $receipt->id,
                CustomerReceipt::STATUS_SUBMITTED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $receipt->fresh();
    }

    public function approveReceipt(CustomerReceipt $receipt): CustomerReceipt
    {
        if (! $receipt->isDraft() && ! $receipt->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only draft or submitted receipts can be approved');
        }

        $this->ensureApproverIsNotCreator($receipt, 'receipt');

        $receipt->update(['status' => CustomerReceipt::STATUS_APPROVED]);

        $this->audit->logCustom('Finance', 'CustomerReceipt', $receipt->id, 'APPROVE', [
            'previous_status' => CustomerReceipt::STATUS_SUBMITTED,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'customer_receipt',
                $receipt->id,
                CustomerReceipt::STATUS_APPROVED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $receipt->fresh();
    }

    public function rejectReceipt(CustomerReceipt $receipt, ?string $reason = null): CustomerReceipt
    {
        if (! $receipt->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted receipts can be rejected');
        }

        $receipt->update(['status' => CustomerReceipt::STATUS_REJECTED]);

        $this->audit->logCustom('Finance', 'CustomerReceipt', $receipt->id, 'REJECT', [
            'previous_status' => CustomerReceipt::STATUS_SUBMITTED,
            'reason' => $reason,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'customer_receipt',
                $receipt->id,
                CustomerReceipt::STATUS_REJECTED,
                $reason
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $receipt->fresh();
    }

    public function cancelReceipt(CustomerReceipt $receipt): CustomerReceipt
    {
        if ($receipt->isPosted()) {
            throw new InvalidAccountingTransactionException('Posted receipts cannot be cancelled directly');
        }

        $receipt->update(['status' => CustomerReceipt::STATUS_CANCELLED]);

        $this->audit->logCustom('Finance', 'CustomerReceipt', $receipt->id, 'CANCEL', [
            'previous_status' => $receipt->status,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'customer_receipt',
                $receipt->id,
                CustomerReceipt::STATUS_CANCELLED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $receipt->fresh();
    }

    public function allocateReceipt(int $receiptId, int $invoiceId, float $amount): ReceiptAllocation
    {
        $receipt = CustomerReceipt::findOrFail($receiptId);
        $invoice = CustomerInvoice::where('id', $invoiceId)
            ->where('customer_id', $receipt->customer_id)
            ->firstOrFail();

        if (! $receipt->isDraft()) {
            throw new InvalidAccountingTransactionException('Can only allocate from draft receipts');
        }

        $outstanding = $invoice->outstanding_amount - $invoice->allocations()
            ->where('customer_receipt_id', '!=', $receiptId)
            ->sum('amount');

        if ($amount > $outstanding) {
            throw new InvalidAccountingTransactionException(
                "Allocation amount ({$amount}) exceeds outstanding amount ({$outstanding}) for invoice {$invoice->invoice_number}"
            );
        }

        $totalAllocated = $receipt->allocations()->sum('amount') + $amount;
        if ($totalAllocated > $receipt->amount) {
            throw new InvalidAccountingTransactionException(
                "Total allocated amount ({$totalAllocated}) exceeds receipt amount ({$receipt->amount})"
            );
        }

        $allocation = $receipt->allocations()->create([
            'customer_invoice_id' => $invoiceId,
            'amount' => $amount,
        ]);

        return $allocation;
    }

    /**
     * Open customer invoices aged by days past due, in the functional currency, with a row per customer.
     */
    public function getARAging(int $companyId, ?int $customerId = null, ?CarbonInterface $asOf = null): array
    {
        $invoices = CustomerInvoice::with(['customer', 'currency'])
            ->where('company_id', $companyId)
            ->pending()
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->get();

        return $this->ageOpenInvoices($invoices, 'customer', $asOf);
    }

    protected function getDefaultReceivableAccount(int $companyId): int
    {
        return app(DefaultAccountService::class)->getReceivableAccount($companyId);
    }

    protected function getDefaultCashAccount(int $companyId): int
    {
        return app(DefaultAccountService::class)->getCashAccount($companyId);
    }

    public function updateReceipt(CustomerReceipt $receipt, array $data): CustomerReceipt
    {
        if (! $receipt->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft receipts can be updated');
        }

        $receipt->update([
            'customer_id' => $data['customer_id'],
            'receipt_number' => $data['receipt_number'],
            'receipt_date' => $data['receipt_date'],
            'currency_id' => $data['currency_id'] ?? null,
            'exchange_rate' => $data['exchange_rate'] ?? app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $data['currency_id'] ?? null, $data['receipt_date']),
            'amount' => $data['amount'],
            'receipt_method' => $data['receipt_method'] ?? 'BANK_TRANSFER',
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        $receipt->allocations()->delete();

        $totalAllocated = 0;
        foreach ($data['allocations'] ?? [] as $allocation) {
            $invoice = CustomerInvoice::where('id', $allocation['invoice_id'])
                ->where('customer_id', $data['customer_id'])
                ->firstOrFail();

            $outstanding = $invoice->outstanding_amount - $invoice->allocations()
                ->where('customer_receipt_id', '!=', $receipt->id)
                ->sum('amount');

            if ($allocation['amount'] > $outstanding) {
                throw new InvalidAccountingTransactionException(
                    "Allocation amount ({$allocation['amount']}) exceeds outstanding amount ({$outstanding}) for invoice {$invoice->invoice_number}"
                );
            }

            $totalAllocated = bcadd($totalAllocated, $allocation['amount'], 4);
            $receipt->allocations()->create([
                'customer_invoice_id' => $allocation['invoice_id'],
                'amount' => $allocation['amount'],
            ]);
        }

        if (bccomp($totalAllocated, $data['amount'], 4) > 0) {
            throw new InvalidAccountingTransactionException(
                "Total allocated amount ({$totalAllocated}) exceeds receipt amount ({$data['amount']})"
            );
        }

        return $receipt->fresh();
    }
}
