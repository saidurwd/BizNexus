<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Company;
use Modules\Core\Models\Department;
use Modules\Core\Services\CompanyContextService;

/**
 * Departments are managed within the active company only; their branch, parent and manager must belong to it too.
 */
class DepartmentController extends Controller
{
    public function __construct(protected CompanyContextService $companyContext) {}

    /**
     * @return array<string, mixed>
     */
    protected function rules(?int $ignoreId = null): array
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        return [
            'company_id' => ['required', Rule::in([$companyId])],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'parent_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($ignoreId)],
            'name' => 'required|string|max:255',
            'manager_id' => ['nullable', Rule::exists('user_companies', 'user_id')->where('company_id', $companyId)],
            'status' => 'required|in:active,inactive',
        ];
    }

    public function index()
    {
        $departments = Department::with(['company', 'branch', 'parent', 'manager'])
            ->where('company_id', $this->companyContext->getActiveCompanyId())
            ->orderBy('name')
            ->get();

        return view('core.departments.index', compact('departments'));
    }

    public function create()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $companies = Company::whereKey($companyId)->get();
        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        $parentDepartments = Department::where('company_id', $companyId)->orderBy('name')->get();
        $users = User::whereHas('userCompanies', fn ($query) => $query->where('company_id', $companyId))->orderBy('name')->get(['id', 'name', 'email']);

        return view('core.departments.create', compact('companies', 'branches', 'parentDepartments', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        Department::create($validated);

        return redirect()->route('core.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(int $id)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $department = Department::where('company_id', $companyId)->findOrFail($id);
        $companies = Company::whereKey($companyId)->get();
        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        $parentDepartments = Department::where('company_id', $companyId)->where('id', '!=', $id)->orderBy('name')->get();
        $users = User::whereHas('userCompanies', fn ($query) => $query->where('company_id', $companyId))->orderBy('name')->get(['id', 'name', 'email']);

        return view('core.departments.edit', compact('department', 'companies', 'branches', 'parentDepartments', 'users'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $department = Department::where('company_id', $this->companyContext->getActiveCompanyId())->findOrFail($id);

        $validated = $request->validate($this->rules($id));

        $department->update($validated);

        return redirect()->route('core.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $department = Department::where('company_id', $this->companyContext->getActiveCompanyId())->findOrFail($id);
        $department->delete();

        return redirect()->route('core.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
