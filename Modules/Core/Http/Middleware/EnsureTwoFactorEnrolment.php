<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the active company requires two-factor authentication, users who have not enrolled can only
 * reach their profile (to enrol), company switching and sign-out.
 */
class EnsureTwoFactorEnrolment
{
    protected const ALLOWED_ROUTES = [
        'profile.edit', 'logout', 'password.confirm', 'company.selection', 'company.selection.submit', 'company.switch',
        'branch.selection', 'branch.selection.submit', 'branch.switch', 'auth.branches.index',
        'two-factor.enable', 'two-factor.confirm', 'two-factor.recovery-codes',
    ];

    public function __construct(protected CompanyContextService $companyContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user
            || $user->hasEnabledTwoFactorAuthentication()
            || $request->routeIs(...self::ALLOWED_ROUTES)
            || $request->route()?->getName() === null && $request->is('confirm-password')
            || ! $this->companyContext->getActiveCompany()?->require_mfa) {
            return $next($request);
        }

        return redirect()->to(route('profile.edit').'#two-factor')
            ->with('error', 'This company requires two-factor authentication. Please enable it to continue.');
    }
}
