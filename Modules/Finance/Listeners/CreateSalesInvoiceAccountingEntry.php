<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Events\CustomerInvoiceApproved;
use Modules\Finance\Services\JournalService;

class CreateSalesInvoiceAccountingEntry
{
    public function __construct(protected JournalService $journalService) {}

    public function handle(CustomerInvoiceApproved $event): void
    {
        $invoice = $event->invoice;

        $journalData = [
            'company_id' => $invoice->company_id,
            'journal_date' => $invoice->invoice_date,
            'description' => "Customer Invoice: {$invoice->invoice_number}",
            'reference_type' => \Modules\Finance\Models\CustomerInvoice::class,
            'reference_id' => $invoice->id,
            'lines' => [
                [
                    'account_id' => $invoice->customer?->receivable_account_id,
                    'debit' => $invoice->total_amount,
                    'credit' => 0,
                    'description' => "Accounts Receivable - {$invoice->customer?->name}",
                ],
                [
                    'account_id' => $invoice->tax?->output_account_id ?? null,
                    'debit' => 0,
                    'credit' => $invoice->tax_amount ?? 0,
                    'description' => "Output VAT - {$invoice->tax?->tax_name}",
                ],
                [
                    'account_id' => $invoice->revenue_account_id ?? null,
                    'debit' => 0,
                    'credit' => $invoice->subtotal ?? 0,
                    'description' => "Sales Revenue",
                ],
            ],
        ];

        $this->journalService->create($journalData);
    }
}
