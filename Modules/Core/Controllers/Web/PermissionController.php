<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all()->groupBy('group');

        return view('core.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('core.permissions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Permission::create($validated);

        return redirect()->route('core.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function edit(int $id)
    {
        $permission = Permission::findOrFail($id);

        return view('core.permissions.edit', compact('permission'));
    }

    public function update(Request $request, int $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug,' . $id,
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $permission->update($validated);

        return redirect()->route('core.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(int $id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return redirect()->route('core.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
