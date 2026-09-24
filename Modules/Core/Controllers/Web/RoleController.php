<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Core\Services\SegregationOfDutiesService;

/**
 * Roles are shared across companies, so an administrator may only assign permissions they hold themselves
 * and may only change or delete roles whose permissions they fully hold.
 */
class RoleController extends Controller
{
    public function __construct(
        protected PermissionService $permissionService,
        protected CompanyContextService $companyContext
    ) {}

    /**
     * @return array<int, int>
     */
    protected function grantablePermissionIds(): array
    {
        return Permission::whereIn('slug', $this->permissionService->getUserPermissions())->pluck('id')->all();
    }

    /**
     * Refuse a permission set that, alone or combined with other roles its holders have, breaks segregation of duties.
     *
     * @param  array<int, int>  $permissionIds
     */
    protected function ensureNoConflictingPermissions(array $permissionIds, ?Role $role = null): void
    {
        $sod = app(SegregationOfDutiesService::class);
        $slugs = Permission::whereIn('id', $permissionIds)->pluck('slug');

        if (($conflicts = $sod->conflictsIn($slugs))->isNotEmpty()) {
            throw ValidationException::withMessages(['permissions' => 'These permissions must not be combined: '.$sod->describe($conflicts).'.']);
        }

        if (! $role) {
            return;
        }

        foreach (CompanyUserRole::active()->where('role_id', $role->id)->get() as $assignment) {
            $otherRoleIds = CompanyUserRole::active()
                ->where('user_id', $assignment->user_id)
                ->where('company_id', $assignment->company_id)
                ->where('role_id', '!=', $role->id)
                ->pluck('role_id');

            $combined = $slugs->merge(Role::whereIn('id', $otherRoleIds)->with('permissions')->get()->flatMap->permissions->pluck('slug'));

            if (($conflicts = $sod->conflictsIn($combined))->isNotEmpty()) {
                throw ValidationException::withMessages(['permissions' => 'A user holding this role would combine conflicting permissions: '.$sod->describe($conflicts).'.']);
            }
        }
    }

    protected function ensureRoleIsManageable(Role $role): void
    {
        abort_unless(
            $this->permissionService->canGrantRole($role, $this->companyContext->getActiveCompanyId()),
            403,
            'This role includes permissions you do not hold.'
        );
    }

    public function index()
    {
        $roles = Role::all();

        return view('core.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::whereIn('id', $this->grantablePermissionIds())->get();

        return view('core.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => ['integer', Rule::in($this->grantablePermissionIds())],
        ]);

        $this->ensureNoConflictingPermissions($validated['permissions'] ?? []);

        $role = Role::create($validated);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('core.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(int $id)
    {
        $role = Role::findOrFail($id);
        $this->ensureRoleIsManageable($role);
        $permissions = Permission::whereIn('id', $this->grantablePermissionIds())->get();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('core.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        $this->ensureRoleIsManageable($role);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug,'.$id,
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => ['integer', Rule::in($this->grantablePermissionIds())],
        ]);

        $this->ensureNoConflictingPermissions($validated['permissions'] ?? [], $role);

        $role->update($validated);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('core.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(int $id)
    {
        $role = Role::findOrFail($id);
        $this->ensureRoleIsManageable($role);
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('core.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
