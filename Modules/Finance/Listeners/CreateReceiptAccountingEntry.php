<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Events\ReceiptApproved;
use Modules\Finance\Services\JournalService;

class CreateReceiptAccountingEntry
{
    public function __construct(protected JournalService $journalService) {}

    public function handle(ReceiptApproved $event): void
    {
        $receipt = $event->receipt;

        $journalData = [
            'company_id' => $receipt->company_id,
            'journal_date' => $receipt->receipt_date,
            'description' => "Customer Receipt: {$receipt->receipt_number}",
            'reference_type' => \Modules\Finance\Models\CustomerReceipt::class,
            'reference_id' => $receipt->id,
            'lines' => [
                [
                    'account_id' => $receipt->bankAccount?->gl_account_id,
                    'debit' => $receipt->amount,
                    'credit' => 0,
                    'description' => "Bank/Cash - {$receipt->bankAccount?->bank_name}",
                ],
                [
                    'account_id' => $receipt->customer?->receivable_account_id,
                    'debit' => 0,
                    'credit' => $receipt->amount,
                    'description' => "Accounts Receivable - {$receipt->customer?->name}",
                ],
            ],
        ];

        $this->journalService->create($journalData);
    }
}
