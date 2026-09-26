<?php

namespace Modules\Core\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Symfony\Component\HttpFoundation\Response;

/**
 * The user's locale, else the active company's, else APP_LOCALE; only supported locales are applied.
 */
class SetLocale
{
    public function __construct(protected CompanyContextService $companyContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('app.supported_locales', []));
        $locale = collect([$request->user()?->locale, $this->companyContext->getActiveCompany()?->locale])
            ->first(fn (?string $candidate) => $candidate && in_array($candidate, $supported, true));

        if ($locale) {
            app()->setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
