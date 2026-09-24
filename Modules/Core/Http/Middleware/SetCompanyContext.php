<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;

class SetCompanyContext
{
    public function __construct(protected CompanyContextService $companyContext) {}

    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $activeCompanyId = session('active_company_id');

            if ($activeCompanyId && ! $this->companyContext->hasCompanyAccess((int) $activeCompanyId)) {
                session()->forget(['active_company_id', 'active_branch_id']);
                $activeCompanyId = null;
            }

            if (! $activeCompanyId) {
                $defaultCompany = $this->companyContext->getDefaultCompany();

                if ($defaultCompany) {
                    $this->companyContext->setActiveCompany($defaultCompany->id);
                }
            }
        }

        return $next($request);
    }
}
