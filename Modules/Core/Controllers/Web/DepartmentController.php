<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Department;
use Modules\Core\Models\Company;
use Modules\Core\Models\Branch;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['company', 'branch', 'parent', 'manager'])
            ->orderBy('name')
            ->get();

        return view('core.departments.index', compact('departments'));
    }

    public function create()
    {
        $companies = Company::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $parentDepartments = Department::orderBy('name')->get();

        return view('core.departments.create', compact('companies', 'branches', 'parentDepartments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'parent_id' => 'nullable|exists:departments,id',
            'code' => 'required|string|max:50|unique:departments,code',
            'name' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive',
        ]);

        Department::create($validated);

        return redirect()->route('core.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(int $id)
    {
        $department = Department::findOrFail($id);
        $companies = Company::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $parentDepartments = Department::where('id', '!=', $id)->orderBy('name')->get();

        return view('core.departments.edit', compact('department', 'companies', 'branches', 'parentDepartments'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'parent_id' => 'nullable|exists:departments,id',
            'code' => 'required|string|max:50|unique:departments,code,' . $id,
            'name' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive',
        ]);

        $department->update($validated);

        return redirect()->route('core.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return redirect()->route('core.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
