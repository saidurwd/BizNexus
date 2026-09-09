<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\BranchContextService;
use Modules\Core\Services\CompanyContextService;

class EnsureCompanyAndBranchSelected
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected BranchContextService $branchContext
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $companyId = session('active_company_id');

        if (!$companyId) {
            return redirect()->route('company.selection');
        }

        $branchId = session('active_branch_id');

        if (!$branchId) {
            $accessibleBranches = $this->branchContext->getAccessibleBranches($companyId);

            if ($accessibleBranches->isNotEmpty()) {
                return redirect()->route('branch.selection', ['company_id' => $companyId]);
            }
        }

        return $next($request);
    }
}
