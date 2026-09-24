<?php

use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\Company;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Support\PermissionCatalog;
use Modules\Finance\Models\Journal;

/**
 * Authenticated routes that are deliberately open to every signed-in user: sign-in flow, profile,
 * company/branch switching (which validates access itself) and the user's own notifications.
 */
const SELF_SERVICE_ROUTES = [
    'dashboard', 'logout', 'password.confirm', 'confirm-password', 'password.update', 'verification.notice', 'verification.verify',
    'verification.send', 'profile.edit', 'profile.update', 'profile.destroy',
    'two-factor.enable', 'two-factor.confirm', 'two-factor.recovery-codes', 'two-factor.disable',
    'company.selection', 'company.selection.submit', 'company.switch',
    'branch.selection', 'branch.selection.submit', 'branch.switch', 'auth.branches.index',
    'core.notifications.index', 'core.notifications.show', 'core.notifications.mark-read',
    'core.notifications.mark-all-read', 'core.notifications.destroy', 'core.notifications.destroy-all',
];

/**
 * @return array<string, RouteDefinition>
 */
function protectedWebRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RouteDefinition $route) => in_array('auth', $route->gatherMiddleware(), true))
        ->reject(fn (RouteDefinition $route) => in_array($route->getName() ?? $route->uri(), SELF_SERVICE_ROUTES, true))
        ->keyBy(fn (RouteDefinition $route) => $route->methods()[0].' '.$route->uri())
        ->all();
}

test('every authenticated web route requires a permission', function () {
    $routesWithoutPermission = collect(protectedWebRoutes())
        ->reject(fn (RouteDefinition $route) => collect($route->gatherMiddleware())->contains(fn ($middleware) => str_starts_with($middleware, 'permission:')))
        ->keys();

    expect($routesWithoutPermission)->toBeEmpty();
});

test('a user without permissions is refused on every protected route', function () {
    $company = Company::factory()->create();
    $user = companyUser([], $company);

    foreach (protectedWebRoutes() as $key => $route) {
        $uri = preg_replace('/\{[^}]+\}/', '1', $route->uri());

        actingInCompany($user, $company)
            ->call($route->methods()[0], '/'.$uri)
            ->assertForbidden();
    }
});

test('a user with the required permission can use the route', function () {
    $company = Company::factory()->create();
    $user = companyUser(['finance.journals.view'], $company);

    actingInCompany($user, $company)
        ->get(route('finance.journals.index'))
        ->assertOk();
});

test('an unprivileged user cannot grant themselves a role', function () {
    $company = Company::factory()->create();
    $user = companyUser(['finance.journals.view'], $company);

    actingInCompany($user, $company)
        ->post(route('core.roles.store'), [
            'name' => 'Escalated',
            'slug' => 'escalated',
            'permissions' => Permission::pluck('id')->all(),
        ])
        ->assertForbidden();

    expect(Role::where('slug', 'escalated')->exists())->toBeFalse();
});

test('permissions granted in one company do not apply in another company', function () {
    $companyWithPostRights = Company::factory()->create();
    $viewOnlyCompany = Company::factory()->create();
    $user = companyUser(['finance.journals.view', 'finance.journals.post'], $companyWithPostRights);
    companyUserRole($user, $viewOnlyCompany, ['finance.journals.view']);
    $journal = Journal::factory()->approved()->create(['company_id' => $viewOnlyCompany->id]);

    actingInCompany($user, $viewOnlyCompany)
        ->post(route('finance.journals.post', $journal->id))
        ->assertForbidden();

    expect(Journal::withoutGlobalScopes()->find($journal->id)->status)->toBe(Journal::STATUS_APPROVED);
});

test('an inactive role grants no permissions', function () {
    $company = Company::factory()->create();
    $user = companyUser(['finance.journals.view'], $company);
    Role::whereIn('id', CompanyUserRole::where('user_id', $user->id)->pluck('role_id'))->update(['status' => 'inactive']);

    actingInCompany($user, $company)
        ->get(route('finance.journals.index'))
        ->assertForbidden();
});

test('installing granular permissions preserves the access of roles holding the legacy permission', function () {
    $legacyPermission = Permission::create(['slug' => 'finance.suppliers.approve', 'name' => 'Approve Supplier Invoice']);
    $approver = Role::create(['name' => 'Approver', 'slug' => 'approver']);
    $viewer = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
    $approver->permissions()->attach($legacyPermission);

    PermissionCatalog::install();

    expect($approver->permissions()->pluck('slug'))
        ->toContain('finance.supplier-invoices.approve', 'finance.supplier-invoices.reject')
        ->and($viewer->permissions()->count())->toBe(0);
});
