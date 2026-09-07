<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Events\PaymentApproved;
use Modules\Finance\Services\JournalService;

class CreatePaymentAccountingEntry
{
    public function __construct(protected JournalService $journalService) {}

    public function handle(PaymentApproved $event): void
    {
        $payment = $event->payment;

        $journalData = [
            'company_id' => $payment->company_id,
            'journal_date' => $payment->payment_date,
            'description' => "Supplier Payment: {$payment->payment_number}",
            'reference_type' => \Modules\Finance\Models\SupplierPayment::class,
            'reference_id' => $payment->id,
            'lines' => [
                [
                    'account_id' => $payment->bankAccount?->gl_account_id,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'description' => "Bank/Cash - {$payment->bankAccount?->bank_name}",
                ],
                [
                    'account_id' => $payment->supplier?->payable_account_id,
                    'debit' => $payment->amount,
                    'credit' => 0,
                    'description' => "Accounts Payable - {$payment->supplier?->name}",
                ],
            ],
        ];

        $this->journalService->create($journalData);
    }
}
