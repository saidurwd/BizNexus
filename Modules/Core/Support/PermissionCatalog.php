<?php

namespace Modules\Core\Support;

use Illuminate\Support\Facades\DB;

/**
 * Granular permissions introduced when authorization moved to route middleware, and the legacy
 * permissions whose holders receive each one so that existing roles keep their effective access.
 */
class PermissionCatalog
{
    /**
     * @var array<string, array{name: string, group: string}>
     */
    public const GRANULAR_PERMISSIONS = [
        'finance.intercompany.view' => ['name' => 'View Intercompany Transactions', 'group' => 'Finance'],
        'finance.intercompany.create' => ['name' => 'Create Intercompany Transactions', 'group' => 'Finance'],
        'finance.consolidation.view' => ['name' => 'View Consolidated Reports', 'group' => 'Finance'],
        'finance.consolidation.manage' => ['name' => 'Manage Consolidation Groups', 'group' => 'Finance'],
        'core.fiscal-years.close' => ['name' => 'Close Fiscal Years', 'group' => 'Administration'],
        'core.fiscal-years.reopen' => ['name' => 'Reopen Fiscal Years', 'group' => 'Administration'],
        'finance.fx-revaluation.view' => ['name' => 'View FX Revaluations', 'group' => 'Finance'],
        'finance.fx-revaluation.run' => ['name' => 'Run FX Revaluation', 'group' => 'Finance'],
        'finance.bank-accounts.view-sensitive' => ['name' => 'View Full Bank Account Numbers', 'group' => 'Finance'],
        'core.exchange-rates.create' => ['name' => 'Create Exchange Rates', 'group' => 'Administration'],
        'core.exchange-rates.delete' => ['name' => 'Delete Exchange Rates', 'group' => 'Administration'],
        'core.exchange-rates.update' => ['name' => 'Edit Exchange Rates', 'group' => 'Administration'],
        'core.exchange-rates.view' => ['name' => 'View Exchange Rates', 'group' => 'Administration'],
        'core.fiscal-years.create' => ['name' => 'Create Fiscal Years', 'group' => 'Administration'],
        'core.periods.close' => ['name' => 'Close Periods', 'group' => 'Administration'],
        'core.periods.lock' => ['name' => 'Lock Periods', 'group' => 'Administration'],
        'core.periods.reopen' => ['name' => 'Reopen Periods', 'group' => 'Administration'],
        'core.periods.view' => ['name' => 'View Periods', 'group' => 'Administration'],
        'finance.bank-accounts.create' => ['name' => 'Create Bank Accounts', 'group' => 'Finance'],
        'finance.bank-accounts.delete' => ['name' => 'Delete Bank Accounts', 'group' => 'Finance'],
        'finance.bank-accounts.update' => ['name' => 'Edit Bank Accounts', 'group' => 'Finance'],
        'finance.bank-accounts.view' => ['name' => 'View Bank Accounts', 'group' => 'Finance'],
        'finance.bank-reconciliation.create' => ['name' => 'Create Bank Reconciliation', 'group' => 'Finance'],
        'finance.bank-reconciliation.view' => ['name' => 'View Bank Reconciliation', 'group' => 'Finance'],
        'finance.bank-transactions.create' => ['name' => 'Create Bank Transactions', 'group' => 'Finance'],
        'finance.bank-transactions.view' => ['name' => 'View Bank Transactions', 'group' => 'Finance'],
        'finance.budgets.approve' => ['name' => 'Approve Budgets', 'group' => 'Finance'],
        'finance.budgets.reject' => ['name' => 'Reject Budgets', 'group' => 'Finance'],
        'finance.budgets.submit' => ['name' => 'Submit Budgets', 'group' => 'Finance'],
        'finance.cash-accounts.create' => ['name' => 'Create Cash Accounts', 'group' => 'Finance'],
        'finance.cash-accounts.delete' => ['name' => 'Delete Cash Accounts', 'group' => 'Finance'],
        'finance.cash-accounts.update' => ['name' => 'Edit Cash Accounts', 'group' => 'Finance'],
        'finance.cash-accounts.view' => ['name' => 'View Cash Accounts', 'group' => 'Finance'],
        'finance.customer-invoices.approve' => ['name' => 'Approve Customer Invoices', 'group' => 'Finance'],
        'finance.customer-invoices.cancel' => ['name' => 'Cancel Customer Invoices', 'group' => 'Finance'],
        'finance.customer-invoices.create' => ['name' => 'Create Customer Invoices', 'group' => 'Finance'],
        'finance.customer-invoices.delete' => ['name' => 'Delete Customer Invoices', 'group' => 'Finance'],
        'finance.customer-invoices.post' => ['name' => 'Post Customer Invoices', 'group' => 'Finance'],
        'finance.customer-invoices.reject' => ['name' => 'Reject Customer Invoices', 'group' => 'Finance'],
        'finance.customer-invoices.submit' => ['name' => 'Submit Customer Invoices', 'group' => 'Finance'],
        'finance.customer-invoices.update' => ['name' => 'Edit Customer Invoices', 'group' => 'Finance'],
        'finance.customer-invoices.view' => ['name' => 'View Customer Invoices', 'group' => 'Finance'],
        'finance.dashboard.view' => ['name' => 'View Dashboard', 'group' => 'Finance'],
        'finance.journals.cancel' => ['name' => 'Cancel Journals', 'group' => 'Finance'],
        'finance.journals.reject' => ['name' => 'Reject Journals', 'group' => 'Finance'],
        'finance.journals.reverse' => ['name' => 'Reverse Journals', 'group' => 'Finance'],
        'finance.payments.reject' => ['name' => 'Reject Payments', 'group' => 'Finance'],
        'finance.receipts.reject' => ['name' => 'Reject Receipts', 'group' => 'Finance'],
        'finance.recurring-journals.create' => ['name' => 'Create Recurring Journals', 'group' => 'Finance'],
        'finance.recurring-journals.delete' => ['name' => 'Delete Recurring Journals', 'group' => 'Finance'],
        'finance.recurring-journals.update' => ['name' => 'Edit Recurring Journals', 'group' => 'Finance'],
        'finance.recurring-journals.view' => ['name' => 'View Recurring Journals', 'group' => 'Finance'],
        'finance.supplier-invoices.approve' => ['name' => 'Approve Supplier Invoices', 'group' => 'Finance'],
        'finance.supplier-invoices.cancel' => ['name' => 'Cancel Supplier Invoices', 'group' => 'Finance'],
        'finance.supplier-invoices.create' => ['name' => 'Create Supplier Invoices', 'group' => 'Finance'],
        'finance.supplier-invoices.delete' => ['name' => 'Delete Supplier Invoices', 'group' => 'Finance'],
        'finance.supplier-invoices.post' => ['name' => 'Post Supplier Invoices', 'group' => 'Finance'],
        'finance.supplier-invoices.reject' => ['name' => 'Reject Supplier Invoices', 'group' => 'Finance'],
        'finance.supplier-invoices.submit' => ['name' => 'Submit Supplier Invoices', 'group' => 'Finance'],
        'finance.supplier-invoices.update' => ['name' => 'Edit Supplier Invoices', 'group' => 'Finance'],
        'finance.supplier-invoices.view' => ['name' => 'View Supplier Invoices', 'group' => 'Finance'],
        'core.activity-logs.view' => ['name' => 'View Activity Logs', 'group' => 'Security'],
        'core.security-events.view' => ['name' => 'View Security Events', 'group' => 'Security'],
        'core.login-history.view' => ['name' => 'View Login History', 'group' => 'Security'],
        'core.system.view' => ['name' => 'View System Health, Queues and Scheduled Jobs', 'group' => 'System'],
        'core.system.manage' => ['name' => 'Retry or Discard Failed Jobs', 'group' => 'System'],
        'finance.data-import.use' => ['name' => 'Import Data from Spreadsheets', 'group' => 'Finance'],
        'finance.number-series.view' => ['name' => 'View Number Series', 'group' => 'Finance'],
        'finance.number-series.manage' => ['name' => 'Change Number Series', 'group' => 'Finance'],
        'finance.payment-terms.view' => ['name' => 'View Payment Terms', 'group' => 'Finance'],
        'finance.payment-terms.manage' => ['name' => 'Manage Payment Terms', 'group' => 'Finance'],
        'finance.supplier-credit-notes.reject' => ['name' => 'Reject Supplier Credit Notes', 'group' => 'Finance'],
        'finance.customer-credit-notes.view' => ['name' => 'View Customer Credit Notes', 'group' => 'Finance'],
        'finance.customer-credit-notes.create' => ['name' => 'Create Customer Credit Notes', 'group' => 'Finance'],
        'finance.customer-credit-notes.update' => ['name' => 'Edit Customer Credit Notes', 'group' => 'Finance'],
        'finance.customer-credit-notes.delete' => ['name' => 'Delete Customer Credit Notes', 'group' => 'Finance'],
        'finance.customer-credit-notes.submit' => ['name' => 'Submit Customer Credit Notes', 'group' => 'Finance'],
        'finance.customer-credit-notes.approve' => ['name' => 'Approve Customer Credit Notes', 'group' => 'Finance'],
        'finance.customer-credit-notes.reject' => ['name' => 'Reject Customer Credit Notes', 'group' => 'Finance'],
        'finance.customer-credit-notes.post' => ['name' => 'Post Customer Credit Notes', 'group' => 'Finance'],
        'finance.customer-credit-notes.cancel' => ['name' => 'Cancel Customer Credit Notes', 'group' => 'Finance'],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    public const LEGACY_EQUIVALENTS = [
        'core.exchange-rates.create' => ['finance.accounts.update'],
        'core.exchange-rates.delete' => ['finance.accounts.update'],
        'core.exchange-rates.update' => ['finance.accounts.update'],
        'core.exchange-rates.view' => ['finance.accounts.view'],
        'core.fiscal-years.create' => ['finance.accounts.update'],
        'core.periods.close' => ['finance.accounts.update'],
        'core.periods.lock' => ['finance.accounts.update'],
        'core.periods.reopen' => ['finance.accounts.update'],
        'core.periods.view' => ['finance.reports.view'],
        'finance.bank-accounts.create' => ['finance.accounts.create'],
        'finance.bank-accounts.delete' => ['finance.accounts.delete'],
        'finance.bank-accounts.update' => ['finance.accounts.update'],
        'finance.bank-accounts.view' => ['finance.accounts.view'],
        'finance.bank-reconciliation.create' => ['finance.accounts.create'],
        'finance.bank-reconciliation.view' => ['finance.accounts.view'],
        'finance.bank-transactions.create' => ['finance.accounts.create'],
        'finance.bank-transactions.view' => ['finance.accounts.view'],
        'finance.budgets.approve' => ['finance.budgets.update'],
        'finance.budgets.reject' => ['finance.budgets.update'],
        'finance.budgets.submit' => ['finance.budgets.update'],
        'finance.cash-accounts.create' => ['finance.accounts.create'],
        'finance.cash-accounts.delete' => ['finance.accounts.delete'],
        'finance.cash-accounts.update' => ['finance.accounts.update'],
        'finance.cash-accounts.view' => ['finance.accounts.view'],
        'finance.customer-invoices.approve' => ['finance.customers.approve'],
        'finance.customer-invoices.cancel' => ['finance.customers.cancel'],
        'finance.customer-invoices.create' => ['finance.customers.create'],
        'finance.customer-invoices.delete' => ['finance.customers.delete'],
        'finance.customer-invoices.post' => ['finance.customers.post'],
        'finance.customer-invoices.reject' => ['finance.customers.approve'],
        'finance.customer-invoices.submit' => ['finance.customers.submit'],
        'finance.customer-invoices.update' => ['finance.customers.update'],
        'finance.customer-invoices.view' => ['finance.customers.view'],
        'finance.dashboard.view' => ['dashboard.view'],
        'finance.journals.cancel' => ['finance.journals.delete'],
        'finance.journals.reject' => ['finance.journals.approve'],
        'finance.journals.reverse' => ['finance.journals.post'],
        'finance.ledger.view' => ['finance.journals.view', 'finance.reports.view'],
        'finance.payments.create' => ['finance.suppliers.create'],
        'finance.payments.reject' => ['finance.payments.approve'],
        'finance.payments.view' => ['finance.suppliers.view'],
        'finance.receipts.create' => ['finance.customers.create'],
        'finance.receipts.reject' => ['finance.receipts.approve'],
        'finance.receipts.view' => ['finance.customers.view'],
        'finance.recurring-journals.create' => ['finance.journals.create'],
        'finance.recurring-journals.delete' => ['finance.journals.delete'],
        'finance.recurring-journals.update' => ['finance.journals.update'],
        'finance.recurring-journals.view' => ['finance.journals.view'],
        'finance.reports.export' => ['finance.reports.view'],
        'finance.supplier-credit-notes.approve' => ['finance.suppliers.approve'],
        'finance.supplier-credit-notes.cancel' => ['finance.suppliers.cancel'],
        'finance.supplier-credit-notes.create' => ['finance.suppliers.create'],
        'finance.supplier-credit-notes.delete' => ['finance.suppliers.delete'],
        'finance.supplier-credit-notes.post' => ['finance.suppliers.post'],
        'finance.supplier-credit-notes.submit' => ['finance.suppliers.approve'],
        'finance.supplier-credit-notes.update' => ['finance.suppliers.update'],
        'finance.supplier-credit-notes.view' => ['finance.suppliers.view'],
        'finance.supplier-invoices.approve' => ['finance.suppliers.approve'],
        'finance.supplier-invoices.cancel' => ['finance.suppliers.cancel'],
        'finance.supplier-invoices.create' => ['finance.suppliers.create'],
        'finance.supplier-invoices.delete' => ['finance.suppliers.delete'],
        'finance.supplier-invoices.post' => ['finance.suppliers.post'],
        'finance.supplier-invoices.reject' => ['finance.suppliers.approve'],
        'finance.supplier-invoices.submit' => ['finance.suppliers.submit'],
        'finance.supplier-invoices.update' => ['finance.suppliers.update'],
        'finance.supplier-invoices.view' => ['finance.suppliers.view'],
        'finance.bank-accounts.view-sensitive' => ['finance.bank-accounts.update'],
        'finance.fx-revaluation.view' => ['finance.journals.view'],
        'finance.fx-revaluation.run' => ['finance.journals.post'],
        'core.fiscal-years.close' => ['core.periods.close'],
        'core.fiscal-years.reopen' => ['core.periods.reopen'],
        'finance.intercompany.view' => ['finance.journals.view'],
        'finance.intercompany.create' => ['finance.journals.post'],
        'core.activity-logs.view' => ['core.audit.view'],
        'core.security-events.view' => ['core.audit.view'],
        'core.login-history.view' => ['core.audit.view'],
        'finance.data-import.use' => ['finance.accounts.create', 'finance.customers.create', 'finance.suppliers.create', 'finance.journals.create'],
        'finance.number-series.view' => ['finance.accounts.view'],
        'finance.number-series.manage' => ['finance.accounts.update'],
        'finance.payment-terms.view' => ['finance.accounts.view'],
        'finance.payment-terms.manage' => ['finance.accounts.update'],
        'finance.supplier-credit-notes.reject' => ['finance.supplier-invoices.reject'],
        'finance.customer-credit-notes.view' => ['finance.customer-invoices.view'],
        'finance.customer-credit-notes.create' => ['finance.customer-invoices.create'],
        'finance.customer-credit-notes.update' => ['finance.customer-invoices.update'],
        'finance.customer-credit-notes.delete' => ['finance.customer-invoices.delete'],
        'finance.customer-credit-notes.submit' => ['finance.customer-invoices.submit'],
        'finance.customer-credit-notes.approve' => ['finance.customer-invoices.approve'],
        'finance.customer-credit-notes.reject' => ['finance.customer-invoices.reject'],
        'finance.customer-credit-notes.post' => ['finance.customer-invoices.post'],
        'finance.customer-credit-notes.cancel' => ['finance.customer-invoices.cancel'],
    ];

    /**
     * Create the granular permissions and grant each to every role holding one of its legacy equivalents.
     * The super-admin role ("full system access") is kept holding every permission.
     *
     * @param  array<string, array{name: string, group: string}>  $permissions
     * @param  array<string, array<int, string>>  $legacyEquivalents
     */
    public static function install(array $permissions = self::GRANULAR_PERMISSIONS, array $legacyEquivalents = self::LEGACY_EQUIVALENTS): void
    {
        $now = now();

        DB::table('permissions')->insertOrIgnore(collect($permissions)->map(fn (array $definition, string $slug) => [
            'slug' => $slug,
            'name' => $definition['name'],
            'group' => $definition['group'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());

        $permissionIds = DB::table('permissions')->pluck('id', 'slug');

        foreach ($legacyEquivalents as $slug => $legacySlugs) {
            if (! $permissionIds->has($slug)) {
                continue;
            }

            $roleIds = DB::table('permission_role')
                ->whereIn('permission_id', $permissionIds->only($legacySlugs)->values())
                ->pluck('role_id')
                ->unique();

            DB::table('permission_role')->insertOrIgnore($roleIds->map(fn (int $roleId) => [
                'permission_id' => $permissionIds[$slug],
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all());
        }

        self::grantEverythingToSuperAdmin($now);
    }

    protected static function grantEverythingToSuperAdmin(mixed $now): void
    {
        $permissionIds = DB::table('permissions')->pluck('id');

        foreach (DB::table('roles')->where('slug', 'super-admin')->pluck('id') as $roleId) {
            DB::table('permission_role')->insertOrIgnore($permissionIds->map(fn (int $permissionId) => [
                'permission_id' => $permissionId,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }
    }
}
