<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Models\CustomerReceipt;
use Modules\Finance\Models\ReceiptAllocation;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\Journal;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Services\AuditService;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;

class ReceiptService
{
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
                'exchange_rate' => $data['exchange_rate'] ?? 1,
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
                app(\Modules\Workflow\Services\WorkflowService::class)->createInstance(
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
        if (!$receipt->isDraft()) {
            throw new InvalidAccountingTransactionException('Receipt cannot be posted');
        }

        return DB::transaction(function () use ($receipt) {
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

            $journalLines[] = [
                'account_id' => $customer->receivable_account_id ?? $this->getDefaultReceivableAccount($company->id),
                'description' => "Receipt from {$customer->name}",
                'debit' => 0,
                'credit' => $receipt->amount,
            ];

            $journal = $this->journalService->create([
                'company_id' => $receipt->company_id,
                'journal_date' => $receipt->receipt_date->toDateString(),
                'reference_type' => 'customer_receipt',
                'reference_id' => $receipt->id,
                'description' => "Customer Receipt #{$receipt->receipt_number}",
                'currency_id' => $receipt->currency_id,
                'exchange_rate' => $receipt->exchange_rate,
                'lines' => $journalLines,
            ]);

            $this->journalService->submit($journal);
            $this->journalService->approve($journal);
            $this->journalService->post($journal);

            foreach ($receipt->allocations as $allocation) {
                $allocation->invoice->calculateOutstanding();
                $allocation->invoice->save();
            }

            $receipt->update([
                'status' => CustomerReceipt::STATUS_POSTED,
                'journal_id' => $journal->id,
            ]);

            $this->audit->logCustom('Finance', 'CustomerReceipt', $receipt->id, 'POST', [
                'journal_id' => $journal->id,
            ]);

            try {
                app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
                    'customer_receipt',
                    $receipt->id,
                    CustomerReceipt::STATUS_POSTED
                );
            } catch (\Throwable $e) {
                // Workflow definitions may not be seeded yet
            }

            event(new \Modules\Finance\Events\ReceiptApproved($receipt));

            return $receipt->fresh();
        });
    }

    public function submitReceipt(CustomerReceipt $receipt): CustomerReceipt
    {
        if (!$receipt->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft receipts can be submitted');
        }

        $receipt->update(['status' => CustomerReceipt::STATUS_SUBMITTED]);

        $this->audit->logCustom('Finance', 'CustomerReceipt', $receipt->id, 'SUBMIT', [
            'previous_status' => CustomerReceipt::STATUS_DRAFT,
        ]);

        try {
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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
        if (!$receipt->isDraft() && !$receipt->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only draft or submitted receipts can be approved');
        }

        $receipt->update(['status' => CustomerReceipt::STATUS_APPROVED]);

        $this->audit->logCustom('Finance', 'CustomerReceipt', $receipt->id, 'APPROVE', [
            'previous_status' => CustomerReceipt::STATUS_SUBMITTED,
        ]);

        try {
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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
        if (!$receipt->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted receipts can be rejected');
        }

        $receipt->update(['status' => CustomerReceipt::STATUS_REJECTED]);

        $this->audit->logCustom('Finance', 'CustomerReceipt', $receipt->id, 'REJECT', [
            'previous_status' => CustomerReceipt::STATUS_SUBMITTED,
            'reason' => $reason,
        ]);

        try {
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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

        if (!$receipt->isDraft()) {
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

    public function getARAging(int $companyId, ?int $customerId = null): array
    {
        $query = CustomerInvoice::with('customer')
            ->where('company_id', $companyId)
            ->pending();

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $invoices = $query->get();

        $aging = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_91_180' => 0,
            'days_180_plus' => 0,
            'total' => 0,
            'invoices' => [],
        ];

        foreach ($invoices as $invoice) {
            $days = $invoice->getDaysOutstanding();
            $outstanding = $invoice->outstanding_amount;
            $aging['total'] = bcadd($aging['total'], $outstanding, 4);

            if ($days <= 0) {
                $aging['current'] = bcadd($aging['current'], $outstanding, 4);
            } elseif ($days <= 30) {
                $aging['days_1_30'] = bcadd($aging['days_1_30'], $outstanding, 4);
            } elseif ($days <= 60) {
                $aging['days_31_60'] = bcadd($aging['days_31_60'], $outstanding, 4);
            } elseif ($days <= 90) {
                $aging['days_61_90'] = bcadd($aging['days_61_90'], $outstanding, 4);
            } elseif ($days <= 180) {
                $aging['days_91_180'] = bcadd($aging['days_91_180'], $outstanding, 4);
            } else {
                $aging['days_180_plus'] = bcadd($aging['days_180_plus'], $outstanding, 4);
            }

            $aging['invoices'][] = [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name,
                'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'total_amount' => $invoice->total_amount,
                'outstanding_amount' => $outstanding,
                'days_outstanding' => $days,
            ];
        }

        return $aging;
    }

    protected function getDefaultReceivableAccount(int $companyId): int
    {
        $account = \Modules\Finance\Models\Account::where('company_id', $companyId)
            ->where('account_code', 'like', '1100%')
            ->where('is_postable', true)
            ->first();

        return $account?->id ?? throw new \Exception('No receivable account found');
    }

    protected function getDefaultCashAccount(int $companyId): int
    {
        $account = \Modules\Finance\Models\Account::where('company_id', $companyId)
            ->where('account_code', 'like', '1110%')
            ->where('is_postable', true)
            ->first();

        return $account?->id ?? throw new \Exception('No cash account found');
    }
}
