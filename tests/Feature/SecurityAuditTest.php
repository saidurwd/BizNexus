<?php

use App\Models\User;
use Modules\Core\Models\ActivityLog;
use Modules\Core\Models\Company;
use Modules\Core\Models\LoginHistory;
use Modules\Core\Models\SecurityEvent;
use Modules\Core\Models\Tenant;

test('signing in and out is kept in the login history', function () {
    $user = companyUser();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $login = LoginHistory::sole();

    expect($login->user_id)->toBe($user->id)
        ->and($login->method)->toBe('password')
        ->and($login->logged_out_at)->toBeNull();

    $this->post('/logout');

    expect($login->fresh()->logged_out_at)->not->toBeNull();
});

test('failed sign-ins and lockouts are security events with the reason', function () {
    $user = companyUser();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
    $this->post('/login', ['email' => 'nobody@example.test', 'password' => 'secret']);

    expect(SecurityEvent::where('type', SecurityEvent::LOGIN_FAILED)->pluck('context')->pluck('reason')->all())
        ->toBe(['wrong_password', 'unknown_email']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }

    expect(SecurityEvent::where('type', SecurityEvent::LOCKOUT)->where('user_id', $user->id)->exists())->toBeTrue();
});

test('refused access is recorded as a security event', function () {
    $company = Company::factory()->create();
    $user = companyUser([], $company);

    actingInCompany($user, $company)->get(route('core.users.index'))->assertForbidden();

    expect(SecurityEvent::where('type', SecurityEvent::ACCESS_DENIED)->sole())
        ->user_id->toBe($user->id)
        ->context->toMatchArray(['permission' => 'core.users.view', 'route' => 'core.users.index']);
});

test('changes and downloads are activity-logged, ordinary page views are not', function () {
    $company = Company::factory()->create();
    $user = companyUser(['finance.customers.view', 'finance.customers.create', 'finance.reports.view', 'finance.reports.export'], $company);

    actingInCompany($user, $company)->get(route('finance.customers.index'))->assertOk();
    actingInCompany($user, $company)->post(route('finance.customers.store'), []);
    actingInCompany($user, $company)->get(route('finance.reports.export', ['report' => 'trial-balance']))->assertOk();

    expect(ActivityLog::orderBy('id')->get(['method', 'route_name', 'company_id'])->toArray())->toBe([
        ['method' => 'POST', 'route_name' => 'finance.customers.store', 'company_id' => $company->id],
        ['method' => 'GET', 'route_name' => 'finance.reports.export', 'company_id' => $company->id],
    ]);
});

test('the security screens list their records and only for the viewer\'s tenant', function () {
    $company = Company::factory()->create();
    $auditor = companyUser(['core.activity-logs.view', 'core.security-events.view', 'core.login-history.view'], $company);
    $colleague = companyUser([], $company);
    $outsider = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'name' => 'Outside Person']);

    SecurityEvent::create(['tenant_id' => $colleague->tenant_id, 'user_id' => $colleague->id, 'type' => SecurityEvent::LOGIN_FAILED, 'severity' => 'warning', 'context' => ['reason' => 'wrong_password']]);
    SecurityEvent::create(['tenant_id' => $outsider->tenant_id, 'user_id' => $outsider->id, 'type' => SecurityEvent::LOGIN_FAILED, 'severity' => 'warning']);
    LoginHistory::create(['tenant_id' => $colleague->tenant_id, 'user_id' => $colleague->id, 'ip_address' => '203.0.113.7', 'logged_in_at' => now()]);
    ActivityLog::create(['tenant_id' => $colleague->tenant_id, 'user_id' => $colleague->id, 'method' => 'POST', 'route_name' => 'finance.journals.post', 'path' => '/finance/journals/9/post', 'status' => 302]);

    actingInCompany($auditor, $company)->get(route('core.security-events.index'))->assertOk()->assertSee($colleague->name)->assertSee('wrong password')->assertDontSee('Outside Person');
    actingInCompany($auditor, $company)->get(route('core.login-history.index'))->assertOk()->assertSee('203.0.113.7');
    actingInCompany($auditor, $company)->get(route('core.activity-logs.index', ['q' => 'journals.post']))->assertOk()->assertSee('/finance/journals/9/post');
});
