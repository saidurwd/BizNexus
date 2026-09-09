<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\SupplierCreditNote;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;

class SupplierCreditNoteService
{
    public function postCreditNote(SupplierCreditNote $creditNote): SupplierCreditNote
    {
        if ($creditNote->status !== 'approved') {
            throw new InvalidAccountingTransactionException('Only approved credit notes can be posted');
        }

        return DB::transaction(function () use ($creditNote) {
            $supplier = $creditNote->supplier;
            $invoice = $creditNote->invoice;

            $journalLines = [];

            $journalLines[] = [
                'account_id' => $supplier->payable_account_id ?? $this->getDefaultPayableAccount($creditNote->company_id),
                'description' => "Credit Note - {$creditNote->credit_note_number}",
                'debit' => $creditNote->total_amount,
                'credit' => 0,
            ];

            if ($invoice) {
                $journalLines[] = [
                    'account_id' => $invoice->supplier?->payable_account_id ?? $this->getDefaultPayableAccount($creditNote->company_id),
                    'description' => "Credit Note applied to Invoice #{$invoice->invoice_number}",
                    'debit' => 0,
                    'credit' => $creditNote->total_amount,
                ];
            }

            $journal = app(\Modules\Finance\Services\JournalService::class)->create([
                'company_id' => $creditNote->company_id,
                'journal_date' => $creditNote->credit_note_date->toDateString(),
                'reference_type' => SupplierCreditNote::class,
                'reference_id' => $creditNote->id,
                'description' => "Supplier Credit Note #{$creditNote->credit_note_number}",
                'lines' => $journalLines,
            ]);

            app(\Modules\Finance\Services\JournalService::class)->submit($journal);
            app(\Modules\Finance\Services\JournalService::class)->approve($journal);
            app(\Modules\Finance\Services\JournalService::class)->post($journal);

            $creditNote->update([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            if ($invoice) {
                $invoice->outstanding_amount = (float) bcsub($invoice->outstanding_amount, $creditNote->total_amount, 4);
                if (bccomp($invoice->outstanding_amount, 0, 4) < 0) {
                    $invoice->outstanding_amount = 0;
                }
                $invoice->save();
            }

            return $creditNote->fresh();
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
}
