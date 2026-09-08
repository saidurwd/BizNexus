<?php

namespace Modules\Core\Services;

use Modules\Core\Models\UserDepartment;

class DepartmentAccessService
{
    public function hasAccess(int $departmentId, ?int $branchId = null, ?int $companyId = null, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? session('active_company_id');

        if (!$userId || !$companyId) {
            return false;
        }

        $query = UserDepartment::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('department_id', $departmentId)
            ->where('status', 'active');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->exists();
    }

    public function getAccessibleDepartments(?int $companyId = null, ?int $branchId = null, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? session('active_company_id');

        if (!$userId || !$companyId) {
            return collect();
        }

        $query = \Modules\Core\Models\Department::whereHas('userDepartments', function ($query) use ($userId, $companyId) {
            $query->where('user_id', $userId)
                ->where('company_id', $companyId)
                ->where('status', 'active');
        });

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    public function getAllowedDepartmentIds(?int $companyId = null, ?int $branchId = null, ?int $userId = null): array
    {
        return $this->getAccessibleDepartments($companyId, $branchId, $userId)->pluck('id')->toArray();
    }
}
