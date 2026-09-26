<?php

namespace Modules\Finance\Services\Import;

use Illuminate\Support\Collection;
use Modules\Core\Models\Currency;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\CashFlowCategory;
use Modules\Finance\Models\Account;
use Modules\Finance\Services\ChartOfAccountsService;

/**
 * Chart of accounts. Accounts are only created, never changed, so an import cannot alter an account that
 * already carries postings. A parent may be an existing account or appear anywhere in the same file.
 */
class AccountImporter implements Importer
{
    public const TYPES = ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'];

    public function __construct(protected ChartOfAccountsService $chartOfAccounts) {}

    public function label(): string
    {
        return __('Chart of accounts');
    }

    public function columns(): array
    {
        return [
            'account_code' => [true, __('Unique code, e.g. 1100')],
            'account_name' => [true, __('Name')],
            'account_type' => [true, 'ASSET, LIABILITY, EQUITY, REVENUE or EXPENSE'],
            'parent_code' => [false, __('Code of the heading account it sits under')],
            'cash_flow_category' => [false, 'cash, operating, investing or financing'],
            'is_control_account' => [false, __('yes for receivable/payable control accounts')],
            'currency_code' => [false, __('ISO 4217 code if the account is kept in one currency')],
            'description' => [false, __('Notes')],
        ];
    }

    public function example(): array
    {
        return [
            ['account_code' => '1000', 'account_name' => 'Current assets', 'account_type' => 'ASSET', 'parent_code' => '', 'cash_flow_category' => '', 'is_control_account' => '', 'currency_code' => '', 'description' => ''],
            ['account_code' => '1100', 'account_name' => 'Main bank account', 'account_type' => 'ASSET', 'parent_code' => '1000', 'cash_flow_category' => 'cash', 'is_control_account' => 'no', 'currency_code' => '', 'description' => ''],
            ['account_code' => '1200', 'account_name' => 'Trade receivables', 'account_type' => 'ASSET', 'parent_code' => '1000', 'cash_flow_category' => 'operating', 'is_control_account' => 'yes', 'currency_code' => '', 'description' => ''],
        ];
    }

    public function validate(array $rows, array $options): array
    {
        $existing = Account::pluck('account_type', 'account_code');
        $fileTypes = collect($rows)->mapWithKeys(fn (array $row) => [$row['data']['account_code'] ?? '' => strtoupper($row['data']['account_type'] ?? '')]);
        $seen = [];
        $currencies = Currency::pluck('id', 'code');

        $checked = array_map(function (array $row) use ($existing, $fileTypes, &$seen, $currencies) {
            $data = $row['data'];
            $code = $data['account_code'] ?? '';
            $type = strtoupper($data['account_type'] ?? '');
            $parent = $data['parent_code'] ?? '';
            $parentType = $parent !== '' ? ($existing->get($parent) ?? $fileTypes->get($parent)) : null;
            $category = strtolower($data['cash_flow_category'] ?? '');
            $currency = strtoupper($data['currency_code'] ?? '');

            $errors = array_keys(array_filter([
                __('Account code is missing.') => $code === '',
                __('Account name is missing.') => ($data['account_name'] ?? '') === '',
                __('Account type must be one of :types.', ['types' => implode(', ', self::TYPES)]) => ! in_array($type, self::TYPES, true),
                __('Account :code already exists.', ['code' => $code]) => $existing->has($code),
                __('Account :code appears more than once in the file.', ['code' => $code]) => isset($seen[$code]),
                __('Parent account :code does not exist.', ['code' => $parent]) => $parent !== '' && $parentType === null,
                __('Parent account :code is a different type.', ['code' => $parent]) => $parentType !== null && $parentType !== $type,
                __('An account cannot be its own parent.') => $parent !== '' && $parent === $code,
                __('Cash flow category must be cash, operating, investing or financing.') => $category !== '' && CashFlowCategory::tryFrom($category) === null,
                __('Currency :code is not set up.', ['code' => $currency]) => $currency !== '' && ! $currencies->has($currency),
                __('is_control_account must be yes or no.') => ! in_array(strtolower($data['is_control_account'] ?? ''), ['', 'yes', 'no', '1', '0', 'true', 'false'], true),
            ]));
            $seen[$code] = true;

            return [...$row, 'action' => 'create', 'errors' => $errors];
        }, $rows);

        return ['rows' => $checked, 'errors' => []];
    }

    public function import(array $rows, array $options): string
    {
        $companyId = (int) app(CompanyContextService::class)->getActiveCompanyId();
        $currencies = Currency::pluck('id', 'code');
        $parentCodes = collect($rows)->pluck('data.parent_code')->filter()->flip();
        $created = [];

        foreach ($this->parentsFirst(collect($rows)) as $row) {
            $data = $row['data'];
            $parentCode = $data['parent_code'] ?? '';

            $created[$data['account_code']] = $this->chartOfAccounts->create([
                'company_id' => $companyId,
                'parent_id' => $parentCode !== '' ? ($created[$parentCode]->id ?? Account::where('account_code', $parentCode)->value('id')) : null,
                'account_code' => $data['account_code'],
                'account_name' => $data['account_name'],
                'account_type' => strtoupper($data['account_type']),
                'is_group' => $parentCodes->has($data['account_code']),
                'is_postable' => ! $parentCodes->has($data['account_code']),
                'is_control_account' => in_array(strtolower($data['is_control_account'] ?? ''), ['yes', '1', 'true'], true),
                'cash_flow_category' => ($data['cash_flow_category'] ?? '') !== '' ? CashFlowCategory::from(strtolower($data['cash_flow_category'])) : null,
                'currency_id' => ($data['currency_code'] ?? '') !== '' ? $currencies->get(strtoupper($data['currency_code'])) : null,
                'description' => ($data['description'] ?? '') ?: null,
            ]);
        }

        return trans_choice(':count account created.|:count accounts created.', count($created));
    }

    /**
     * Order rows so that every parent defined in the file is created before its children.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function parentsFirst(Collection $rows): array
    {
        $pending = $rows->keyBy('data.account_code');
        $ordered = [];

        while ($pending->isNotEmpty()) {
            $ready = $pending->filter(fn (array $row) => ($row['data']['parent_code'] ?? '') === '' || ! $pending->has($row['data']['parent_code']));
            $ready = $ready->isEmpty() ? $pending->take(1) : $ready;

            foreach ($ready as $code => $row) {
                $ordered[] = $row;
                $pending->forget($code);
            }
        }

        return $ordered;
    }
}
