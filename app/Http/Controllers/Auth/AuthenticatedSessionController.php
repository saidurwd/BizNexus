<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected CompanyContextService $companyContext)
    {
    }

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!auth()->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $companies = $this->companyContext->getUserCompanies();

        if ($companies->count() === 0) {
            auth()->logout();

            return redirect()->route('login')->with('error', 'You do not have access to any companies.');
        }

        if ($companies->count() === 1) {
            $companyId = $companies->first()->id;
            $this->companyContext->setActiveCompany($companyId);

            $branches = app(\Modules\Core\Services\BranchContextService::class)->getAccessibleBranches($companyId);

            if ($branches->count() === 1) {
                app(\Modules\Core\Services\BranchContextService::class)->setActiveBranch($companyId, $branches->first()->id);

                return redirect()->intended(route('dashboard', absolute: false));
            }

            return redirect()->route('branch.selection', ['company_id' => $companyId]);
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
