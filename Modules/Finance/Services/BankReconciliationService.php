<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankReconciliation;
use Modules\Finance\Models\BankTransaction;
use Modules\Core\Services\AuditService;
use Carbon\Carbon;

class BankReconciliationService
{
    public function reconcile(int $bankAccountId, Carbon $statementDate, float $statementBalance): BankReconciliation
    {
        $account = BankAccount::findOrFail($bankAccountId);

        $bookBalance = $this->calculateBookBalance($bankAccountId, $statementDate);

        $reconciliation = new BankReconciliation([
            'bank_account_id' => $bankAccountId,
            'statement_date' => $statementDate,
            'statement_balance' => $statementBalance,
            'book_balance' => $bookBalance,
            'difference' => $statementBalance - $bookBalance,
            'status' => abs($statementBalance - $bookBalance) < 0.01 ? 'RECONCILED' : 'PENDING',
            'reconciled_by' => auth()->id(),
            'reconciled_at' => abs($statementBalance - $bookBalance) < 0.01 ? now() : null,
        ]);

        $reconciliation->save();
            $this->audit->logCreate('Finance', 'BankReconciliation', $reconciliation->id, $reconciliation->toArray());

        return $reconciliation;
    }

    public function calculateBookBalance(int $bankAccountId, Carbon $asOfDate): float
    {
        $openingBalance = BankAccount::findOrFail($bankAccountId)->opening_balance;

        $transactions = BankTransaction::where('bank_account_id', $bankAccountId)
            ->where('transaction_date', '<=', $asOfDate)
            ->where('status', 'COMPLETED')
            ->get();

        $balance = $openingBalance;

        foreach ($transactions as $transaction) {
            switch ($transaction->transaction_type) {
                case 'DEPOSIT':
                    $balance += $transaction->amount;
                    break;
                case 'WITHDRAWAL':
                case 'TRANSFER':
                case 'CHARGE':
                    $balance -= $transaction->amount;
                    break;
            }
        }

        return $balance;
    }

    public function getUnreconciledTransactions(int $bankAccountId, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $query = BankTransaction::where('bank_account_id', $bankAccountId)
            ->where('status', 'COMPLETED');

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        return $query->orderBy('transaction_date')->get();
    }

    public function markAsReconciled(int $reconciliationId): BankReconciliation
    {
        $reconciliation = BankReconciliation::findOrFail($reconciliationId);

        if ($reconciliation->status === 'RECONCILED') {
            return $reconciliation;
        }

        $reconciliation->update([
            'status' => 'RECONCILED',
            'reconciled_by' => auth()->id(),
            'reconciled_at' => now(),
        ]);

        return $reconciliation;
    }

    public function getReconciliationHistory(int $bankAccountId)
    {
        return BankReconciliation::where('bank_account_id', $bankAccountId)
            ->orderByDesc('statement_date')
            ->get();
    }
}
