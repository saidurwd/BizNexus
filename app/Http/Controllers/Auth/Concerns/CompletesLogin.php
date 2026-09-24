<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Services\BranchContextService;
use Modules\Core\Services\CompanyContextService;

trait CompletesLogin
{
    /**
     * Sign the verified user in and continue to company (and branch) selection.
     */
    protected function completeLogin(Request $request, User $user, bool $remember): RedirectResponse
    {
        $companyContext = app(CompanyContextService::class);
        $companies = $companyContext->getUserCompanies($user->id);

        if ($companies->isEmpty()) {
            return redirect()->route('login')->with('error', 'You do not have access to any companies.');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($companies->count() > 1) {
            return redirect()->route('company.selection');
        }

        $companyId = $companies->first()->id;
        $companyContext->setActiveCompany($companyId);

        $branchContext = app(BranchContextService::class);
        $branches = $branchContext->getAccessibleBranches($companyId);

        if ($branches->count() === 1) {
            $branchContext->setActiveBranch($companyId, $branches->first()->id);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
