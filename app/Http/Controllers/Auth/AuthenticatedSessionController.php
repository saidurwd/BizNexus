<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\CompletesLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;

class AuthenticatedSessionController extends Controller
{
    use CompletesLogin;

    public function __construct(protected CompanyContextService $companyContext) {}

    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'login.id' => $user->id,
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.login');
        }

        return $this->completeLogin($request, $user, $request->boolean('remember'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
