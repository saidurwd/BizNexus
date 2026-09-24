<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Core\Models\Company;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\UserCompany;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a user who belongs to the given company with a role holding the given permission slugs.
 *
 * @param  array<int, string>  $permissions
 */
function companyUser(array $permissions = [], ?Company $company = null): User
{
    $user = User::factory()->create();

    companyUserRole($user, $company ?? Company::factory()->create(), $permissions, isDefault: true);

    return $user;
}

/**
 * Give the user access to the company through a new role holding the given permission slugs.
 *
 * @param  array<int, string>  $permissions
 */
function companyUserRole(User $user, Company $company, array $permissions = [], bool $isDefault = false): Role
{
    UserCompany::create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'is_default' => $isDefault,
        'status' => 'active',
    ]);

    $role = Role::create([
        'name' => 'Role '.Str::random(8),
        'slug' => 'role-'.Str::lower(Str::random(8)),
        'status' => 'active',
    ]);

    $role->permissions()->sync(collect($permissions)->map(
        fn (string $slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'group' => 'Test'])->id
    ));

    CompanyUserRole::create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    return $role;
}

/**
 * Authenticate as the user with the given company active in the session.
 */
function actingInCompany(User $user, Company|int $company): TestCase
{
    return test()->actingAs($user)->withSession([
        'active_company_id' => $company instanceof Company ? $company->id : $company,
    ]);
}
