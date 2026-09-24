<?php

namespace Modules\Core\Controllers\Web;

use Modules\Core\Support\Countries;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Models\Company;
use Modules\Core\Services\PermissionService;

/**
 * Existing companies can only be viewed, changed or deleted where the user holds the matching permission in that company.
 */
class CompanyController extends Controller
{
    public function __construct(protected PermissionService $permissionService) {}

    protected function findPermittedCompany(int $id, string $permission): Company
    {
        return Company::whereIn('id', $this->permissionService->companyIdsWithPermission($permission))->findOrFail($id);
    }

    public function index()
    {
        $companies = Company::whereIn('id', $this->permissionService->companyIdsWithPermission('core.companies.view'))
            ->orderBy('name')
            ->get();

        return view('core.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('core.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('companies', 'code')->where('tenant_id', $request->user()->tenant_id)],
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'country_code' => ['nullable', Rule::in(Countries::codes())],
            'registration_number' => 'nullable|string|max:100',
            'base_currency_id' => 'nullable|exists:currencies,id',
            'timezone' => 'nullable|string|max:100',
            'fiscal_year_start' => 'nullable|date',
            'status' => 'required|in:active,inactive',
            'require_mfa' => 'sometimes|boolean',
        ]);

        Company::create($validated);

        return redirect()->route('core.companies.index')
            ->with('success', 'Company created successfully.');
    }

    public function edit(int $id)
    {
        $company = $this->findPermittedCompany($id, 'core.companies.update');

        return view('core.companies.edit', compact('company'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $company = $this->findPermittedCompany($id, 'core.companies.update');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('companies', 'code')->where('tenant_id', $request->user()->tenant_id)->ignore($id)],
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'country_code' => ['nullable', Rule::in(Countries::codes())],
            'registration_number' => 'nullable|string|max:100',
            'base_currency_id' => 'nullable|exists:currencies,id',
            'timezone' => 'nullable|string|max:100',
            'fiscal_year_start' => 'nullable|date',
            'status' => 'required|in:active,inactive',
            'require_mfa' => 'sometimes|boolean',
        ]);

        $company->update($validated);

        return redirect()->route('core.companies.index')
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $company = $this->findPermittedCompany($id, 'core.companies.delete');

        try {
            $company->delete();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            return back()->with('error', 'This company has financial records and cannot be deleted. Deactivate it instead.');
        }

        return redirect()->route('core.companies.index')
            ->with('success', 'Company deleted successfully.');
    }
}
