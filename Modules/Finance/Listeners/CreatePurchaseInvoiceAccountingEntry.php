<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Events\SupplierInvoiceApproved;
use Modules\Finance\Services\JournalService;

class CreatePurchaseInvoiceAccountingEntry
{
    public function __construct(protected JournalService $journalService) {}

    public function handle(SupplierInvoiceApproved $event): void
    {
        $invoice = $event->invoice;

        $journalData = [
            'company_id' => $invoice->company_id,
            'journal_date' => $invoice->invoice_date,
            'description' => "Supplier Invoice: {$invoice->invoice_number}",
            'reference_type' => SupplierInvoice::class,
            'reference_id' => $invoice->id,
            'lines' => [
                [
                    'account_id' => $invoice->supplier?->payable_account_id,
                    'debit' => $invoice->total_amount,
                    'credit' => 0,
                    'description' => "Accounts Payable - {$invoice->supplier?->name}",
                ],
                [
                    'account_id' => $invoice->tax?->input_account_id ?? null,
                    'debit' => $invoice->tax_amount ?? 0,
                    'credit' => 0,
                    'description' => "Input VAT - {$invoice->tax?->tax_name}",
                ],
                [
                    'account_id' => $invoice->expense_account_id ?? null,
                    'debit' => $invoice->subtotal ?? 0,
                    'credit' => 0,
                    'description' => "Purchase Expense",
                ],
            ],
        ];

        $this->journalService->create($journalData);
    }
}
