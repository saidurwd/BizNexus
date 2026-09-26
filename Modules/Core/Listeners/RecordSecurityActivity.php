<?php

namespace Modules\Core\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Modules\Core\Models\SecurityEvent;
use Modules\Core\Services\SecurityLogService;

/**
 * Turns authentication events into sign-in history and security events.
 */
class RecordSecurityActivity
{
    public function __construct(protected SecurityLogService $securityLog) {}

    public function login(Login $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User) {
            return;
        }

        $method = match (true) {
            ! request()->isMethod('POST') || ! request()->is('login', 'two-factor-challenge') => 'remember_me',
            $event->user->hasEnabledTwoFactorAuthentication() => 'two_factor',
            default => 'password',
        };

        $this->securityLog->openLogin($event->user, $method);
    }

    public function logout(Logout $event): void
    {
        $this->securityLog->closeLogin();
    }

    public function lockout(Lockout $event): void
    {
        $email = (string) $event->request->input('email');

        $this->securityLog->event(SecurityEvent::LOCKOUT, SecurityEvent::SEVERITY_CRITICAL, User::where('email', $email)->first(), $email);
    }

    public function passwordReset(PasswordReset $event): void
    {
        $this->securityLog->event(SecurityEvent::PASSWORD_RESET, SecurityEvent::SEVERITY_WARNING, $event->user instanceof User ? $event->user : null);
    }

    public function twoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->securityLog->event(SecurityEvent::TWO_FACTOR_ENABLED, SecurityEvent::SEVERITY_INFO, $event->user);
    }

    public function twoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->securityLog->event(SecurityEvent::TWO_FACTOR_DISABLED, SecurityEvent::SEVERITY_WARNING, $event->user);
    }

    public function recoveryCodesGenerated(RecoveryCodesGenerated $event): void
    {
        $this->securityLog->event(SecurityEvent::RECOVERY_CODES_REGENERATED, SecurityEvent::SEVERITY_INFO, $event->user);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'login',
            Logout::class => 'logout',
            Lockout::class => 'lockout',
            PasswordReset::class => 'passwordReset',
            TwoFactorAuthenticationConfirmed::class => 'twoFactorConfirmed',
            TwoFactorAuthenticationDisabled::class => 'twoFactorDisabled',
            RecoveryCodesGenerated::class => 'recoveryCodesGenerated',
        ];
    }
}
