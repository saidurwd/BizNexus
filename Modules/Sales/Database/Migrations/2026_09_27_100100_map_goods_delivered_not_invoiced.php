<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give companies on the standard chart of accounts a Goods Delivered Not Invoiced account (1150, under
 * Current Assets) and map it, so deliveries can post. Other companies set it up under Account Determination.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            if (DB::table('account_mappings')->where('company_id', $companyId)->where('purpose', 'goods_delivered_not_invoiced')->exists()) {
                continue;
            }

            $account = DB::table('accounts')->where('company_id', $companyId)->where('account_code', '1150')->whereNull('deleted_at')->first();

            if (! $account) {
                $parent = DB::table('accounts')->where('company_id', $companyId)->where('account_code', '1100')->where('is_group', true)->where('account_type', 'ASSET')->whereNull('deleted_at')->first();

                if (! $parent) {
                    continue;
                }

                $accountId = DB::table('accounts')->insertGetId([
                    'company_id' => $companyId, 'parent_id' => $parent->id, 'account_code' => '1150', 'account_name' => 'Goods Delivered Not Invoiced',
                    'account_type' => 'ASSET', 'normal_balance' => 'DEBIT', 'level' => $parent->level + 1, 'is_group' => false, 'is_postable' => true,
                    'is_current' => true, 'cash_flow_category' => 'operating', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
                ]);
            } elseif ($account->account_type === 'ASSET' && $account->is_postable && ! $account->is_group) {
                $accountId = $account->id;
            } else {
                continue;
            }

            DB::table('account_mappings')->insert(['company_id' => $companyId, 'purpose' => 'goods_delivered_not_invoiced', 'account_id' => $accountId, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('account_mappings')->where('purpose', 'goods_delivered_not_invoiced')->delete();
    }
};
