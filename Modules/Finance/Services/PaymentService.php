<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Finance\Events\PaymentApproved;
use Modules\Finance\Models\PaymentAllocation;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierPayment;
use Modules\Workflow\Services\WorkflowService;

class PaymentService
{
    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService,
        protected DefaultAccountService $defaultAccounts
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

            $totalAllocated = 0;
            foreach ($data['allocations'] ?? [] as $allocation) {
                $invoice = SupplierInvoice::where('id', $allocation['invoice_id'])
                    ->where('supplier_id', $data['supplier_id'])
                    ->firstOrFail();

                $outstanding = $invoice->outstanding_amount - $invoice->allocations()
                    ->where('supplier_payment_id', '!=', $payment->id)
                    ->sum('amount');

                if ($allocation['amount'] > $outstanding) {
                    throw new InvalidAccountingTransactionException(
                        "Allocation amount ({$allocation['amount']}) exceeds outstanding amount ({$outstanding}) for invoice {$invoice->invoice_number}"
                    );
                }

                $totalAllocated = bcadd($totalAllocated, $allocation['amount'], 4);
                $payment->allocations()->create([
                    'supplier_invoice_id' => $allocation['invoice_id'],
                    'amount' => $allocation['amount'],
                ]);
            }

            if (bccomp($totalAllocated, $data['amount'], 4) > 0) {
                throw new InvalidAccountingTransactionException(
                    "Total allocated amount ({$totalAllocated}) exceeds payment amount ({$data['amount']})"
                );
            }

            $this->audit->logCreate('Finance', 'SupplierPayment', $payment->id, $payment->toArray());

            try {
                app(WorkflowService::class)->createInstance(
                    'supplier_payment',
                    $payment->id,
                    SupplierPayment::STATUS_DRAFT,
                    $payment->company_id
                );
            } catch (\Throwable $e) {
                // Workflow definitions may not be seeded yet
            }

            return $payment;
        });
    }

    public function postPayment(SupplierPayment $payment): SupplierPayment
    {
        if (! $payment->isApproved()) {
            throw new InvalidAccountingTransactionException('Only approved payments can be posted');
        }

        return DB::transaction(function () use ($payment) {
            $supplier = $payment->supplier;
            $company = $payment->company;

            $journalLines = [];

            $journalLines[] = [
                'account_id' => $supplier->payable_account_id ?? $this->defaultAccounts->getPayableAccount($company->id),
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
                    'account_id' => $this->defaultAccounts->getCashAccount($company->id),
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

            try {
                app(WorkflowService::class)->transitionInstance(
                    'supplier_payment',
                    $payment->id,
                    SupplierPayment::STATUS_POSTED
                );
            } catch (\Throwable $e) {
                // Workflow definitions may not be seeded yet
            }

            event(new PaymentApproved($payment));

            return $payment->fresh();
        });
    }

    public function submitPayment(SupplierPayment $payment): SupplierPayment
    {
        if (! $payment->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft payments can be submitted');
        }

        $payment->update(['status' => SupplierPayment::STATUS_SUBMITTED]);

        $this->audit->logCustom('Finance', 'SupplierPayment', $payment->id, 'SUBMIT', [
            'previous_status' => SupplierPayment::STATUS_DRAFT,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'supplier_payment',
                $payment->id,
                SupplierPayment::STATUS_SUBMITTED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $payment->fresh();
    }

    public function approvePayment(SupplierPayment $payment): SupplierPayment
    {
        if (! $payment->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted payments can be approved');
        }

        $payment->update(['status' => SupplierPayment::STATUS_APPROVED]);

        $this->audit->logCustom('Finance', 'SupplierPayment', $payment->id, 'APPROVE', [
            'previous_status' => SupplierPayment::STATUS_SUBMITTED,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'supplier_payment',
                $payment->id,
                SupplierPayment::STATUS_APPROVED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $payment->fresh();
    }

    public function rejectPayment(SupplierPayment $payment, ?string $reason = null): SupplierPayment
    {
        if (! $payment->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted payments can be rejected');
        }

        $payment->update(['status' => SupplierPayment::STATUS_REJECTED]);

        $this->audit->logCustom('Finance', 'SupplierPayment', $payment->id, 'REJECT', [
            'previous_status' => SupplierPayment::STATUS_SUBMITTED,
            'reason' => $reason,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'supplier_payment',
                $payment->id,
                SupplierPayment::STATUS_REJECTED,
                $reason
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $payment->fresh();
    }

    public function cancelPayment(SupplierPayment $payment): SupplierPayment
    {
        if ($payment->isPosted()) {
            throw new InvalidAccountingTransactionException('Posted payments cannot be cancelled directly');
        }

        $payment->update(['status' => SupplierPayment::STATUS_CANCELLED]);

        $this->audit->logCustom('Finance', 'SupplierPayment', $payment->id, 'CANCEL', [
            'previous_status' => $payment->status,
        ]);

        try {
            app(WorkflowService::class)->transitionInstance(
                'supplier_payment',
                $payment->id,
                SupplierPayment::STATUS_CANCELLED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $payment->fresh();
    }

    public function allocatePayment(int $paymentId, int $invoiceId, float $amount): PaymentAllocation
    {
        $payment = SupplierPayment::findOrFail($paymentId);
        $invoice = SupplierInvoice::where('id', $invoiceId)
            ->where('supplier_id', $payment->supplier_id)
            ->firstOrFail();

        if (! $payment->isDraft()) {
            throw new InvalidAccountingTransactionException('Can only allocate from draft payments');
        }

        $outstanding = $invoice->outstanding_amount - $invoice->allocations()
            ->where('supplier_payment_id', '!=', $paymentId)
            ->sum('amount');

        if ($amount > $outstanding) {
            throw new InvalidAccountingTransactionException(
                "Allocation amount ({$amount}) exceeds outstanding amount ({$outstanding}) for invoice {$invoice->invoice_number}"
            );
        }

        $totalAllocated = $payment->allocations()->sum('amount') + $amount;
        if ($totalAllocated > $payment->amount) {
            throw new InvalidAccountingTransactionException(
                "Total allocated amount ({$totalAllocated}) exceeds payment amount ({$payment->amount})"
            );
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
                'supplier_name' => $invoice->supplier->name,
                'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'total_amount' => $invoice->total_amount,
                'outstanding_amount' => $outstanding,
                'days_outstanding' => $days,
            ];
        }

        return $aging;
    }

    public function updatePayment(SupplierPayment $payment, array $data): SupplierPayment
    {
        if (! $payment->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft payments can be updated');
        }

        $payment->update([
            'supplier_id' => $data['supplier_id'],
            'payment_number' => $data['payment_number'],
            'payment_date' => $data['payment_date'],
            'currency_id' => $data['currency_id'] ?? null,
            'exchange_rate' => $data['exchange_rate'] ?? 1,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'BANK_TRANSFER',
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        $payment->allocations()->delete();

        $totalAllocated = 0;
        foreach ($data['allocations'] ?? [] as $allocation) {
            $invoice = SupplierInvoice::where('id', $allocation['invoice_id'])
                ->where('supplier_id', $data['supplier_id'])
                ->firstOrFail();

            $outstanding = $invoice->outstanding_amount - $invoice->allocations()
                ->where('supplier_payment_id', '!=', $payment->id)
                ->sum('amount');

            if ($allocation['amount'] > $outstanding) {
                throw new InvalidAccountingTransactionException(
                    "Allocation amount ({$allocation['amount']}) exceeds outstanding amount ({$outstanding}) for invoice {$invoice->invoice_number}"
                );
            }

            $totalAllocated = bcadd($totalAllocated, $allocation['amount'], 4);
            $payment->allocations()->create([
                'supplier_invoice_id' => $allocation['invoice_id'],
                'amount' => $allocation['amount'],
            ]);
        }

        if (bccomp($totalAllocated, $data['amount'], 4) > 0) {
            throw new InvalidAccountingTransactionException(
                "Total allocated amount ({$totalAllocated}) exceeds payment amount ({$data['amount']})"
            );
        }

        return $payment->fresh();
    }
}
