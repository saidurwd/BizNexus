<?php

use App\Models\User;
use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;
use Modules\Core\Models\Company;
use Modules\Core\Models\Tenant;
use Modules\Core\Models\UserCompany;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->otherTenant = Tenant::factory()->create();
});

test('a user cannot be given access to a company of another tenant', function () {
    $foreignCompany = Company::factory()->create(['tenant_id' => $this->otherTenant->id]);

    UserCompany::create(['user_id' => companyUser([], $this->company)->id, 'company_id' => $foreignCompany->id, 'status' => 'active']);
})->throws(UnauthorizedCompanyAccessException::class);

test('companies and users created by an administrator belong to the administrator tenant', function () {
    $tenant = Tenant::factory()->create();
    $company = Company::factory()->create(['tenant_id' => $tenant->id]);
    $admin = companyUser(['core.companies.create', 'core.users.create'], $company);

    actingInCompany($admin, $company)->post(route('core.companies.store'), ['code' => 'NEWCO', 'name' => 'New Co', 'status' => 'active'])
        ->assertSessionHasNoErrors();
    $this->actingAs($admin);
    $user = User::create(['name' => 'New', 'email' => 'new@example.com', 'password' => 'secret']);

    expect(Company::where('code', 'NEWCO')->sole()->tenant_id)->toBe($tenant->id)
        ->and($user->tenant_id)->toBe($tenant->id);
});

test('company codes are unique per tenant only', function () {
    $foreignAdmin = companyUser(['core.companies.create'], $foreignCompany = Company::factory()->create(['tenant_id' => $this->otherTenant->id]));

    actingInCompany($foreignAdmin, $foreignCompany)
        ->post(route('core.companies.store'), ['code' => $this->company->code, 'name' => 'Same code, other tenant', 'status' => 'active'])
        ->assertSessionHasNoErrors();

    expect(Company::where('code', $this->company->code)->count())->toBe(2);
});

test('a company cannot be moved to another tenant', function () {
    $this->company->update(['tenant_id' => $this->otherTenant->id]);
})->throws(LogicException::class);

test('users of a suspended tenant cannot sign in', function () {
    $user = companyUser([], $this->company);
    $user->tenant->update(['status' => 'suspended']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a tenant can only sign in on the deployment serving its data region', function (?string $deploymentRegion, bool $allowed) {
    config(['tenancy.data_region' => $deploymentRegion]);
    $user = companyUser([], $this->company);
    $user->tenant->update(['data_region' => 'eu']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    expect(auth()->check())->toBe($allowed);
})->with([
    'same region' => ['eu', true],
    'other region' => ['me', false],
    'single-region installation' => [null, true],
]);
