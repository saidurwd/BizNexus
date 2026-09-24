<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Session;
use Modules\Core\Models\Branch;

class BranchContextService
{
    protected ?Branch $activeBranch = null;

    public function getActiveBranch(): ?Branch
    {
        if ($this->activeBranch) {
            return $this->activeBranch;
        }

        $branchId = Session::get('active_branch_id');

        if (! $branchId) {
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
        if (! $this->hasBranchAccess($branchId, $companyId)) {
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
        return app(BranchAccessService::class)->hasAccess($branchId, $companyId, $userId);
    }

    public function getAccessibleBranches(?int $companyId = null, ?int $userId = null)
    {
        return app(BranchAccessService::class)->getAccessibleBranches($companyId, $userId);
    }

    public function getAllowedBranchIds(?int $companyId = null, ?int $userId = null): array
    {
        return $this->getAccessibleBranches($companyId, $userId)->pluck('id')->toArray();
    }
}
