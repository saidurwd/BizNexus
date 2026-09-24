<?php

use App\Models\User;
use Modules\Core\Models\Company;
use Modules\Core\Models\Role;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

test('an inactive user cannot sign in', function () {
    $user = companyUser([], $this->company);
    $user->update(['status' => 'inactive']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('signing in records the last login time', function () {
    $user = companyUser([], $this->company);

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('a deactivated user is signed out on their next request', function () {
    $user = companyUser(['finance.journals.view'], $this->company);
    $user->update(['status' => 'inactive']);

    actingInCompany($user, $this->company)
        ->get(route('finance.journals.index'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('a deactivated user can no longer use an api token', function () {
    $user = companyUser(['finance.journals.view'], $this->company);
    $token = $user->createToken('test', ['company:'.$this->company->id, '*'])->plainTextToken;
    $user->update(['status' => 'inactive']);

    $this->withToken($token)->getJson('/api/v1/finance/journals')->assertForbidden();
});

test('new passwords must meet the password policy', function (string $password) {
    $admin = companyUser(['core.users.create', 'finance.journals.view'], $this->company);
    $role = Role::whereHas('permissions', fn ($query) => $query->where('slug', 'core.users.create'))->first();

    actingInCompany($admin, $this->company)
        ->post(route('core.users.store'), [
            'name' => 'Weak', 'email' => 'weak@example.com',
            'password' => $password, 'password_confirmation' => $password,
            'companies' => [$this->company->id], 'roles' => [$role->id],
        ])
        ->assertSessionHasErrors('password');

    expect(User::where('email', 'weak@example.com')->exists())->toBeFalse();
})->with(['too short' => 'Sh0rt!', 'no symbol' => 'NoSymbolPassw0rd', 'no number' => 'No-Number-Password', 'no uppercase' => 'no-upper-passw0rd']);

test('an administrator can deactivate a user of their company only', function () {
    $admin = companyUser(['core.users.update'], $this->company);
    $role = Role::whereHas('permissions', fn ($query) => $query->where('slug', 'core.users.update'))->first();
    $user = companyUser([], $this->company);
    $sharedUser = companyUser([], Company::factory()->create());
    companyUserRole($sharedUser, $this->company);

    $payload = fn (User $target) => ['name' => $target->name, 'email' => $target->email, 'status' => 'inactive', 'companies' => [$this->company->id], 'roles' => [$role->id]];

    actingInCompany($admin, $this->company)->put(route('core.users.update', $user->id), $payload($user))->assertSessionHasNoErrors();
    actingInCompany($admin, $this->company)->put(route('core.users.update', $sharedUser->id), $payload($sharedUser))->assertForbidden();

    expect($user->fresh()->status)->toBe('inactive')
        ->and($sharedUser->fresh()->status)->toBe('active');
});
