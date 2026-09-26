<?php

namespace Modules\Assets\Support;

use Illuminate\Support\Facades\DB;

/**
 * Asset accounts and categories for a company on the standard chart of accounts: depreciation expense (5160)
 * and gain or loss on disposal (5350) are added under their groups, and three categories are created against
 * Property & Equipment (1210) and Accumulated Depreciation (1220). Companies with another chart are skipped.
 */
class StandardAssetSetup
{
    /**
     * code => [name, type, normal balance, parent group code]
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    protected const ACCOUNTS = [
        '5160' => ['Depreciation Expense', 'EXPENSE', 'DEBIT', '5100'],
        '5350' => ['Gain / Loss on Disposal of Assets', 'EXPENSE', 'DEBIT', '5300'],
    ];

    /**
     * @var list<array{code: string, name: string, depreciation_method: string, useful_life_months: int, declining_rate: string|null}>
     */
    protected const CATEGORIES = [
        ['code' => 'EQUIP', 'name' => 'Office and IT equipment', 'depreciation_method' => 'straight_line', 'useful_life_months' => 36, 'declining_rate' => null],
        ['code' => 'FURN', 'name' => 'Furniture and fittings', 'depreciation_method' => 'straight_line', 'useful_life_months' => 84, 'declining_rate' => null],
        ['code' => 'VEH', 'name' => 'Vehicles', 'depreciation_method' => 'declining_balance', 'useful_life_months' => 96, 'declining_rate' => '25'],
    ];

    public static function installFor(int $companyId): void
    {
        $now = now();
        $account = fn (string $code) => DB::table('accounts')->where('company_id', $companyId)->where('account_code', $code)->whereNull('deleted_at')->first();

        foreach (self::ACCOUNTS as $code => [$name, $type, $normalBalance, $parentCode]) {
            if ($account($code)) {
                continue;
            }

            $parent = DB::table('accounts')->where('company_id', $companyId)->where('account_code', $parentCode)->where('is_group', true)->whereNull('deleted_at')->first();

            if ($parent) {
                DB::table('accounts')->insert([
                    'company_id' => $companyId, 'parent_id' => $parent->id, 'account_code' => $code, 'account_name' => $name,
                    'account_type' => $type, 'normal_balance' => $normalBalance, 'level' => $parent->level + 1, 'is_group' => false,
                    'is_postable' => true, 'cash_flow_category' => 'operating', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        [$cost, $accumulated, $expense, $disposal] = [$account('1210'), $account('1220'), $account('5160'), $account('5350')];

        if (! $cost || ! $accumulated || ! $expense || ! $disposal || $cost->is_group || $accumulated->is_group) {
            return;
        }

        foreach (self::CATEGORIES as $category) {
            if (DB::table('asset_categories')->where('company_id', $companyId)->where('code', $category['code'])->exists()) {
                continue;
            }

            DB::table('asset_categories')->insert([
                ...$category,
                'company_id' => $companyId,
                'asset_account_id' => $cost->id,
                'accumulated_depreciation_account_id' => $accumulated->id,
                'depreciation_expense_account_id' => $expense->id,
                'disposal_account_id' => $disposal->id,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
