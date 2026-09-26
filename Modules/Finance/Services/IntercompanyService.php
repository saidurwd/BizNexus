<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;
use Modules\Core\Models\Company;
use Modules\Core\Scopes\CompanyScope;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\IntercompanyTransaction;

/**
 * Intercompany charges: the source company books a receivable due from the target against income, the
 * target books the cost against a payable due to the source, atomically. Every line carries the other
 * company as trading partner so consolidation can eliminate the balances and the income/expense.
 */
class IntercompanyService
{
    public const PERMISSION = 'finance.intercompany.create';

    public function __construct(
        protected JournalService $journals,
        protected CompanyContextService $companyContext,
        protected DefaultAccountService $defaultAccounts,
        protected PermissionService $permissions,
    ) {}

    /**
     * @param  array{transaction_date: string, currency_id: int, amount: string|int|float, description: string, source_account_id: int, target_account_code: string}  $data
     */
    public function charge(Company $source, Company $target, array $data): IntercompanyTransaction
    {
        if ($source->is($target) || (int) $source->tenant_id !== (int) $target->tenant_id) {
            throw new InvalidAccountingTransactionException('Intercompany charges are between two different companies of the same organisation.');
        }

        $permittedCompanyIds = $this->permissions->companyIdsWithPermission(self::PERMISSION, Auth::id());

        foreach ([$source, $target] as $company) {
            if (! $permittedCompanyIds->contains($company->id)) {
                throw new UnauthorizedCompanyAccessException($company->id, Auth::id());
            }
        }

        $targetAccountId = Account::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $target->id)
            ->where('account_code', $data['target_account_code'])
            ->where('is_postable', true)
            ->value('id') ?? throw new InvalidAccountingTransactionException("Account {$data['target_account_code']} does not exist in {$target->code}.");

        return DB::transaction(function () use ($source, $target, $data, $targetAccountId) {
            $common = [
                'journal_date' => $data['transaction_date'],
                'currency_id' => $data['currency_id'],
                'reference_type' => 'intercompany',
                'description' => $data['description'],
            ];

            $sourceJournal = $this->companyContext->runAs($source->id, fn () => $this->journals->postFromSource([...$common, 'lines' => [
                ['account_id' => $this->defaultAccounts->forPurpose($source->id, AccountPurpose::IntercompanyReceivable), 'debit' => $data['amount'], 'counterparty_company_id' => $target->id, 'description' => "Due from {$target->code}"],
                ['account_id' => $data['source_account_id'], 'credit' => $data['amount'], 'counterparty_company_id' => $target->id, 'description' => $data['description']],
            ]]));

            $targetJournal = $this->companyContext->runAs($target->id, fn () => $this->journals->postFromSource([...$common, 'lines' => [
                ['account_id' => $targetAccountId, 'debit' => $data['amount'], 'counterparty_company_id' => $source->id, 'description' => $data['description']],
                ['account_id' => $this->defaultAccounts->forPurpose($target->id, AccountPurpose::IntercompanyPayable), 'credit' => $data['amount'], 'counterparty_company_id' => $source->id, 'description' => "Due to {$source->code}"],
            ]]));

            return IntercompanyTransaction::create([
                'tenant_id' => $source->tenant_id,
                'source_company_id' => $source->id,
                'target_company_id' => $target->id,
                'transaction_date' => $data['transaction_date'],
                'currency_id' => $data['currency_id'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'source_journal_id' => $sourceJournal->id,
                'target_journal_id' => $targetJournal->id,
                'created_by' => Auth::id(),
            ]);
        });
    }
}
