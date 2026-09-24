<?php

namespace Modules\Core\Services;

use Illuminate\Support\Collection;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\UserCompany;

class PermissionService
{
    /**
     * Permission slugs already resolved during this request, keyed by "userId:companyId".
     *
     * @var array<string, array<int, string>>
     */
    protected array $resolvedPermissions = [];

    public function getUserPermissions(?int $userId = null, ?int $companyId = null): array
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? app(CompanyContextService::class)->getActiveCompanyId();

        if (! $userId || ! $companyId) {
            return [];
        }

        return $this->resolvedPermissions["{$userId}:{$companyId}"] ??= Permission::query()
            ->whereHas('roles', fn ($roles) => $roles
                ->where('roles.status', 'active')
                ->whereIn('roles.id', CompanyUserRole::where('user_id', $userId)
                    ->where('company_id', $companyId)->active()
                    ->select('role_id')))
            ->pluck('slug')
            ->all();
    }

    /**
     * Ids of the companies in which the user holds the permission through an active role and active company access.
     *
     * @return Collection<int, int>
     */
    public function companyIdsWithPermission(string $permissionSlug, ?int $userId = null): Collection
    {
        $userId = $userId ?? auth()->id();

        return CompanyUserRole::where('user_id', $userId)->active()
            ->whereIn('company_id', UserCompany::where('user_id', $userId)->where('status', 'active')->select('company_id'))
            ->whereHas('role', fn ($role) => $role
                ->where('status', 'active')
                ->whereHas('permissions', fn ($permissions) => $permissions->where('slug', $permissionSlug)))
            ->pluck('company_id')
            ->unique()
            ->values();
    }

    /**
     * Whether every permission of the role is also held by the user in the company, so granting it cannot escalate privileges.
     */
    public function canGrantRole(Role $role, int $companyId, ?int $userId = null): bool
    {
        return $role->permissions()->pluck('slug')
            ->diff($this->getUserPermissions($userId, $companyId))
            ->isEmpty();
    }

    public function hasPermission(string $permissionSlug, ?int $userId = null, ?int $companyId = null): bool
    {
        $permissions = $this->getUserPermissions($userId, $companyId);

        return in_array($permissionSlug, $permissions);
    }

    public function getUserRoles(?int $userId = null, ?int $companyId = null)
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? app(CompanyContextService::class)->getActiveCompanyId();

        if (! $userId || ! $companyId) {
            return collect();
        }

        return CompanyUserRole::where('user_id', $userId)
            ->where('company_id', $companyId)->active()
            ->with('role')
            ->get()
            ->pluck('role');
    }
}
