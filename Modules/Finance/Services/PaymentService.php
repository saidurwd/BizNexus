<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Models\SupplierPayment;
use Modules\Finance\Models\PaymentAllocation;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\Journal;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Services\AuditService;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;

class PaymentService
{
    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService
    ) {}

    public function createPayment(array $data): SupplierPayment
    {
        return DB::transaction(function () use ($data) {
            $payment = SupplierPayment::create([
                'company_id' => $data['company_id'],
                'supplier_id' => $data['supplier_id'],
                'payment_number' => $data['payment_number'] ?? $this->documentNumber->generateNumber($data['company_id'], 'PV'),
                'payment_date' => $data['payment_date'],
                'currency_id' => $data['currency_id'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? 'BANK_TRANSFER',
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => SupplierPayment::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['allocations'] ?? [] as $allocation) {
                $payment->allocations()->create([
                    'supplier_invoice_id' => $allocation['invoice_id'],
                    'amount' => $allocation['amount'],
                ]);
            }

            $this->audit->logCreate('Finance', 'SupplierPayment', $payment->id, $payment->toArray());

            return $payment;
        });
    }

    public function postPayment(SupplierPayment $payment): SupplierPayment
    {
        if (!$payment->isDraft()) {
            throw new InvalidAccountingTransactionException('Payment cannot be posted');
        }

        return DB::transaction(function () use ($payment) {
            $supplier = $payment->supplier;
            $company = $payment->company;

            $journalLines = [];

            $journalLines[] = [
                'account_id' => $supplier->payable_account_id ?? $this->getDefaultPayableAccount($company->id),
                'description' => "Payment to {$supplier->name}",
                'debit' => $payment->amount,
                'credit' => 0,
            ];

            if ($payment->bank_account_id) {
                $bankAccount = $payment->bankAccount;
                $journalLines[] = [
                    'account_id' => $bankAccount->gl_account_id,
                    'description' => "Bank Payment #{$payment->payment_number}",
                    'debit' => 0,
                    'credit' => $payment->amount,
                ];
            } else {
                $journalLines[] = [
                    'account_id' => $this->getDefaultCashAccount($company->id),
                    'description' => "Cash Payment #{$payment->payment_number}",
                    'debit' => 0,
                    'credit' => $payment->amount,
                ];
            }

            $journal = $this->journalService->create([
                'company_id' => $payment->company_id,
                'journal_date' => $payment->payment_date->toDateString(),
                'reference_type' => 'supplier_payment',
                'reference_id' => $payment->id,
                'description' => "Supplier Payment #{$payment->payment_number}",
                'currency_id' => $payment->currency_id,
                'exchange_rate' => $payment->exchange_rate,
                'lines' => $journalLines,
            ]);

            $this->journalService->submit($journal);
            $this->journalService->approve($journal);
            $this->journalService->post($journal);

            foreach ($payment->allocations as $allocation) {
                $allocation->invoice->calculateOutstanding();
                $allocation->invoice->save();
            }

            $payment->update([
                'status' => SupplierPayment::STATUS_POSTED,
                'journal_id' => $journal->id,
            ]);

            $this->audit->logCustom('Finance', 'SupplierPayment', $payment->id, 'POST', [
                'journal_id' => $journal->id,
            ]);

            return $payment->fresh();
        });
    }

    protected function getDefaultPayableAccount(int $companyId): int
    {
        $account = \Modules\Finance\Models\Account::where('company_id', $companyId)
            ->where('account_code', 'like', '2100%')
            ->where('is_postable', true)
            ->first();

        return $account?->id ?? throw new \Exception('No payable account found');
    }

    protected function getDefaultCashAccount(int $companyId): int
    {
        $account = \Modules\Finance\Models\Account::where('company_id', $companyId)
            ->where('account_code', 'like', '1110%')
            ->where('is_postable', true)
            ->first();

        return $account?->id ?? throw new \Exception('No cash account found');
    }

    public function allocatePayment(SupplierPayment $payment, int $invoiceId, float $amount): PaymentAllocation
    {
        $invoice = SupplierInvoice::findOrFail($invoiceId);

        if ($invoice->outstanding_amount < $amount) {
            throw new InvalidAccountingTransactionException('Payment amount exceeds invoice outstanding balance');
        }

        $allocation = $payment->allocations()->create([
            'supplier_invoice_id' => $invoiceId,
            'amount' => $amount,
        ]);

        return $allocation;
    }

    public function getAPAging(int $companyId, ?int $supplierId = null): array
    {
        $query = SupplierInvoice::with('supplier')
            ->where('company_id', $companyId)
            ->pending();

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
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
                'supplier_name' => $invoice->supplier->name,
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
