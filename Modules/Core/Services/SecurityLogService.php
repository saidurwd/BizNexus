<?php

namespace Modules\Core\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Core\Models\LoginHistory;
use Modules\Core\Models\SecurityEvent;

/**
 * Records sign-ins and security events with the request's IP address and browser.
 */
class SecurityLogService
{
    protected const SESSION_KEY = 'security.login_history_id';

    /**
     * @param  array<string, mixed>  $context
     */
    public function event(string $type, string $severity, ?User $user = null, ?string $email = null, array $context = []): SecurityEvent
    {
        return SecurityEvent::create([
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'email' => $email ?? $user?->email,
            'type' => $type,
            'severity' => $severity,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 490, ''),
            'context' => $context ?: null,
        ]);
    }

    /**
     * Open a sign-in record and remember it in the session so sign-out can close it.
     */
    public function openLogin(User $user, string $method): LoginHistory
    {
        $login = LoginHistory::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'method' => $method,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 490, ''),
            'logged_in_at' => now(),
        ]);

        if (request()->hasSession()) {
            request()->session()->put(self::SESSION_KEY, $login->id);
        }

        return $login;
    }

    public function closeLogin(): void
    {
        if (! request()->hasSession() || ! ($loginId = request()->session()->pull(self::SESSION_KEY))) {
            return;
        }

        LoginHistory::whereKey($loginId)->whereNull('logged_out_at')->update(['logged_out_at' => now()]);
    }
}
