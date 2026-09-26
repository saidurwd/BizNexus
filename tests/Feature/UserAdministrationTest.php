<?php

use App\Models\User;
use Modules\Core\Models\Company;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\UserCompany;

const USER_ADMIN_PERMISSIONS = ['core.users.view', 'core.users.create', 'core.users.update', 'core.users.delete', 'finance.journals.view'];

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();
    $this->admin = companyUser(USER_ADMIN_PERMISSIONS, $this->company);
    $this->adminRole = Role::whereIn('id', CompanyUserRole::where('user_id', $this->admin->id)->pluck('role_id'))->first();
});

/**
 * @param  array<int, string>  $permissions
 */
function roleWithPermissions(array $permissions): Role
{
    $role = Role::create(['name' => 'Role '.count($permissions), 'slug' => 'role-'.uniqid(), 'status' => 'active']);
    $role->permissions()->sync(collect($permissions)->map(fn (string $slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => $slug])->id));

    return $role;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function newUserPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'New Accountant',
        'email' => 'accountant@example.com',
        'password' => 'N3w-Str0ng-Passw0rd!',
        'password_confirmation' => 'N3w-Str0ng-Passw0rd!',
    ], $overrides);
}

test('an administrator can create a user in a company they administer', function () {
    $role = roleWithPermissions(['finance.journals.view']);

    actingInCompany($this->admin, $this->company)
        ->post(route('core.users.store'), newUserPayload(['companies' => [$this->company->id], 'roles' => [$role->id]]))
        ->assertRedirect(route('core.users.index'))
        ->assertSessionHasNoErrors();

    $user = User::where('email', 'accountant@example.com')->firstOrFail();
    expect(CompanyUserRole::where('user_id', $user->id)->pluck('company_id', 'role_id')->all())->toBe([$role->id => $this->company->id]);
});

test('an administrator cannot grant access to a company they do not administer', function () {
    $role = roleWithPermissions(['finance.journals.view']);

    actingInCompany($this->admin, $this->company)
        ->post(route('core.users.store'), newUserPayload(['companies' => [$this->otherCompany->id], 'roles' => [$role->id]]))
        ->assertSessionHasErrors('companies.0');

    expect(User::where('email', 'accountant@example.com')->exists())->toBeFalse();
});

test('an administrator cannot grant a role with permissions they do not hold', function () {
    $superRole = roleWithPermissions(['finance.journals.view', 'finance.journals.post']);

    actingInCompany($this->admin, $this->company)
        ->put(route('core.users.update', $this->admin->id), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'companies' => [$this->company->id],
            'roles' => [$superRole->id],
        ])
        ->assertSessionHasErrors(['roles' => "You cannot grant the {$superRole->name} role because it includes permissions you do not hold."]);

    expect(CompanyUserRole::where('user_id', $this->admin->id)->pluck('role_id')->all())->toBe([$this->adminRole->id]);
});

test('updating a user keeps their access to companies the administrator does not administer', function () {
    $user = companyUser(['finance.journals.view'], $this->otherCompany);
    companyUserRole($user, $this->company, ['finance.journals.view']);
    $role = roleWithPermissions(['finance.journals.view']);

    actingInCompany($this->admin, $this->company)
        ->put(route('core.users.update', $user->id), [
            'name' => 'Renamed',
            'email' => $user->email,
            'companies' => [$this->company->id],
            'roles' => [$role->id],
        ])
        ->assertSessionHasNoErrors();

    expect(UserCompany::where('user_id', $user->id)->pluck('company_id')->sort()->values()->all())
        ->toBe(collect([$this->company->id, $this->otherCompany->id])->sort()->values()->all())
        ->and(CompanyUserRole::where('user_id', $user->id)->where('company_id', $this->company->id)->pluck('role_id')->all())->toBe([$role->id]);
});

test('an administrator cannot see or edit users of other companies', function () {
    $outsider = companyUser(['finance.journals.view'], $this->otherCompany);

    actingInCompany($this->admin, $this->company)
        ->get(route('core.users.index'))
        ->assertOk()
        ->assertDontSee($outsider->email);

    actingInCompany($this->admin, $this->company)
        ->get(route('core.users.edit', $outsider->id))
        ->assertNotFound();
});

test('an administrator cannot delete a user who also belongs to another company', function () {
    $user = companyUser(['finance.journals.view'], $this->otherCompany);
    companyUserRole($user, $this->company, ['finance.journals.view']);

    actingInCompany($this->admin, $this->company)
        ->delete(route('core.users.destroy', $user->id))
        ->assertForbidden();

    expect(User::find($user->id))->not->toBeNull();
});

test('the super admin role can be granted and kept although it holds conflicting permissions', function () {
    $conflicting = ['finance.suppliers.create', 'finance.payments.approve'];
    $superAdmin = Role::firstOrCreate(['slug' => Role::SUPER_ADMIN], ['name' => 'Super Admin', 'status' => 'active']);
    $superAdmin->permissions()->sync(collect([...USER_ADMIN_PERMISSIONS, ...$conflicting])->map(fn (string $slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => $slug])->id));
    $admin = companyUser([...USER_ADMIN_PERMISSIONS, ...$conflicting], $this->company);

    actingInCompany($admin, $this->company)
        ->post(route('core.users.store'), newUserPayload(['companies' => [$this->company->id], 'roles' => [$superAdmin->id]]))
        ->assertRedirect(route('core.users.index'))
        ->assertSessionHasNoErrors();
    $user = User::where('email', 'accountant@example.com')->firstOrFail();

    actingInCompany($admin, $this->company)
        ->put(route('core.users.update', $user->id), ['name' => 'Renamed Admin', 'email' => $user->email, 'companies' => [$this->company->id], 'roles' => [$superAdmin->id]])
        ->assertRedirect(route('core.users.index'))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('Renamed Admin');
});

test('a refused user form says why', function () {
    $role = roleWithPermissions(['finance.journals.view']);

    actingInCompany($this->admin, $this->company)
        ->from(route('core.users.create'))
        ->followingRedirects()
        ->post(route('core.users.store'), newUserPayload(['password' => 'short', 'password_confirmation' => 'short', 'companies' => [$this->company->id], 'roles' => [$role->id]]))
        ->assertOk()
        ->assertSee('Please correct the following and try again:')
        ->assertSee('The password field must be at least 12 characters.');
});

test('editing a user keeps their default company, and a new user gets one', function () {
    $role = roleWithPermissions(['finance.journals.view']);
    actingInCompany($this->admin, $this->company)->post(route('core.users.store'), newUserPayload(['companies' => [$this->company->id], 'roles' => [$role->id]]));
    $user = User::where('email', 'accountant@example.com')->firstOrFail();

    expect(UserCompany::where('user_id', $user->id)->where('is_default', true)->pluck('company_id')->all())->toBe([$this->company->id]);

    actingInCompany($this->admin, $this->company)
        ->put(route('core.users.update', $user->id), ['name' => 'Still Default', 'email' => $user->email, 'companies' => [$this->company->id], 'roles' => [$role->id]])
        ->assertSessionHasNoErrors();

    expect(UserCompany::where('user_id', $user->id)->where('is_default', true)->pluck('company_id')->all())->toBe([$this->company->id]);
});
