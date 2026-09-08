<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankTransaction;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Services\AuditService;
use InvalidArgumentException;

class BankService
{
    public function deposit(int $bankAccountId, float $amount, ?string $reference = null, ?string $description = null): BankTransaction
    {
        $account = BankAccount::findOrFail($bankAccountId);

        if ($account->status !== 'active') {
            throw new InvalidArgumentException('Bank account is not active.');
        }

        return \DB::transaction(function () use ($bankAccountId, $amount, $reference, $description) {
            $transaction = BankTransaction::create([
                'bank_account_id' => $bankAccountId,
                'transaction_type' => 'DEPOSIT',
                'amount' => $amount,
                'reference' => $reference,
                'description' => $description,
                'transaction_date' => now(),
                'status' => 'COMPLETED',
                'transaction_number' => $this->generateTransactionNumber('DEPOSIT'),
            ]);

            $account->increment('current_balance', $amount);

            $this->audit->logCreate('Finance', 'BankTransaction', $transaction->id, $transaction->toArray());
            return $transaction;
        });
    }

    public function withdraw(int $bankAccountId, float $amount, ?string $reference = null, ?string $description = null): BankTransaction
    {
        $account = BankAccount::findOrFail($bankAccountId);

        if ($account->status !== 'active') {
            throw new InvalidArgumentException('Bank account is not active.');
        }

        if ($account->current_balance < $amount) {
            throw new InvalidArgumentException('Insufficient balance.');
        }

        return \DB::transaction(function () use ($bankAccountId, $amount, $reference, $description) {
            $transaction = BankTransaction::create([
                'bank_account_id' => $bankAccountId,
                'transaction_type' => 'WITHDRAWAL',
                'amount' => $amount,
                'reference' => $reference,
                'description' => $description,
                'transaction_date' => now(),
                'status' => 'COMPLETED',
                'transaction_number' => $this->generateTransactionNumber('WITHDRAWAL'),
            ]);

            $account->decrement('current_balance', $amount);

            $this->audit->logCreate('Finance', 'BankTransaction', $transaction->id, $transaction->toArray());
            return $transaction;
        });
    }

    public function transfer(int $fromAccountId, int $toAccountId, float $amount, ?string $reference = null, ?string $description = null): array
    {
        if ($fromAccountId === $toAccountId) {
            throw new InvalidArgumentException('Cannot transfer to the same account.');
        }

        $fromAccount = BankAccount::findOrFail($fromAccountId);
        $toAccount = BankAccount::findOrFail($toAccountId);

        if ($fromAccount->status !== 'active' || $toAccount->status !== 'active') {
            throw new InvalidArgumentException('One or both bank accounts are not active.');
        }

        if ($fromAccount->current_balance < $amount) {
            throw new InvalidArgumentException('Insufficient balance in source account.');
        }

        return \DB::transaction(function () use ($fromAccountId, $toAccountId, $amount, $reference, $description) {
            $outTransaction = BankTransaction::create([
                'bank_account_id' => $fromAccountId,
                'transaction_type' => 'TRANSFER',
                'amount' => $amount,
                'reference' => $reference,
                'description' => $description,
                'transaction_date' => now(),
                'status' => 'COMPLETED',
                'transaction_number' => $this->generateTransactionNumber('TRANSFER'),
            ]);

            $inTransaction = BankTransaction::create([
                'bank_account_id' => $toAccountId,
                'transaction_type' => 'DEPOSIT',
                'amount' => $amount,
                'reference' => $reference,
                'description' => $description,
                'transaction_date' => now(),
                'status' => 'COMPLETED',
                'transaction_number' => $this->generateTransactionNumber('DEPOSIT'),
            ]);

            $fromAccount->decrement('current_balance', $amount);
            $toAccount->increment('current_balance', $amount);

            return ['outgoing' => $outTransaction, 'incoming' => $inTransaction];
        });
    }

    public function getAccountBalance(int $bankAccountId): float
    {
        $account = BankAccount::findOrFail($bankAccountId);
        
        return $account->current_balance;
    }

    protected function generateTransactionNumber(string $type): string
    {
        $prefix = match ($type) {
            'DEPOSIT' => 'BRV',
            'WITHDRAWAL' => 'BPV',
            'TRANSFER' => 'BT',
            'CHARGE' => 'BC',
            default => 'BNK',
        };

        return $this->documentNumber->generateNumber($this->getCompanyId(), $prefix);
    }

    protected function getCompanyId(): int
    {
        $bankAccount = BankAccount::first();
        return $bankAccount?->company_id ?? 1;
    }
}
