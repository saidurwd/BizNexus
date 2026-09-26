<?php

namespace Modules\Finance\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Support\Money;
use Modules\Finance\Events\PaymentApproved;
use Modules\Finance\Models\PaymentAllocation;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierPayment;
use Modules\Finance\Models\Tax;
use Modules\Finance\Models\TaxTransaction;
use Modules\Finance\Services\Concerns\AgesOpenInvoices;
use Modules\Finance\Services\Concerns\EnforcesSegregationOfDuties;
use Modules\Workflow\Services\WorkflowService;

class PaymentService
{
    use AgesOpenInvoices;
    use EnforcesSegregationOfDuties;

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
                'exchange_rate' => $data['exchange_rate'] ?? app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $data['currency_id'] ?? null, $data['payment_date']),
                'amount' => $data['amount'],
                ...$this->withholding($data),
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
        return DB::transaction(function () use ($payment) {
            $payment = SupplierPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! $payment->isApproved()) {
                throw new InvalidAccountingTransactionException('Only approved payments can be posted');
            }

            $supplier = $payment->supplier;
            $company = $payment->company;

            $journalLines = [];
            $payableAccountId = $supplier->payable_account_id ?? $this->defaultAccounts->getPayableAccount($company->id);

            $journalLines[] = [
                'account_id' => $payableAccountId,
                'description' => "Payment to {$supplier->name}",
                'debit' => $payment->amount,
                'credit' => 0,
            ];

            $currency = $payment->currency?->code ?? $company->baseCurrency?->code ?? 'XXX';
            $withheld = Money::of($payment->withholding_amount ?? 0, $currency);
            $netPaid = Money::of($payment->amount, $currency)->minus($withheld)->amount;

            if ($payment->bank_account_id) {
                $bankAccount = $payment->bankAccount;
                $journalLines[] = [
                    'account_id' => $bankAccount->gl_account_id,
                    'description' => "Bank Payment #{$payment->payment_number}",
                    'debit' => 0,
                    'credit' => $netPaid,
                ];
            } else {
                $journalLines[] = [
                    'account_id' => $this->defaultAccounts->getCashAccount($company->id),
                    'description' => "Cash Payment #{$payment->payment_number}",
                    'debit' => 0,
                    'credit' => $netPaid,
                ];
            }

            if ($withheld->isPositive()) {
                $journalLines[] = [
                    'account_id' => app(DocumentTaxService::class)->requireAccount($payment->withholdingTax->output_account_id, $payment->withholdingTax->tax_code, 'output'),
                    'description' => "Withholding {$payment->withholdingTax->tax_code} on payment #{$payment->payment_number}",
                    'debit' => 0,
                    'credit' => $withheld->amount,
                ];
            }

            $journalLines = [...$journalLines, ...app(RealizedExchangeDifferenceService::class)->settlementLines(
                $company,
                $payment->allocations()->with('invoice')->get(),
                $payment->currency_id,
                (string) $payment->exchange_rate,
                $payableAccountId,
                RealizedExchangeDifferenceService::SIDE_PAYABLE,
            )];

            $journal = $this->journalService->postFromSource([
                'company_id' => $payment->company_id,
                'journal_date' => $payment->payment_date->toDateString(),
                'reference_type' => 'supplier_payment',
                'reference_id' => $payment->id,
                'description' => "Supplier Payment #{$payment->payment_number}",
                'currency_id' => $payment->currency_id,
                'exchange_rate' => $payment->exchange_rate,
                'lines' => $journalLines,
            ]);

            if ($withheld->isPositive()) {
                TaxTransaction::create([
                    'company_id' => $payment->company_id,
                    'tax_id' => $payment->withholding_tax_id,
                    'transaction_type' => 'WITHHOLDING',
                    'payment_id' => $payment->id,
                    'taxable_amount' => $payment->amount,
                    'tax_amount' => $withheld->amount,
                    'exchange_rate' => $payment->exchange_rate ?? 1,
                    'currency_code' => $currency,
                    'tax_date' => $payment->payment_date,
                    'reference_number' => $payment->payment_number,
                ]);
            }

            $payment->update([
                'status' => SupplierPayment::STATUS_POSTED,
                'journal_id' => $journal->id,
            ]);

            foreach ($payment->allocations as $allocation) {
                $allocation->invoice->calculateOutstanding();
                $allocation->invoice->save();
            }

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

        $this->ensureApproverIsNotCreator($payment, 'payment');

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

    /**
     * Open supplier invoices aged by days past due, in the functional currency, with a row per supplier.
     */
    public function getAPAging(int $companyId, ?int $supplierId = null, ?CarbonInterface $asOf = null): array
    {
        $invoices = SupplierInvoice::with(['supplier', 'currency'])
            ->where('company_id', $companyId)
            ->pending()
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->get();

        return $this->ageOpenInvoices($invoices, 'supplier', $asOf);
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
            'exchange_rate' => $data['exchange_rate'] ?? app(ExchangeRateService::class)->rateForDocument(app(CompanyContextService::class)->getActiveCompanyId(), $data['currency_id'] ?? null, $data['payment_date']),
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

    /**
     * Tax withheld at source from a supplier payment: the gross amount settles the invoices, the supplier
     * receives the net, and the withheld part is owed to the tax authority.
     *
     * @return array{withholding_tax_id: ?int, withholding_amount: string}
     */
    protected function withholding(array $data): array
    {
        if (empty($data['withholding_tax_id'])) {
            return ['withholding_tax_id' => null, 'withholding_amount' => '0'];
        }

        $tax = Tax::findOrFail($data['withholding_tax_id']);

        if ($tax->tax_type !== Tax::TYPE_WITHHOLDING_TAX) {
            throw new InvalidAccountingTransactionException("{$tax->tax_code} is not a withholding tax.");
        }

        $currency = Currency::find($data['currency_id'] ?? null)?->code
            ?? Company::find($data['company_id'])?->baseCurrency?->code
            ?? 'XXX';

        $withheld = Money::of($data['amount'], $currency)
            ->multipliedBy(bcdiv($tax->rateOn(Carbon::parse($data['payment_date'])), '100', 12));

        return ['withholding_tax_id' => $tax->id, 'withholding_amount' => $withheld->amount];
    }
}
