<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Modules\Core\Http\Middleware\SetTokenCompanyContext;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

/**
 * Issues API tokens bound to one company. A token may be limited to a subset of the permissions the user
 * holds in that company; without a list it carries "*" and is limited only by the user's roles.
 */
class TokenController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected PermissionService $permissionService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'company_id' => 'required|integer',
            'device_name' => 'required|string|max:255',
            'code' => 'nullable|string',
            'abilities' => 'array',
            'abilities.*' => 'string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->isActive() || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()
            && ! app(TwoFactorAuthenticationProvider::class)->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), (string) ($validated['code'] ?? ''))) {
            throw ValidationException::withMessages(['code' => 'A valid two-factor authentication code is required.']);
        }

        if (! $this->companyContext->hasCompanyAccess($validated['company_id'], $user->id)) {
            throw ValidationException::withMessages(['company_id' => 'You do not have access to this company.']);
        }

        $requestedAbilities = $validated['abilities'] ?? ['*'];
        $heldPermissions = $this->permissionService->getRolePermissions($user->id, (int) $validated['company_id']);

        if ($requestedAbilities !== ['*'] && array_diff($requestedAbilities, $heldPermissions) !== []) {
            throw ValidationException::withMessages(['abilities' => 'A token cannot carry permissions you do not hold in this company.']);
        }

        $token = $user->createToken($validated['device_name'], [
            SetTokenCompanyContext::COMPANY_ABILITY_PREFIX.$validated['company_id'],
            ...$requestedAbilities,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token created successfully',
            'data' => ['token' => $token->plainTextToken, 'company_id' => (int) $validated['company_id']],
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Token revoked', 'data' => null]);
    }
}
