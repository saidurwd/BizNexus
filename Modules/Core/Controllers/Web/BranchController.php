<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;

/**
 * Branches are managed within the active company only.
 */
class BranchController extends Controller
{
    public function __construct(protected CompanyContextService $companyContext) {}

    public function index()
    {
        $branches = Branch::with('company')->where('company_id', $this->companyContext->getActiveCompanyId())->orderBy('name')->get();

        return view('core.branches.index', compact('branches'));
    }

    public function create()
    {
        $companies = Company::whereKey($this->companyContext->getActiveCompanyId())->get();

        return view('core.branches.create', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', Rule::in([$this->companyContext->getActiveCompanyId()])],
            'code' => 'required|string|max:50|unique:branches,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        Branch::create($validated);

        return redirect()->route('core.branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function edit(int $id)
    {
        $branch = Branch::where('company_id', $this->companyContext->getActiveCompanyId())->findOrFail($id);
        $companies = Company::whereKey($this->companyContext->getActiveCompanyId())->get();

        return view('core.branches.edit', compact('branch', 'companies'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $branch = Branch::where('company_id', $this->companyContext->getActiveCompanyId())->findOrFail($id);

        $validated = $request->validate([
            'company_id' => ['required', Rule::in([$this->companyContext->getActiveCompanyId()])],
            'code' => 'required|string|max:50|unique:branches,code,'.$id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $branch->update($validated);

        return redirect()->route('core.branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $branch = Branch::where('company_id', $this->companyContext->getActiveCompanyId())->findOrFail($id);
        $branch->delete();

        return redirect()->route('core.branches.index')
            ->with('success', 'Branch deleted successfully.');
    }
}
