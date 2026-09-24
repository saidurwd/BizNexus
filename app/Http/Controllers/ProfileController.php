<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Core\Models\ApprovalDelegation;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $companyId = app(CompanyContextService::class)->getActiveCompanyId();

        return view('profile.edit', [
            'user' => $user,
            'canDelegateApprovals' => $companyId && PermissionService::delegablePermissions(app(PermissionService::class)->getRolePermissions($user->id, $companyId)) !== [],
            'delegationsGiven' => ApprovalDelegation::with('delegate')->where('delegator_id', $user->id)->whereNull('revoked_at')->whereDate('ends_on', '>=', now()->toDateString())->orderBy('starts_on')->get(),
            'delegationsReceived' => ApprovalDelegation::with('delegator')->where('delegate_id', $user->id)->inForce()->get(),
            'colleagues' => User::whereKeyNot($user->id)
                ->whereHas('userCompanies', fn ($query) => $query->where('company_id', $companyId)->where('status', 'active'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_picture')) {
            if ($request->user()->profile_picture && Storage::disk('public')->exists($request->user()->profile_picture)) {
                Storage::disk('public')->delete($request->user()->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $data['profile_picture'] = $path;
        }

        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
