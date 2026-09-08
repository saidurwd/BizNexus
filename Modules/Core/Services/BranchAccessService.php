<?php

namespace Modules\Core\Services;

use Modules\Core\Models\UserBranch;

class BranchAccessService
{
    public function hasAccess(int $branchId, ?int $companyId = null, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? session('active_company_id');

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
        $companyId = $companyId ?? session('active_company_id');

        if (!$userId || !$companyId) {
            return collect();
        }

        return \Modules\Core\Models\Branch::whereHas('userBranches', function ($query) use ($userId, $companyId) {
            $query->where('user_id', $userId)
                ->where('company_id', $companyId)
                ->where('status', 'active');
        })->get();
    }

    public function getAllowedBranchIds(?int $companyId = null, ?int $userId = null): array
    {
        return $this->getAccessibleBranches($companyId, $userId)->pluck('id')->toArray();
    }
}
