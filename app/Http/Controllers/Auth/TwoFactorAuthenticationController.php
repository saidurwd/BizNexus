<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Modules\Core\Services\CompanyContextService;

/**
 * Self-service enrolment in two-factor authentication from the profile page. Enrolment only takes
 * effect once the user confirms a code from their authenticator app.
 */
class TwoFactorAuthenticationController extends Controller
{
    public function store(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $enable($request->user());

        return redirect()->route('profile.edit')->with('status', 'two-factor-enabling');
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        $confirm($request->user(), $request->string('code')->toString());

        return redirect()->route('profile.edit')->with('status', 'two-factor-confirmed');
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        $generate($request->user());

        return redirect()->route('profile.edit')->with('status', 'recovery-codes-generated');
    }

    public function destroy(Request $request, DisableTwoFactorAuthentication $disable, CompanyContextService $companyContext): RedirectResponse
    {
        $requiringCompany = $companyContext->getUserCompanies($request->user()->id)->firstWhere('require_mfa', true);

        if ($requiringCompany) {
            return redirect()->route('profile.edit')->with('error', "{$requiringCompany->name} requires two-factor authentication, so it cannot be disabled.");
        }

        $disable($request->user());

        return redirect()->route('profile.edit')->with('status', 'two-factor-disabled');
    }
}
