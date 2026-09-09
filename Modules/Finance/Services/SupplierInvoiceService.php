<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierInvoiceLine;
use Modules\Finance\Models\Journal;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Services\AuditService;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;

class SupplierInvoiceService
{
    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService
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
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => 0,
                'outstanding_amount' => 0,
                'status' => SupplierInvoice::STATUS_DRAFT,
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

            $this->audit->logCreate('Finance', 'SupplierInvoice', $invoice->id, $invoice->toArray());

            try {
                app(\Modules\Workflow\Services\WorkflowService::class)->createInstance(
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
        if (!$invoice->isDraft() && !$invoice->isSubmitted() && !$invoice->isApproved()) {
            throw new InvalidAccountingTransactionException('Invoice cannot be posted');
        }

        if ($invoice->lines->isEmpty()) {
            throw new InvalidAccountingTransactionException('Invoice must have at least one line item to post.');
        }

        return DB::transaction(function () use ($invoice) {
            $supplier = $invoice->supplier;
            $company = $invoice->company;

            $journalLines = [];

            foreach ($invoice->lines as $line) {
                $journalLines[] = [
                    'account_id' => $line->account_id,
                    'description' => $line->description,
                    'debit' => $line->total_amount,
                    'credit' => 0,
                ];

                if ($line->tax_id && $line->tax_amount > 0) {
                    $tax = $line->tax;
                    if ($tax && $tax->input_account_id) {
                        $journalLines[] = [
                            'account_id' => $tax->input_account_id,
                            'description' => "Input Tax on {$line->description}",
                            'debit' => $line->tax_amount,
                            'credit' => 0,
                        ];
                    }
                }
            }

            $journalLines[] = [
                'account_id' => $supplier->payable_account_id ?? $this->getDefaultPayableAccount($company->id),
                'description' => "Payable to {$supplier->name}",
                'debit' => 0,
                'credit' => $invoice->total_amount,
            ];

            $journal = $this->journalService->create([
                'company_id' => $invoice->company_id,
                'journal_date' => $invoice->invoice_date->toDateString(),
                'reference_type' => 'supplier_invoice',
                'reference_id' => $invoice->id,
                'description' => "Supplier Invoice #{$invoice->invoice_number}",
                'currency_id' => $invoice->currency_id,
                'exchange_rate' => $invoice->exchange_rate,
                'lines' => $journalLines,
            ]);

            $this->journalService->submit($journal);
            $this->journalService->approve($journal);
            $this->journalService->post($journal);

            $invoice->update([
                'status' => SupplierInvoice::STATUS_POSTED,
                'journal_id' => $journal->id,
            ]);

            $this->audit->logCustom('Finance', 'SupplierInvoice', $invoice->id, 'POST', [
                'journal_id' => $journal->id,
            ]);

            try {
                app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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
        if (!$invoice->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft invoices can be submitted');
        }

        $invoice->update(['status' => SupplierInvoice::STATUS_SUBMITTED]);

        $this->audit->logCustom('Finance', 'SupplierInvoice', $invoice->id, 'SUBMIT', [
            'previous_status' => SupplierInvoice::STATUS_DRAFT,
        ]);

        try {
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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
        if (!$invoice->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted invoices can be approved');
        }

        $invoice->update(['status' => SupplierInvoice::STATUS_APPROVED]);

        $this->audit->logCustom('Finance', 'SupplierInvoice', $invoice->id, 'APPROVE', [
            'previous_status' => SupplierInvoice::STATUS_SUBMITTED,
        ]);

        try {
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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
        if (!$invoice->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted invoices can be rejected');
        }

        $invoice->update(['status' => SupplierInvoice::STATUS_REJECTED]);

        $this->audit->logCustom('Finance', 'SupplierInvoice', $invoice->id, 'REJECT', [
            'previous_status' => SupplierInvoice::STATUS_SUBMITTED,
            'reason' => $reason,
        ]);

        try {
            app(\Modules\Workflow\Services\WorkflowService::class)->transitionInstance(
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

    protected function getDefaultPayableAccount(int $companyId): int
    {
        $account = \Modules\Finance\Models\Account::where('company_id', $companyId)
            ->where('account_code', 'like', '2100%')
            ->where('is_postable', true)
            ->first();

        return $account?->id ?? throw new \Exception('No payable account found');
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
}
