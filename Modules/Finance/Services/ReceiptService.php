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

            foreach ($data['allocations'] ?? [] as $allocation) {
                $receipt->allocations()->create([
                    'customer_invoice_id' => $allocation['invoice_id'],
                    'amount' => $allocation['amount'],
                ]);
            }

            $this->audit->logCreate('Finance', 'CustomerReceipt', $receipt->id, $receipt->toArray());

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

            return $receipt->fresh();
        });
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

    public function allocateReceipt(CustomerReceipt $receipt, int $invoiceId, float $amount): ReceiptAllocation
    {
        $invoice = CustomerInvoice::findOrFail($invoiceId);

        if ($invoice->outstanding_amount < $amount) {
            throw new InvalidAccountingTransactionException('Receipt amount exceeds invoice outstanding balance');
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
            $amount = (float) $invoice->outstanding_amount;

            $invoiceAging = [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name,
                'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'days_outstanding' => $days,
                'amount' => $amount,
                'bucket' => $this->getAgingBucket($days),
            ];

            $aging['invoices'][] = $invoiceAging;

            switch ($this->getAgingBucket($days)) {
                case 'current':
                    $aging['current'] = bcadd($aging['current'], $amount, 4);
                    break;
                case 'days_1_30':
                    $aging['days_1_30'] = bcadd($aging['days_1_30'], $amount, 4);
                    break;
                case 'days_31_60':
                    $aging['days_31_60'] = bcadd($aging['days_31_60'], $amount, 4);
                    break;
                case 'days_61_90':
                    $aging['days_61_90'] = bcadd($aging['days_61_90'], $amount, 4);
                    break;
                case 'days_91_180':
                    $aging['days_91_180'] = bcadd($aging['days_91_180'], $amount, 4);
                    break;
                case 'days_180_plus':
                    $aging['days_180_plus'] = bcadd($aging['days_180_plus'], $amount, 4);
                    break;
            }

            $aging['total'] = bcadd($aging['total'], $amount, 4);
        }

        return $aging;
    }

    protected function getAgingBucket(int $days): string
    {
        if ($days <= 0) {
            return 'current';
        } elseif ($days <= 30) {
            return 'days_1_30';
        } elseif ($days <= 60) {
            return 'days_31_60';
        } elseif ($days <= 90) {
            return 'days_61_90';
        } elseif ($days <= 180) {
            return 'days_91_180';
        }

        return 'days_180_plus';
    }
}
