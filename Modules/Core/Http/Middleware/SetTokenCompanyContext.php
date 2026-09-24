<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Symfony\Component\HttpFoundation\Response;

/**
 * API tokens are issued for exactly one company, recorded as a "company:{id}" ability. The company is pinned
 * for the request after re-checking that the token's user still has access to it.
 */
class SetTokenCompanyContext
{
    public const COMPANY_ABILITY_PREFIX = 'company:';

    public function __construct(protected CompanyContextService $companyContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->canSignIn()) {
            abort(403, 'This account has been deactivated.');
        }

        $abilities = $request->user()?->currentAccessToken()?->abilities ?? [];

        $companyId = collect($abilities)
            ->first(fn (string $ability) => str_starts_with($ability, self::COMPANY_ABILITY_PREFIX));

        $companyId = $companyId ? (int) substr($companyId, strlen(self::COMPANY_ABILITY_PREFIX)) : null;

        if (! $companyId || ! $this->companyContext->hasCompanyAccess($companyId, $request->user()->id)) {
            abort(403, 'This token is not valid for any company you can access.');
        }

        $this->companyContext->pinCompany($companyId);

        return $next($request);
    }
}
