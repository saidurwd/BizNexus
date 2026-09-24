<?php

use Modules\Core\Models\Company;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = companyUser([
        'core.roles.create', 'core.roles.update', 'core.users.update',
        'finance.suppliers.create', 'finance.payments.approve', 'finance.journals.view',
    ], $this->company);
    $this->supplierCreate = Permission::where('slug', 'finance.suppliers.create')->first();
    $this->paymentApprove = Permission::where('slug', 'finance.payments.approve')->first();
});

function roleWith(Permission ...$permissions): Role
{
    $role = Role::create(['name' => 'Role '.uniqid(), 'slug' => 'role-'.uniqid(), 'status' => 'active']);
    $role->permissions()->sync(collect($permissions)->pluck('id'));

    return $role;
}

test('a role cannot combine conflicting permissions', function () {
    actingInCompany($this->admin, $this->company)
        ->post(route('core.roles.store'), [
            'name' => 'Vendor and payments', 'slug' => 'vendor-payments',
            'permissions' => [$this->supplierCreate->id, $this->paymentApprove->id],
        ])
        ->assertSessionHasErrors(['permissions' => 'These permissions must not be combined: finance.suppliers.create + finance.payments.approve.']);

    expect(Role::where('slug', 'vendor-payments')->exists())->toBeFalse();
});

test('a user cannot be given roles that together combine conflicting permissions', function () {
    $user = companyUser([], $this->company);

    actingInCompany($this->admin, $this->company)
        ->put(route('core.users.update', $user->id), [
            'name' => $user->name, 'email' => $user->email,
            'companies' => [$this->company->id],
            'roles' => [roleWith($this->supplierCreate)->id, roleWith($this->paymentApprove)->id],
        ])
        ->assertSessionHasErrors('roles');
});

test('a role change that would create a conflict for one of its holders is refused', function () {
    $clerkRole = roleWith($this->supplierCreate);
    $approverRole = roleWith(Permission::where('slug', 'finance.journals.view')->first());
    $user = companyUser([], $this->company);
    foreach ([$clerkRole, $approverRole] as $role) {
        CompanyUserRole::create(['user_id' => $user->id, 'company_id' => $this->company->id, 'role_id' => $role->id, 'status' => 'active']);
    }

    actingInCompany($this->admin, $this->company)
        ->put(route('core.roles.update', $approverRole->id), [
            'name' => $approverRole->name, 'slug' => $approverRole->slug,
            'permissions' => [$this->paymentApprove->id],
        ])
        ->assertSessionHasErrors('permissions');

    expect($approverRole->permissions()->pluck('slug')->all())->toBe(['finance.journals.view']);
});

test('the report lists existing conflicts', function () {
    $user = companyUser([], $this->company);
    CompanyUserRole::create(['user_id' => $user->id, 'company_id' => $this->company->id, 'role_id' => roleWith($this->supplierCreate, $this->paymentApprove)->id, 'status' => 'active']);

    $this->artisan('authorization:sod-report')
        ->expectsOutputToContain($user->email)
        ->assertFailed();
});

test('the report passes when no conflicts exist', function () {
    CompanyUserRole::query()->delete();

    $this->artisan('authorization:sod-report')
        ->expectsOutput('No segregation of duties conflicts found.')
        ->assertSuccessful();
});
