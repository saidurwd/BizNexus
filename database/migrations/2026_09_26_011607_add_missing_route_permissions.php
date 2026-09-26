<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\PermissionCatalog;

/**
 * Permissions that routes require but that only CoreSeeder created, so databases seeded before they were added
 * had no way to reach users, roles, payments, receipts, supplier notes, departments, audit or workflow screens.
 * Administration-only permissions go to super-admin; the rest follow the nearest existing permission.
 */
return new class extends Migration
{
    protected const PERMISSIONS = [
        'core.audit.view' => ['name' => 'View Audit Logs', 'group' => 'Administration'],
        'core.departments.create' => ['name' => 'Create Department', 'group' => 'Company Management'],
        'core.departments.delete' => ['name' => 'Delete Department', 'group' => 'Company Management'],
        'core.departments.update' => ['name' => 'Edit Department', 'group' => 'Company Management'],
        'core.departments.view' => ['name' => 'View Departments', 'group' => 'Company Management'],
        'core.permissions.create' => ['name' => 'Create Permission', 'group' => 'Administration'],
        'core.permissions.delete' => ['name' => 'Delete Permission', 'group' => 'Administration'],
        'core.permissions.update' => ['name' => 'Edit Permission', 'group' => 'Administration'],
        'core.permissions.view' => ['name' => 'View Permissions', 'group' => 'Administration'],
        'core.roles.create' => ['name' => 'Create Role', 'group' => 'Administration'],
        'core.roles.delete' => ['name' => 'Delete Role', 'group' => 'Administration'],
        'core.roles.update' => ['name' => 'Edit Role', 'group' => 'Administration'],
        'core.roles.view' => ['name' => 'View Roles', 'group' => 'Administration'],
        'core.users.create' => ['name' => 'Create User', 'group' => 'Administration'],
        'core.users.delete' => ['name' => 'Delete User', 'group' => 'Administration'],
        'core.users.update' => ['name' => 'Edit User', 'group' => 'Administration'],
        'core.users.view' => ['name' => 'View Users', 'group' => 'Administration'],
        'core.workflow.manage' => ['name' => 'Manage Workflows', 'group' => 'Administration'],
        'core.workflow.view' => ['name' => 'View Workflows', 'group' => 'Administration'],
        'finance.payments.approve' => ['name' => 'Approve Payment', 'group' => 'Finance'],
        'finance.payments.cancel' => ['name' => 'Cancel Payment', 'group' => 'Finance'],
        'finance.payments.create' => ['name' => 'Create Payment', 'group' => 'Finance'],
        'finance.payments.post' => ['name' => 'Post Payment', 'group' => 'Finance'],
        'finance.payments.submit' => ['name' => 'Submit Payment', 'group' => 'Finance'],
        'finance.payments.view' => ['name' => 'View Payments', 'group' => 'Finance'],
        'finance.receipts.approve' => ['name' => 'Approve Receipt', 'group' => 'Finance'],
        'finance.receipts.cancel' => ['name' => 'Cancel Receipt', 'group' => 'Finance'],
        'finance.receipts.create' => ['name' => 'Create Receipt', 'group' => 'Finance'],
        'finance.receipts.post' => ['name' => 'Post Receipt', 'group' => 'Finance'],
        'finance.receipts.submit' => ['name' => 'Submit Receipt', 'group' => 'Finance'],
        'finance.receipts.view' => ['name' => 'View Receipts', 'group' => 'Finance'],
        'finance.supplier-credit-notes.approve' => ['name' => 'Approve Supplier Credit Note', 'group' => 'Finance'],
        'finance.supplier-credit-notes.cancel' => ['name' => 'Cancel Supplier Credit Note', 'group' => 'Finance'],
        'finance.supplier-credit-notes.create' => ['name' => 'Create Supplier Credit Note', 'group' => 'Finance'],
        'finance.supplier-credit-notes.delete' => ['name' => 'Delete Supplier Credit Note', 'group' => 'Finance'],
        'finance.supplier-credit-notes.post' => ['name' => 'Post Supplier Credit Note', 'group' => 'Finance'],
        'finance.supplier-credit-notes.submit' => ['name' => 'Submit Supplier Credit Note', 'group' => 'Finance'],
        'finance.supplier-credit-notes.update' => ['name' => 'Edit Supplier Credit Note', 'group' => 'Finance'],
        'finance.supplier-credit-notes.view' => ['name' => 'View Supplier Credit Notes', 'group' => 'Finance'],
        'finance.supplier-debit-notes.cancel' => ['name' => 'Cancel Supplier Debit Note', 'group' => 'Finance'],
        'finance.supplier-debit-notes.create' => ['name' => 'Create Supplier Debit Note', 'group' => 'Finance'],
        'finance.supplier-debit-notes.delete' => ['name' => 'Delete Supplier Debit Note', 'group' => 'Finance'],
        'finance.supplier-debit-notes.post' => ['name' => 'Post Supplier Debit Note', 'group' => 'Finance'],
        'finance.supplier-debit-notes.update' => ['name' => 'Edit Supplier Debit Note', 'group' => 'Finance'],
        'finance.supplier-debit-notes.view' => ['name' => 'View Supplier Debit Notes', 'group' => 'Finance'],
    ];

    protected const LEGACY_EQUIVALENTS = [
        'finance.payments.view' => ['finance.supplier-invoices.view'],
        'finance.payments.create' => ['finance.supplier-invoices.create'],
        'finance.payments.submit' => ['finance.supplier-invoices.submit'],
        'finance.payments.approve' => ['finance.supplier-invoices.approve'],
        'finance.payments.post' => ['finance.supplier-invoices.post'],
        'finance.payments.cancel' => ['finance.supplier-invoices.cancel'],
        'finance.supplier-credit-notes.view' => ['finance.supplier-invoices.view'],
        'finance.supplier-credit-notes.create' => ['finance.supplier-invoices.create'],
        'finance.supplier-credit-notes.update' => ['finance.supplier-invoices.update'],
        'finance.supplier-credit-notes.delete' => ['finance.supplier-invoices.delete'],
        'finance.supplier-credit-notes.submit' => ['finance.supplier-invoices.submit'],
        'finance.supplier-credit-notes.approve' => ['finance.supplier-invoices.approve'],
        'finance.supplier-credit-notes.post' => ['finance.supplier-invoices.post'],
        'finance.supplier-credit-notes.cancel' => ['finance.supplier-invoices.cancel'],
        'finance.supplier-debit-notes.view' => ['finance.supplier-invoices.view'],
        'finance.supplier-debit-notes.create' => ['finance.supplier-invoices.create'],
        'finance.supplier-debit-notes.update' => ['finance.supplier-invoices.update'],
        'finance.supplier-debit-notes.delete' => ['finance.supplier-invoices.delete'],
        'finance.supplier-debit-notes.post' => ['finance.supplier-invoices.post'],
        'finance.supplier-debit-notes.cancel' => ['finance.supplier-invoices.cancel'],
        'finance.receipts.view' => ['finance.customer-invoices.view'],
        'finance.receipts.create' => ['finance.customer-invoices.create'],
        'finance.receipts.submit' => ['finance.customer-invoices.submit'],
        'finance.receipts.approve' => ['finance.customer-invoices.approve'],
        'finance.receipts.post' => ['finance.customer-invoices.post'],
        'finance.receipts.cancel' => ['finance.customer-invoices.cancel'],
        'core.departments.view' => ['core.branches.view'],
        'core.departments.create' => ['core.branches.create'],
        'core.departments.update' => ['core.branches.update'],
        'core.departments.delete' => ['core.branches.delete'],
        'core.workflow.view' => ['finance.journals.approve', 'finance.customer-invoices.approve', 'finance.supplier-invoices.approve', 'finance.budgets.approve'],
    ];

    public function up(): void
    {
        PermissionCatalog::install(self::PERMISSIONS, self::LEGACY_EQUIVALENTS);
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', array_keys(self::PERMISSIONS))->delete();
    }
};
