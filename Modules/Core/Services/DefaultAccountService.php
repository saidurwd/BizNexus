<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Config;
use Modules\Finance\Models\Account;

class DefaultAccountService
{
    public function getPayableAccount(int $companyId): int
    {
        return $this->resolveAccount($companyId, 'finance.accounts.payable.pattern', 'No payable account found');
    }

    public function getCashAccount(int $companyId): int
    {
        return $this->resolveAccount($companyId, 'finance.accounts.cash.pattern', 'No cash account found');
    }

    public function getBankAccount(int $companyId): int
    {
        return $this->resolveAccount($companyId, 'finance.accounts.bank.pattern', 'No bank account found');
    }

    public function getReceivableAccount(int $companyId): int
    {
        return $this->resolveAccount($companyId, 'finance.accounts.receivable.pattern', 'No receivable account found');
    }

    protected function resolveAccount(int $companyId, string $configKey, string $exceptionMessage): int
    {
        $pattern = Config::get($configKey);

        $account = Account::where('company_id', $companyId)
            ->where('account_code', 'like', $pattern)
            ->where('is_postable', true)
            ->first();

        return $account?->id ?? throw new \Exception($exceptionMessage);
    }
}
