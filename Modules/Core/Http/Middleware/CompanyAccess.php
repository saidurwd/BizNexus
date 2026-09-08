<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyAccessService;

class CompanyAccess
{
    public function __construct(protected CompanyAccessService $companyAccess)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $companyId = $request->route('company_id') ?? $request->input('company_id');

        if ($companyId && !$this->companyAccess->hasAccess($companyId)) {
            abort(403, 'You do not have access to this company.');
        }

        return $next($request);
    }
}
