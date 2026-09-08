<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();

        return view('core.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();

        return view('core.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
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
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('core.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
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
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('core.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
