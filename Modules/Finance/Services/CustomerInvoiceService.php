<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\CustomerInvoiceLine;
use Modules\Finance\Models\Journal;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Services\AuditService;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;

class CustomerInvoiceService
{
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
                'due_date' => $data['due_date'],
                'currency_id' => $data['currency_id'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => 0,
                'outstanding_amount' => 0,
                'status' => CustomerInvoice::STATUS_DRAFT,
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $totalSubtotal = 0;
            $totalTax = 0;

            foreach ($data['lines'] ?? [] as $lineData) {
                $line = $invoice->lines()->create([
                    'account_id' => $lineData['account_id'],
                    'description' => $lineData['description'],
                    'quantity' => $lineData['quantity'] ?? 1,
                    'unit_price' => $lineData['unit_price'] ?? 0,
                    'subtotal' => 0,
                    'tax_id' => $lineData['tax_id'] ?? null,
                    'tax_amount' => 0,
                    'discount_amount' => $lineData['discount_amount'] ?? 0,
                    'total_amount' => 0,
                ]);

                $line->calculateTotals();
                $line->save();

                $totalSubtotal = bcadd($totalSubtotal, $line->subtotal, 4);
                $totalTax = bcadd($totalTax, $line->tax_amount ?? 0, 4);
            }

            $invoice->subtotal = $totalSubtotal;
            $invoice->tax_amount = $totalTax;
            $invoice->total_amount = bcadd(bcadd($totalSubtotal, $totalTax, 4), $invoice->discount_amount, 4);
            $invoice->outstanding_amount = $invoice->total_amount;
            $invoice->save();

            $this->audit->logCreate('Finance', 'CustomerInvoice', $invoice->id, $invoice->toArray());

            try {
                app(\Modules\Workflow\Services\WorkflowService::class)->createInstance(
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
        if (!$invoice->isApproved()) {
            throw new InvalidAccountingTransactionException('Only approved invoices can be posted');
        }

        if ($invoice->lines->isEmpty()) {
            throw new InvalidAccountingTransactionException('Invoice must have at least one line item to post.');
        }

        return DB::transaction(function () use ($invoice) {
            $customer = $invoice->customer;
            $company = $invoice->company;

            $journalLines = [];

            foreach ($invoice->lines as $line) {
                $journalLines[] = [
                    'account_id' => $customer->receivable_account_id ?? $this->getDefaultReceivableAccount($company->id),
                    'description' => $line->description,
                    'debit' => $line->total_amount,
                    'credit' => 0,
                ];

                $journalLines[] = [
                    'account_id' => $line->account_id,
                    'description' => $line->description,
                    'debit' => 0,
                    'credit' => $line->subtotal,
                ];

                if ($line->tax_id && $line->tax_amount > 0) {
                    $tax = $line->tax;
                    if ($tax && $tax->output_account_id) {
                        $journalLines[] = [
                            'account_id' => $tax->output_account_id,
                            'description' => "Output Tax on {$line->description}",
                            'debit' => 0,
                            'credit' => $line->tax_amount,
                        ];
                    }
                }
            }

            $journal = $this->journalService->create([
                'company_id' => $invoice->company_id,
                'journal_date' => $invoice->invoice_date->toDateString(),
                'reference_type' => 'customer_invoice',
                'reference_id' => $invoice->id,
                'description' => "Customer Invoice #{$invoice->invoice_number}",
                'currency_id' => $invoice->currency_id,
                'exchange_rate' => $invoice->exchange_rate,
                'lines' => $journalLines,
            ]);

            $this->journalService->submit($journal);
            $this->journalService->approve($journal);
            $this->journalService->post($journal);

            $invoice->update([
                'status' => CustomerInvoice::STATUS_POSTED,
                'journal_id' => $journal->id,
            ]);

            $this->audit->logCustom('Finance', 'CustomerInvoice', $invoice->id, 'POST', [
                'journal_id' => $journal->id,
            ]);

            try {
                app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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
        if (!$invoice->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft invoices can be submitted');
        }

        $invoice->update(['status' => CustomerInvoice::STATUS_SUBMITTED]);

        $this->audit->logCustom('Finance', 'CustomerInvoice', $invoice->id, 'SUBMIT', [
            'previous_status' => CustomerInvoice::STATUS_DRAFT,
        ]);

        try {
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
                'customer_invoice',
                $invoice->id,
                CustomerInvoice::STATUS_SUBMITTED
            );
        } catch (\Throwable $e) {
            // Workflow definitions may not be seeded yet
        }

        return $invoice->fresh();
    }

    public function approveInvoice(CustomerInvoice $invoice): CustomerInvoice
    {
        if (!$invoice->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted invoices can be approved');
        }

        $invoice->update(['status' => CustomerInvoice::STATUS_APPROVED]);

        $this->audit->logCustom('Finance', 'CustomerInvoice', $invoice->id, 'APPROVE', [
            'previous_status' => CustomerInvoice::STATUS_SUBMITTED,
        ]);

        event(new \Modules\Finance\Events\CustomerInvoiceApproved($invoice));

        try {
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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
        if (!$invoice->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted invoices can be rejected');
        }

        $invoice->update(['status' => CustomerInvoice::STATUS_REJECTED]);

        $this->audit->logCustom('Finance', 'CustomerInvoice', $invoice->id, 'REJECT', [
            'previous_status' => CustomerInvoice::STATUS_SUBMITTED,
            'reason' => $reason,
        ]);

        try {
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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
        $account = \Modules\Finance\Models\Account::where('company_id', $companyId)
            ->where('account_code', 'like', '1100%')
            ->where('is_postable', true)
            ->first();

        return $account?->id ?? throw new \Exception('No receivable account found');
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
        if (!$invoice->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft invoices can be updated');
        }

        $invoice->update([
            'customer_id' => $data['customer_id'],
            'invoice_number' => $data['invoice_number'],
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'],
            'currency_id' => $data['currency_id'] ?? null,
            'exchange_rate' => $data['exchange_rate'] ?? 1,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'description' => $data['description'] ?? null,
        ]);

        $invoice->lines()->delete();

        $totalSubtotal = 0;
        $totalTax = 0;

        foreach ($data['lines'] ?? [] as $lineData) {
            $line = $invoice->lines()->create([
                'account_id' => $lineData['account_id'],
                'description' => $lineData['description'],
                'quantity' => $lineData['quantity'] ?? 1,
                'unit_price' => $lineData['unit_price'] ?? 0,
                'subtotal' => 0,
                'tax_id' => $lineData['tax_id'] ?? null,
                'tax_amount' => 0,
                'discount_amount' => $lineData['discount_amount'] ?? 0,
                'total_amount' => 0,
            ]);

            $line->calculateTotals();
            $line->save();

            $totalSubtotal = bcadd($totalSubtotal, $line->subtotal, 4);
            $totalTax = bcadd($totalTax, $line->tax_amount ?? 0, 4);
        }

        $invoice->subtotal = $totalSubtotal;
        $invoice->tax_amount = $totalTax;
        $invoice->total_amount = bcadd(bcadd($totalSubtotal, $totalTax, 4), $invoice->discount_amount, 4);
        $invoice->outstanding_amount = $invoice->total_amount;
        $invoice->save();

        return $invoice->fresh();
    }
}
