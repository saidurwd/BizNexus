<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Company;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Core\Models\UserCompany;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\UserBranch;
use Modules\Core\Models\UserDepartment;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Department;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        return view('core.users.index', compact('users'));
    }

    public function create()
    {
        $companies = Company::all();
        $roles = Role::all();

        return view('core.users.create', compact('companies', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'companies' => 'required|array|min:1',
            'companies.*' => 'exists:companies,id',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'branches' => 'array',
            'branches.*' => 'exists:branches,id',
            'departments' => 'array',
            'departments.*' => 'exists:departments,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        foreach ($validated['companies'] as $companyId) {
            UserCompany::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'is_default' => false,
                'status' => 'active',
            ]);

            foreach ($validated['roles'] as $roleId) {
                CompanyUserRole::create([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'role_id' => $roleId,
                    'status' => 'active',
                ]);
            }
        }

        if (isset($validated['branches'])) {
            foreach ($validated['branches'] as $branchId) {
                $branch = Branch::find($branchId);
                UserBranch::create([
                    'user_id' => $user->id,
                    'company_id' => $branch->company_id,
                    'branch_id' => $branchId,
                    'status' => 'active',
                ]);
            }
        }

        if (isset($validated['departments'])) {
            foreach ($validated['departments'] as $departmentId) {
                $department = Department::find($departmentId);
                UserDepartment::create([
                    'user_id' => $user->id,
                    'company_id' => $department->company_id,
                    'branch_id' => $department->branch_id,
                    'department_id' => $departmentId,
                    'status' => 'active',
                ]);
            }
        }

        return redirect()->route('core.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(int $id)
    {
        $user = User::findOrFail($id);
        $companies = Company::all();
        $roles = Role::all();
        $userCompanies = UserCompany::where('user_id', $id)->pluck('company_id')->toArray();
        $userRoles = CompanyUserRole::where('user_id', $id)->pluck('role_id')->toArray();
        $userBranches = UserBranch::where('user_id', $id)->pluck('branch_id')->toArray();
        $userDepartments = UserDepartment::where('user_id', $id)->pluck('department_id')->toArray();

        return view('core.users.edit', compact('user', 'companies', 'roles', 'userCompanies', 'userRoles', 'userBranches', 'userDepartments'));
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'companies' => 'required|array|min:1',
            'companies.*' => 'exists:companies,id',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'branches' => 'array',
            'branches.*' => 'exists:branches,id',
            'departments' => 'array',
            'departments.*' => 'exists:departments,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ? bcrypt($validated['password']) : $user->password,
        ]);

        UserCompany::where('user_id', $id)->delete();
        CompanyUserRole::where('user_id', $id)->delete();
        UserBranch::where('user_id', $id)->delete();
        UserDepartment::where('user_id', $id)->delete();

        foreach ($validated['companies'] as $companyId) {
            UserCompany::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'is_default' => false,
                'status' => 'active',
            ]);

            foreach ($validated['roles'] as $roleId) {
                CompanyUserRole::create([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'role_id' => $roleId,
                    'status' => 'active',
                ]);
            }
        }

        if (isset($validated['branches'])) {
            foreach ($validated['branches'] as $branchId) {
                $branch = Branch::find($branchId);
                UserBranch::create([
                    'user_id' => $user->id,
                    'company_id' => $branch->company_id,
                    'branch_id' => $branchId,
                    'status' => 'active',
                ]);
            }
        }

        if (isset($validated['departments'])) {
            foreach ($validated['departments'] as $departmentId) {
                $department = Department::find($departmentId);
                UserDepartment::create([
                    'user_id' => $user->id,
                    'company_id' => $department->company_id,
                    'branch_id' => $department->branch_id,
                    'department_id' => $departmentId,
                    'status' => 'active',
                ]);
            }
        }

        return redirect()->route('core.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('core.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
