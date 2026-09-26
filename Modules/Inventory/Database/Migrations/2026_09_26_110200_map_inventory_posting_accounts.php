<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give companies that use the standard chart of accounts the accounts inventory postings need: map the
 * existing Inventory (1140) and Cost of Goods Sold (5210) accounts, and add Goods Received Not Invoiced (2140),
 * Purchase Price Variance (5220) and Inventory Adjustments (5230) under their standard groups. Companies with
 * another chart, or where a code is already taken or a purpose already mapped, are left for Account
 * Determination.
 */
return new class extends Migration
{
    /**
     * purpose => [code, name, type, normal balance, parent group code]
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    protected const ACCOUNTS = [
        'inventory' => ['1140', 'Inventory', 'ASSET', 'DEBIT', '1100'],
        'cost_of_goods_sold' => ['5210', 'Cost of Goods Sold', 'EXPENSE', 'DEBIT', '5200'],
        'goods_received_not_invoiced' => ['2140', 'Goods Received Not Invoiced', 'LIABILITY', 'CREDIT', '2100'],
        'purchase_price_variance' => ['5220', 'Purchase Price Variance', 'EXPENSE', 'DEBIT', '5200'],
        'inventory_adjustment' => ['5230', 'Inventory Adjustments', 'EXPENSE', 'DEBIT', '5200'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            foreach (self::ACCOUNTS as $purpose => [$code, $name, $type, $normalBalance, $parentCode]) {
                if (DB::table('account_mappings')->where('company_id', $companyId)->where('purpose', $purpose)->exists()) {
                    continue;
                }

                $account = DB::table('accounts')->where('company_id', $companyId)->where('account_code', $code)->whereNull('deleted_at')->first();

                if (! $account) {
                    $parent = DB::table('accounts')->where('company_id', $companyId)->where('account_code', $parentCode)->where('is_group', true)->whereNull('deleted_at')->first();

                    if (! $parent || $parent->account_type !== $type) {
                        continue;
                    }

                    $accountId = DB::table('accounts')->insertGetId([
                        'company_id' => $companyId, 'parent_id' => $parent->id, 'account_code' => $code, 'account_name' => $name,
                        'account_type' => $type, 'normal_balance' => $normalBalance, 'level' => $parent->level + 1,
                        'is_group' => false, 'is_postable' => true, 'is_current' => true, 'status' => 'active',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                } elseif ($account->account_type === $type && $account->is_postable && ! $account->is_group) {
                    $accountId = $account->id;
                } else {
                    continue;
                }

                DB::table('account_mappings')->insert(['company_id' => $companyId, 'purpose' => $purpose, 'account_id' => $accountId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('account_mappings')->whereIn('purpose', array_keys(self::ACCOUNTS))->delete();
    }
};
