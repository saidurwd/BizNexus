<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\SupplierDebitNote;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;

class SupplierDebitNoteService
{
    public function postDebitNote(SupplierDebitNote $debitNote): SupplierDebitNote
    {
        if (!$debitNote->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft debit notes can be posted');
        }

        return DB::transaction(function () use ($debitNote) {
            $supplier = $debitNote->supplier;

            $journalLines = [];

            $journalLines[] = [
                'account_id' => $supplier->payable_account_id ?? $this->getDefaultPayableAccount($debitNote->company_id),
                'description' => "Debit Note - {$debitNote->note_number}",
                'debit' => 0,
                'credit' => $debitNote->amount,
            ];

            $journal = app(\Modules\Finance\Services\JournalService::class)->create([
                'company_id' => $debitNote->company_id,
                'journal_date' => $debitNote->note_date->toDateString(),
                'reference_type' => SupplierDebitNote::class,
                'reference_id' => $debitNote->id,
                'description' => "Supplier Debit Note #{$debitNote->note_number} - {$debitNote->description}",
                'lines' => $journalLines,
            ]);

            app(\Modules\Finance\Services\JournalService::class)->submit($journal);
            app(\Modules\Finance\Services\JournalService::class)->approve($journal);
            app(\Modules\Finance\Services\JournalService::class)->post($journal);

            $debitNote->update([
                'status' => SupplierDebitNote::STATUS_POSTED,
                'journal_id' => $journal->id,
            ]);

            return $debitNote->fresh();
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
