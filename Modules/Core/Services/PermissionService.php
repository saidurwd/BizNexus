<?php

namespace Modules\Core\Services;

use Illuminate\Support\Collection;
use Modules\Core\Models\ApprovalDelegation;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\UserCompany;
use Modules\Core\Scopes\CompanyScope;

class PermissionService
{
    /**
     * Permission slugs already resolved during this request, keyed by "userId:companyId".
     *
     * @var array<string, array<int, string>>
     */
    protected array $resolvedPermissions = [];

    /**
     * @var array<string, array<int, string>>
     */
    protected array $resolvedRolePermissions = [];

    /**
     * Permissions the user holds in the company: through their own roles plus approval permissions delegated to them.
     *
     * @return array<int, string>
     */
    public function getUserPermissions(?int $userId = null, ?int $companyId = null): array
    {
        $userId = $userId ?? auth()->id();
        $companyId = $companyId ?? app(CompanyContextService::class)->getActiveCompanyId();

        if (! $userId || ! $companyId) {
            return [];
        }

        return $this->resolvedPermissions["{$userId}:{$companyId}"] ??= array_values(array_unique([
            ...$this->getRolePermissions($userId, $companyId),
            ...$this->getDelegatedPermissions($userId, $companyId),
        ]));
    }

    /**
     * Permissions from the user's own roles in force in the company, excluding delegations. Use this for
     * anything the user may pass on to others (roles, API tokens, delegations), so delegated authority cannot chain.
     *
     * @return array<int, string>
     */
    public function getRolePermissions(int $userId, int $companyId): array
    {
        return $this->resolvedRolePermissions["{$userId}:{$companyId}"] ??= Permission::query()
            ->whereHas('roles', fn ($roles) => $roles
                ->where('roles.status', 'active')
                ->whereIn('roles.id', CompanyUserRole::where('user_id', $userId)
                    ->where('company_id', $companyId)->active()
                    ->select('role_id')))
            ->pluck('slug')
            ->all();
    }

    /**
     * Approval permissions lent to the user by delegations in force from active users of the company.
     *
     * @return array<int, string>
     */
    public function getDelegatedPermissions(int $userId, int $companyId): array
    {
        return ApprovalDelegation::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('delegate_id', $userId)
            ->inForce()
            ->whereHas('delegator', fn ($delegator) => $delegator->where('status', 'active'))
            ->pluck('delegator_id')
            ->flatMap(fn (int $delegatorId) => self::delegablePermissions($this->getRolePermissions($delegatorId, $companyId)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The subset of permissions that may be delegated: approving and rejecting.
     *
     * @param  iterable<int, string>  $permissions
     * @return array<int, string>
     */
    public static function delegablePermissions(iterable $permissions): array
    {
        return collect($permissions)
            ->filter(fn (string $slug) => str_ends_with($slug, '.approve') || str_ends_with($slug, '.reject'))
            ->values()
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
            ->diff($this->getRolePermissions($userId ?? auth()->id(), $companyId))
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
