<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Role;
use Modules\Core\Models\Permission;
use Modules\Core\Models\CompanyUserRole;

class PermissionService
{
    public function getUserPermissions(?int $userId = null, ?int $companyId = null): array
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? session('active_company_id');

        if (!$userId || !$companyId) {
            return [];
        }

        $roles = CompanyUserRole::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->with('role.permissions')
            ->get()
            ->pluck('role');

        $permissions = collect();
        foreach ($roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }

        return $permissions->unique('slug')->pluck('slug')->toArray();
    }

    public function hasPermission(string $permissionSlug, ?int $userId = null, ?int $companyId = null): bool
    {
        $permissions = $this->getUserPermissions($userId, $companyId);

        return in_array($permissionSlug, $permissions);
    }

    public function getUserRoles(?int $userId = null, ?int $companyId = null)
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? session('active_company_id');

        if (!$userId || !$companyId) {
            return collect();
        }

        return CompanyUserRole::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->with('role')
            ->get()
            ->pluck('role');
    }
}
