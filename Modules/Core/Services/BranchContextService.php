<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Session;
use Modules\Core\Models\Branch;
use Modules\Core\Models\UserBranch;

class BranchContextService
{
    protected ?Branch $activeBranch = null;

    public function getActiveBranch(): ?Branch
    {
        if ($this->activeBranch) {
            return $this->activeBranch;
        }

        $branchId = Session::get('active_branch_id');

        if (!$branchId) {
            return null;
        }

        return $this->activeBranch = Branch::find($branchId);
    }

    public function getActiveBranchId(): ?int
    {
        return Session::get('active_branch_id');
    }

    public function getBranchId(): ?int
    {
        return $this->getActiveBranchId();
    }

    public function setActiveBranch(int $companyId, int $branchId): bool
    {
        $userBranch = UserBranch::where('user_id', auth()->id())
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->first();

        if (!$userBranch) {
            return false;
        }

        Session::put('active_branch_id', $branchId);
        $this->activeBranch = Branch::find($branchId);

        return true;
    }

    public function clearActiveBranch(): void
    {
        Session::forget('active_branch_id');
        $this->activeBranch = null;
    }

    public function hasBranchAccess(int $branchId, ?int $companyId = null, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? Session::get('active_company_id');

        if (!$userId || !$companyId) {
            return false;
        }

        return UserBranch::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->exists();
    }

    public function getAccessibleBranches(?int $companyId = null, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? Session::get('active_company_id');

        if (!$userId || !$companyId) {
            return collect();
        }

        return Branch::whereHas('userBranches', function ($query) use ($userId, $companyId) {
            $query->where('user_id', $userId)
                ->where('company_id', $companyId)
                ->where('status', 'active');
        })->where('status', 'active')->get();
    }

    public function getAllowedBranchIds(?int $companyId = null, ?int $userId = null): array
    {
        return $this->getAccessibleBranches($companyId, $userId)->pluck('id')->toArray();
    }
}
