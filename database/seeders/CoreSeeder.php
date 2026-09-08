<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Role;
use Modules\Core\Models\Permission;
use Modules\Core\Models\UserCompany;
use Modules\Core\Models\CompanyUserRole;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->assignPermissionsToRoles();
        $this->assignTestUserToCompanies();
    }

    protected function seedPermissions(): void
    {
        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'Dashboard'],
            ['name' => 'View Companies', 'slug' => 'core.companies.view', 'group' => 'Company Management'],
            ['name' => 'Create Company', 'slug' => 'core.companies.create', 'group' => 'Company Management'],
            ['name' => 'Edit Company', 'slug' => 'core.companies.update', 'group' => 'Company Management'],
            ['name' => 'Delete Company', 'slug' => 'core.companies.delete', 'group' => 'Company Management'],
            ['name' => 'View Branches', 'slug' => 'core.branches.view', 'group' => 'Company Management'],
            ['name' => 'Create Branch', 'slug' => 'core.branches.create', 'group' => 'Company Management'],
            ['name' => 'Edit Branch', 'slug' => 'core.branches.update', 'group' => 'Company Management'],
            ['name' => 'Delete Branch', 'slug' => 'core.branches.delete', 'group' => 'Company Management'],
            ['name' => 'View Chart of Accounts', 'slug' => 'finance.accounts.view', 'group' => 'Finance'],
            ['name' => 'Create Account', 'slug' => 'finance.accounts.create', 'group' => 'Finance'],
            ['name' => 'Edit Account', 'slug' => 'finance.accounts.update', 'group' => 'Finance'],
            ['name' => 'Delete Account', 'slug' => 'finance.accounts.delete', 'group' => 'Finance'],
            ['name' => 'View Journal Entry', 'slug' => 'finance.journals.view', 'group' => 'Finance'],
            ['name' => 'Create Journal', 'slug' => 'finance.journals.create', 'group' => 'Finance'],
            ['name' => 'Edit Journal', 'slug' => 'finance.journals.update', 'group' => 'Finance'],
            ['name' => 'Delete Journal', 'slug' => 'finance.journals.delete', 'group' => 'Finance'],
            ['name' => 'Submit Journal', 'slug' => 'finance.journals.submit', 'group' => 'Finance'],
            ['name' => 'Approve Journal', 'slug' => 'finance.journals.approve', 'group' => 'Finance'],
            ['name' => 'Post Journal', 'slug' => 'finance.journals.post', 'group' => 'Finance'],
            ['name' => 'View Ledger', 'slug' => 'finance.ledger.view', 'group' => 'Finance'],
            ['name' => 'Export Ledger', 'slug' => 'finance.ledger.export', 'group' => 'Finance'],
            ['name' => 'View Reports', 'slug' => 'finance.reports.view', 'group' => 'Finance'],
            ['name' => 'Export Reports', 'slug' => 'finance.reports.export', 'group' => 'Finance'],
            ['name' => 'View Suppliers', 'slug' => 'finance.suppliers.view', 'group' => 'Finance'],
            ['name' => 'Create Supplier', 'slug' => 'finance.suppliers.create', 'group' => 'Finance'],
            ['name' => 'Edit Supplier', 'slug' => 'finance.suppliers.update', 'group' => 'Finance'],
            ['name' => 'Delete Supplier', 'slug' => 'finance.suppliers.delete', 'group' => 'Finance'],
            ['name' => 'View Customers', 'slug' => 'finance.customers.view', 'group' => 'Finance'],
            ['name' => 'Create Customer', 'slug' => 'finance.customers.create', 'group' => 'Finance'],
            ['name' => 'Edit Customer', 'slug' => 'finance.customers.update', 'group' => 'Finance'],
            ['name' => 'Delete Customer', 'slug' => 'finance.customers.delete', 'group' => 'Finance'],
            ['name' => 'View Cost Centers', 'slug' => 'finance.costcenters.view', 'group' => 'Finance'],
            ['name' => 'Create Cost Center', 'slug' => 'finance.costcenters.create', 'group' => 'Finance'],
            ['name' => 'Edit Cost Center', 'slug' => 'finance.costcenters.update', 'group' => 'Finance'],
            ['name' => 'Delete Cost Center', 'slug' => 'finance.costcenters.delete', 'group' => 'Finance'],
            ['name' => 'View Budgets', 'slug' => 'finance.budgets.view', 'group' => 'Finance'],
            ['name' => 'Create Budget', 'slug' => 'finance.budgets.create', 'group' => 'Finance'],
            ['name' => 'Edit Budget', 'slug' => 'finance.budgets.update', 'group' => 'Finance'],
            ['name' => 'Delete Budget', 'slug' => 'finance.budgets.delete', 'group' => 'Finance'],
            ['name' => 'View Taxes', 'slug' => 'finance.taxes.view', 'group' => 'Finance'],
            ['name' => 'Create Tax', 'slug' => 'finance.taxes.create', 'group' => 'Finance'],
            ['name' => 'Edit Tax', 'slug' => 'finance.taxes.update', 'group' => 'Finance'],
            ['name' => 'Delete Tax', 'slug' => 'finance.taxes.delete', 'group' => 'Finance'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }

    protected function seedRoles(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Full system access'],
            ['name' => 'Finance Manager', 'slug' => 'finance-manager', 'description' => 'Manage all finance operations'],
            ['name' => 'Accountant', 'slug' => 'accountant', 'description' => 'Create and manage journals and reports'],
            ['name' => 'Finance User', 'slug' => 'finance-user', 'description' => 'View-only access to finance data'],
            ['name' => 'AP Clerk', 'slug' => 'ap-clerk', 'description' => 'Manage accounts payable'],
            ['name' => 'AR Clerk', 'slug' => 'ar-clerk', 'description' => 'Manage accounts receivable'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }

    protected function assignPermissionsToRoles(): void
    {
        $rolePermissions = [
            'super-admin' => Permission::all()->pluck('id'),
            'finance-manager' => Permission::whereIn('slug', [
                'dashboard.view',
                'core.companies.view', 'core.branches.view',
                'finance.accounts.view', 'finance.accounts.create', 'finance.accounts.update',
                'finance.journals.view', 'finance.journals.create', 'finance.journals.update', 'finance.journals.submit', 'finance.journals.approve', 'finance.journals.post',
                'finance.ledger.view', 'finance.ledger.export',
                'finance.reports.view', 'finance.reports.export',
                'finance.suppliers.view', 'finance.suppliers.create', 'finance.suppliers.update',
                'finance.customers.view', 'finance.customers.create', 'finance.customers.update',
                'finance.costcenters.view', 'finance.costcenters.create', 'finance.costcenters.update',
                'finance.budgets.view', 'finance.budgets.create', 'finance.budgets.update',
                'finance.taxes.view', 'finance.taxes.create', 'finance.taxes.update',
            ])->pluck('id'),
            'accountant' => Permission::whereIn('slug', [
                'dashboard.view',
                'finance.accounts.view',
                'finance.journals.view', 'finance.journals.create', 'finance.journals.update', 'finance.journals.submit',
                'finance.ledger.view', 'finance.ledger.export',
                'finance.reports.view', 'finance.reports.export',
                'finance.suppliers.view',
                'finance.customers.view',
                'finance.costcenters.view',
                'finance.budgets.view',
                'finance.taxes.view',
            ])->pluck('id'),
            'finance-user' => Permission::whereIn('slug', [
                'dashboard.view',
                'finance.accounts.view',
                'finance.journals.view',
                'finance.ledger.view',
                'finance.reports.view',
                'finance.suppliers.view',
                'finance.customers.view',
                'finance.costcenters.view',
                'finance.budgets.view',
                'finance.taxes.view',
            ])->pluck('id'),
            'ap-clerk' => Permission::whereIn('slug', [
                'dashboard.view',
                'finance.suppliers.view', 'finance.suppliers.create', 'finance.suppliers.update',
                'finance.journals.view', 'finance.journals.create',
                'finance.reports.view',
            ])->pluck('id'),
            'ar-clerk' => Permission::whereIn('slug', [
                'dashboard.view',
                'finance.customers.view', 'finance.customers.create', 'finance.customers.update',
                'finance.journals.view', 'finance.journals.create',
                'finance.reports.view',
            ])->pluck('id'),
        ];

        foreach ($rolePermissions as $roleSlug => $permissionIds) {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                $role->permissions()->sync($permissionIds);
            }
        }
    }

    protected function assignTestUserToCompanies(): void
    {
        $user = \App\Models\User::where('email', 'test@example.com')->first();

        if (!$user) {
            return;
        }

        $companies = \Modules\Core\Models\Company::all();

        foreach ($companies as $company) {
            UserCompany::firstOrCreate(
                ['user_id' => $user->id, 'company_id' => $company->id],
                ['is_default' => $company->code === 'DEMO', 'status' => 'active']
            );

            $roleSlug = $company->code === 'DEMO' ? 'finance-manager' : 'finance-user';

            $role = Role::where('slug', $roleSlug)->first();

            if ($role) {
                CompanyUserRole::firstOrCreate(
                    ['user_id' => $user->id, 'company_id' => $company->id, 'role_id' => $role->id],
                    ['status' => 'active']
                );
            }
        }
    }
}
