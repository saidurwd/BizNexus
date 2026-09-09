<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\BranchContextService;

class BranchSelectionController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected BranchContextService $branchContext
    ) {
    }

    public function index(Request $request)
    {
        $companyId = (int) $request->query('company_id');

        if (!$this->companyContext->hasCompanyAccess($companyId)) {
            return redirect()->route('company.selection');
        }

        $branches = $this->branchContext->getAccessibleBranches($companyId);

        if ($branches->count() === 0) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to any branches for the selected company.');
        }

        if ($branches->count() === 1) {
            $this->branchContext->setActiveBranch($companyId, $branches->first()->id);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return view('auth.branch-selection', [
            'company' => $this->companyContext->getActiveCompany() ?? \Modules\Core\Models\Company::find($companyId),
            'branches' => $branches,
        ]);
    }

    public function select(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $companyId = (int) $request->input('company_id');
        $branchId = (int) $request->input('branch_id');

        if (!$this->companyContext->hasCompanyAccess($companyId)) {
            return redirect()->route('login')->with('error', 'You do not have access to the selected company.');
        }

        if (!$this->branchContext->hasBranchAccess($branchId, $companyId)) {
            return redirect()->route('branch.selection', ['company_id' => $companyId])
                ->with('error', 'You do not have access to the selected branch.');
        }

        $this->companyContext->setActiveCompany($companyId);
        $this->branchContext->setActiveBranch($companyId, $branchId);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branchId = (int) $request->input('branch_id');
        $companyId = $this->companyContext->getActiveCompanyId();

        if (!$companyId) {
            return redirect()->route('login');
        }

        if (!$this->branchContext->hasBranchAccess($branchId, $companyId)) {
            return redirect()->back()->with('error', 'You do not have access to the selected branch.');
        }

        $this->branchContext->setActiveBranch($companyId, $branchId);

        return redirect()->back();
    }
}
