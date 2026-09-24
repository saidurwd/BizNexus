<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\CompletesLogin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Second login step for users with two-factor authentication: a TOTP code or a single-use recovery code.
 */
class TwoFactorChallengeController extends Controller
{
    use CompletesLogin;

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => 'nullable|string|required_without:recovery_code',
            'recovery_code' => 'nullable|string',
        ]);

        $throttleKey = 'two-factor:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages(['code' => trans('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)])]);
        }

        if (! $this->passesChallenge($request, $user, $provider)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['code' => 'The provided two-factor code was invalid.']);
        }

        RateLimiter::clear($throttleKey);
        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');

        return $this->completeLogin($request, $user, $remember);
    }

    protected function pendingUser(Request $request): ?User
    {
        $user = User::find($request->session()->get('login.id'));

        return $user?->canSignIn() && $user->hasEnabledTwoFactorAuthentication() ? $user : null;
    }

    protected function passesChallenge(Request $request, User $user, TwoFactorAuthenticationProvider $provider): bool
    {
        if ($request->filled('code')) {
            return $provider->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $request->string('code')->toString());
        }

        $recoveryCode = collect($user->recoveryCodes())->first(fn (string $code) => hash_equals($code, $request->string('recovery_code')->toString()));

        if ($recoveryCode === null) {
            return false;
        }

        $user->replaceRecoveryCode($recoveryCode);

        return true;
    }
}
