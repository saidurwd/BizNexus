<?php

namespace Modules\Core\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\ApprovalDelegation;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Role;
use Modules\Core\Notifications\ApprovalRequested;
use Modules\Core\Scopes\CompanyScope;

/**
 * Notifies the people who can approve a submitted document: holders of the approve permission in its company,
 * and anyone standing in for them under a delegation in force. The submitter is never notified.
 */
class ApprovalNotifier
{
    public function documentSubmitted(int $companyId, string $approvePermission, string $documentLabel, string $documentNumber, string $url, ?string $amount = null, ?string $currency = null): void
    {
        $approvers = $this->approvers($companyId, $approvePermission);

        if ($approvers->isEmpty()) {
            return;
        }

        Notification::send($approvers, new ApprovalRequested($documentLabel, $documentNumber, $url, Auth::user()?->name, $amount, $currency, $companyId));
    }

    /**
     * @return Collection<int, User>
     */
    public function approvers(int $companyId, string $approvePermission): Collection
    {
        $roleIds = Role::where('status', 'active')
            ->whereHas('permissions', fn ($permissions) => $permissions->where('slug', $approvePermission))
            ->pluck('id');

        $holderIds = CompanyUserRole::where('company_id', $companyId)->active()->whereIn('role_id', $roleIds)->pluck('user_id');

        $delegateIds = ApprovalDelegation::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->inForce()
            ->whereIn('delegator_id', $holderIds)
            ->pluck('delegate_id');

        return User::whereIn('id', $holderIds->merge($delegateIds)->unique())
            ->where('status', 'active')
            ->when(Auth::id(), fn ($query, int $submitterId) => $query->whereKeyNot($submitterId))
            ->get();
    }
}
