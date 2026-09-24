<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

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
