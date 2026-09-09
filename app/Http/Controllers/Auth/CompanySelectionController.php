<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\BranchContextService;

class CompanySelectionController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected BranchContextService $branchContext
    ) {
    }

    public function index()
    {
        $companies = $this->companyContext->getUserCompanies();

        if ($companies->count() === 0) {
            return redirect()->route('login')->with('error', 'You do not have access to any companies.');
        }

        if ($companies->count() === 1) {
            $companyId = $companies->first()->id;
            $this->companyContext->setActiveCompany($companyId);

            $branches = $this->branchContext->getAccessibleBranches($companyId);

            if ($branches->count() === 1) {
                $this->branchContext->setActiveBranch($companyId, $branches->first()->id);

                return redirect()->intended(route('dashboard', absolute: false));
            }

            return redirect()->route('branch.selection', ['company_id' => $companyId]);
        }

        return view('auth.company-selection', compact('companies'));
    }

    public function select(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $companyId = (int) $request->input('company_id');

        if (!$this->companyContext->hasCompanyAccess($companyId)) {
            return redirect()->route('login')->with('error', 'You do not have access to the selected company.');
        }

        $this->companyContext->setActiveCompany($companyId);

        $accessibleBranches = $this->branchContext->getAccessibleBranches($companyId);

        if ($accessibleBranches->isNotEmpty()) {
            $branchId = (int) $request->input('branch_id');

            if (!$branchId || !$this->branchContext->hasBranchAccess($branchId, $companyId)) {
                return redirect()->route('company.selection')
                    ->with('error', 'You do not have access to the selected branch.');
            }

            $this->branchContext->setActiveBranch($companyId, $branchId);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $companyId = (int) $request->input('company_id');

        if (!$this->companyContext->hasCompanyAccess($companyId)) {
            return back()->with('error', 'You do not have access to the selected company.');
        }

        $this->companyContext->setActiveCompany($companyId);
        $this->branchContext->clearActiveBranch();

        return redirect()->route('branch.selection', ['company_id' => $companyId]);
    }
}
