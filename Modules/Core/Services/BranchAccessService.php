<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Branch;
use Modules\Core\Models\UserBranch;
use Modules\Core\Models\UserCompany;

class BranchAccessService
{
    public function hasAccess(int $branchId, ?int $companyId = null, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? app(CompanyContextService::class)->getActiveCompanyId();

        if (! $userId || ! $companyId) {
            return false;
        }

        if ($this->hasAllBranches($companyId, $userId)) {
            return Branch::whereKey($branchId)->where('company_id', $companyId)->where('status', 'active')->exists();
        }

        return UserBranch::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Whether the user's access to the company covers every branch, including branches created later.
     */
    public function hasAllBranches(int $companyId, int $userId): bool
    {
        return UserCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where('all_branches', true)
            ->exists();
    }

    public function getAccessibleBranches(?int $companyId = null, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? app(CompanyContextService::class)->getActiveCompanyId();

        if (! $userId || ! $companyId) {
            return collect();
        }

        if ($this->hasAllBranches($companyId, $userId)) {
            return Branch::where('company_id', $companyId)->where('status', 'active')->get();
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
