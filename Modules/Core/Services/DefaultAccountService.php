<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Config;
use Modules\Core\Scopes\CompanyScope;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Exceptions\MissingAccountMappingException;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;

/**
 * Account determination: the account a company uses for an automatic posting purpose. Explicit mappings
 * win; the legacy account-code patterns remain as a fallback for payables, receivables, cash and bank.
 */
class DefaultAccountService
{
    public function forPurpose(int $companyId, AccountPurpose $purpose): int
    {
        $mappedAccountId = AccountMapping::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('purpose', $purpose->value)
            ->value('account_id');

        if ($mappedAccountId) {
            return (int) $mappedAccountId;
        }

        $pattern = $purpose->legacyPatternKey() ? Config::get($purpose->legacyPatternKey()) : null;

        $accountId = $pattern ? Account::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('account_code', 'like', $pattern)
            ->where('is_postable', true)
            ->value('id') : null;

        return $accountId ?? throw new MissingAccountMappingException("No account is mapped for \"{$purpose->label()}\" in this company.");
    }

    public function getPayableAccount(int $companyId): int
    {
        return $this->forPurpose($companyId, AccountPurpose::Payable);
    }

    public function getCashAccount(int $companyId): int
    {
        return $this->forPurpose($companyId, AccountPurpose::Cash);
    }

    public function getBankAccount(int $companyId): int
    {
        return $this->forPurpose($companyId, AccountPurpose::Bank);
    }

    public function getReceivableAccount(int $companyId): int
    {
        return $this->forPurpose($companyId, AccountPurpose::Receivable);
    }
}
