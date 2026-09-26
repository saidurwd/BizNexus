<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InactiveAccountException;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Exceptions\NonPostableAccountException;
use Modules\Core\Services\AuditService;
use Modules\Finance\Models\Account;

class ChartOfAccountsService
{
    public function __construct(
        protected AuditService $audit
    ) {}

    public function create(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            // A child makes its parent a group (heading) account, which cannot carry postings of its own.
            if (isset($data['parent_id'])) {
                $parent = Account::findOrFail($data['parent_id']);

                if (! $parent->is_group) {
                    if ($parent->journalLines()->exists()) {
                        throw new InvalidAccountingTransactionException("Account {$parent->account_code} has postings and cannot become a parent account.");
                    }

                    $parent->update(['is_group' => true, 'is_postable' => false]);
                }

                $data['level'] = $parent->level + 1;
            } else {
                $data['level'] = 1;
            }

            $account = Account::create([
                'company_id' => $data['company_id'],
                'parent_id' => $data['parent_id'] ?? null,
                'account_code' => $data['account_code'],
                'account_name' => $data['account_name'],
                'account_type' => $data['account_type'],
                'account_category_id' => $data['account_category_id'] ?? null,
                'normal_balance' => $data['normal_balance'] ?? $this->getDefaultNormalBalance($data['account_type']),
                'level' => $data['level'],
                'is_group' => $data['is_group'] ?? false,
                'is_postable' => $data['is_postable'] ?? ! ($data['is_group'] ?? false),
                'is_control_account' => $data['is_control_account'] ?? false,
                'cash_flow_category' => $data['cash_flow_category'] ?? null,
                'currency_id' => $data['currency_id'] ?? null,
                'status' => $data['status'] ?? 'active',
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->audit->logCreate('Finance', 'Account', $account->id, $account->toArray());

            return $account;
        });
    }

    public function update(Account $account, array $data): Account
    {
        return DB::transaction(function () use ($account, $data) {
            $oldData = $account->toArray();

            $account->update($data);
            $account->save();

            $this->audit->logUpdate('Finance', 'Account', $account->id, $oldData, $account->toArray());

            return $account->fresh();
        });
    }

    public function delete(Account $account): void
    {
        if ($account->journalLines()->exists()) {
            throw new \Exception('Cannot delete account with existing journal lines');
        }

        if ($account->children()->exists()) {
            throw new \Exception('Cannot delete account with child accounts');
        }

        $accountData = $account->toArray();
        $account->delete();

        $this->audit->logDelete('Finance', 'Account', $account->id, $accountData);
    }

    public function getAccountTree(int $companyId): array
    {
        $accounts = Account::where('company_id', $companyId)
            ->orderBy('account_code')
            ->get();

        return $this->buildTree($accounts);
    }

    protected function buildTree($accounts, ?int $parentId = null): array
    {
        $tree = [];

        foreach ($accounts->where('parent_id', $parentId) as $account) {
            $node = [
                'id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'account_category_id' => $account->account_category_id,
                'account_category' => $account->category?->name,
                'normal_balance' => $account->normal_balance,
                'is_group' => $account->is_group,
                'is_postable' => $account->is_postable,
                'status' => $account->status,
                'balance' => $account->balance,
            ];

            $children = $this->buildTree($accounts, $account->id);
            if (! empty($children)) {
                $node['children'] = $children;
            }

            $tree[] = $node;
        }

        return $tree;
    }

    public function getDefaultNormalBalance(string $accountType): string
    {
        return match ($accountType) {
            'ASSET', 'EXPENSE' => 'DEBIT',
            'LIABILITY', 'EQUITY', 'REVENUE' => 'CREDIT',
            default => 'DEBIT',
        };
    }

    public function validateAccountCode(int $companyId, string $code, ?int $excludeId = null): bool
    {
        $query = Account::where('company_id', $companyId)
            ->where('account_code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    public function validateAccountForPosting(Account $account): void
    {
        if (! $account->isActive()) {
            throw new InactiveAccountException($account);
        }

        if (! $account->isPostable()) {
            throw new NonPostableAccountException($account);
        }
    }
}
