<?php

use App\Models\User;
use Laravel\Fortify\Fortify;
use Modules\Core\Models\Company;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = companyUser(['finance.journals.view'], $this->company);
});

function enableTwoFactor(User $user): string
{
    $secret = app(Google2FA::class)->generateSecretKey();

    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code-one', 'recovery-code-two'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $secret;
}

function currentCode(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

test('a user with two-factor authentication is challenged instead of signed in', function () {
    enableTwoFactor($this->user);

    $this->post('/login', ['email' => $this->user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
});

test('a valid authenticator code completes the sign in', function () {
    $secret = enableTwoFactor($this->user);
    $this->post('/login', ['email' => $this->user->email, 'password' => 'password']);

    $this->post(route('two-factor.login'), ['code' => currentCode($secret)])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($this->user);
    expect(session('active_company_id'))->toBe($this->company->id);
});

test('an invalid authenticator code is refused', function () {
    enableTwoFactor($this->user);
    $this->post('/login', ['email' => $this->user->email, 'password' => 'password']);

    $this->post(route('two-factor.login'), ['code' => '000000'])
        ->assertSessionHasErrors(['code' => 'The provided two-factor code was invalid.']);

    $this->assertGuest();
});

test('a recovery code signs in once and is then replaced', function () {
    enableTwoFactor($this->user);
    $this->post('/login', ['email' => $this->user->email, 'password' => 'password']);

    $this->post(route('two-factor.login'), ['recovery_code' => 'recovery-code-one'])->assertRedirect();

    $this->assertAuthenticatedAs($this->user);
    expect($this->user->fresh()->recoveryCodes())->not->toContain('recovery-code-one')->toContain('recovery-code-two');
});

test('the challenge cannot be used without a pending sign in', function () {
    $this->post(route('two-factor.login'), ['code' => '123456'])->assertRedirect(route('login'));

    $this->assertGuest();
});

test('a user enrols by confirming a code from the authenticator app', function () {
    $this->actingAs($this->user)->withSession(['active_company_id' => $this->company->id, 'auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('profile.edit'));

    $secret = Fortify::currentEncrypter()->decrypt($this->user->fresh()->two_factor_secret);
    expect($this->user->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();

    $this->actingAs($this->user)->withSession(['active_company_id' => $this->company->id, 'auth.password_confirmed_at' => time()])
        ->post(route('two-factor.confirm'), ['code' => currentCode($secret)])
        ->assertRedirect(route('profile.edit'));

    expect($this->user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('a company that requires two-factor authentication holds unenrolled users on their profile', function () {
    $this->company->update(['require_mfa' => true]);

    actingInCompany($this->user, $this->company)
        ->get(route('finance.journals.index'))
        ->assertRedirect(route('profile.edit').'#two-factor');

    actingInCompany($this->user, $this->company)->get(route('profile.edit'))->assertOk();
});

test('enrolled users work normally in a company that requires two-factor authentication', function () {
    $this->company->update(['require_mfa' => true]);
    enableTwoFactor($this->user);

    actingInCompany($this->user, $this->company)->get(route('finance.journals.index'))->assertOk();
});

test('two-factor authentication cannot be disabled while a company requires it', function () {
    $this->company->update(['require_mfa' => true]);
    enableTwoFactor($this->user);

    $this->actingAs($this->user)->withSession(['active_company_id' => $this->company->id, 'auth.password_confirmed_at' => time()])
        ->delete(route('two-factor.disable'))
        ->assertSessionHas('error');

    expect($this->user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('an api token for a user with two-factor authentication requires a valid code', function () {
    $secret = enableTwoFactor($this->user);
    $request = ['email' => $this->user->email, 'password' => 'password', 'company_id' => $this->company->id, 'device_name' => 'cli'];

    $this->postJson('/api/v1/tokens', $request)->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->postJson('/api/v1/tokens', [...$request, 'code' => currentCode($secret)])->assertCreated();
});

test('the profile shows the QR code while pending and recovery codes once enabled', function () {
    $this->user->forceFill(['two_factor_secret' => Fortify::currentEncrypter()->encrypt(app(Google2FA::class)->generateSecretKey())])->save();

    actingInCompany($this->user, $this->company)->get(route('profile.edit'))->assertOk()->assertSee('<svg', false);

    enableTwoFactor($this->user);

    actingInCompany($this->user, $this->company)->get(route('profile.edit'))->assertOk()->assertSee('recovery-code-one');
});
