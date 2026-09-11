<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\SupplierDebitNote;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;

class SupplierDebitNoteService
{
    public function postDebitNote(SupplierDebitNote $debitNote): SupplierDebitNote
    {
        if (!$debitNote->isApproved()) {
            throw new InvalidAccountingTransactionException('Only approved debit notes can be posted');
        }

        return DB::transaction(function () use ($debitNote) {
            $supplier = $debitNote->supplier;

            $journalLines = [];

            $journalLines[] = [
                'account_id' => $supplier->payable_account_id ?? app(\Modules\Core\Services\DefaultAccountService::class)->getPayableAccount($debitNote->company_id),
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

    public function submitDebitNote(SupplierDebitNote $debitNote): SupplierDebitNote
    {
        if (!$debitNote->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft debit notes can be submitted');
        }

        $debitNote->update(['status' => SupplierDebitNote::STATUS_SUBMITTED]);

        return $debitNote->fresh();
    }

    public function approveDebitNote(SupplierDebitNote $debitNote): SupplierDebitNote
    {
        if (!$debitNote->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted debit notes can be approved');
        }

        $debitNote->update(['status' => SupplierDebitNote::STATUS_APPROVED]);

        event(new \Modules\Finance\Events\SupplierDebitNoteApproved($debitNote));

        return $debitNote->fresh();
    }

    public function rejectDebitNote(SupplierDebitNote $debitNote, ?string $reason = null): SupplierDebitNote
    {
        if (!$debitNote->isSubmitted()) {
            throw new InvalidAccountingTransactionException('Only submitted debit notes can be rejected');
        }

        $debitNote->update(['status' => SupplierDebitNote::STATUS_REJECTED]);

        return $debitNote->fresh();
    }

    public function cancelDebitNote(SupplierDebitNote $debitNote): SupplierDebitNote
    {
        if ($debitNote->isPosted()) {
            throw new InvalidAccountingTransactionException('Posted debit notes cannot be cancelled directly');
        }

        $debitNote->update(['status' => SupplierDebitNote::STATUS_CANCELLED]);

        return $debitNote->fresh();
    }

    public function createDebitNote(array $data): SupplierDebitNote
    {
        return DB::transaction(function () use ($data) {
            $debitNote = SupplierDebitNote::create([
                'company_id' => $data['company_id'],
                'supplier_id' => $data['supplier_id'],
                'note_number' => $data['note_number'] ?? 'DN-' . strtoupper(uniqid()),
                'note_date' => $data['note_date'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'status' => SupplierDebitNote::STATUS_DRAFT,
            ]);

            return $debitNote->fresh();
        });
    }

    public function updateDebitNote(SupplierDebitNote $debitNote, array $data): SupplierDebitNote
    {
        if (!$debitNote->isDraft()) {
            throw new InvalidAccountingTransactionException('Only draft debit notes can be updated');
        }

        $debitNote->update([
            'supplier_id' => $data['supplier_id'],
            'note_number' => $data['note_number'],
            'note_date' => $data['note_date'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
        ]);

        return $debitNote->fresh();
    }
}
