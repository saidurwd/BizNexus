<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\BranchContextService;
use Modules\Core\Services\CompanyContextService;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected CompanyContextService $companyContext) {}

    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $companies = $this->companyContext->getUserCompanies();

        if ($companies->count() === 0) {
            auth()->logout();

            return redirect()->route('login')->with('error', 'You do not have access to any companies.');
        }

        if ($companies->count() === 1) {
            $companyId = $companies->first()->id;
            $this->companyContext->setActiveCompany($companyId);

            $branches = app(BranchContextService::class)->getAccessibleBranches($companyId);

            if ($branches->count() === 1) {
                app(BranchContextService::class)->setActiveBranch($companyId, $branches->first()->id);
            }

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->route('company.selection');
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
