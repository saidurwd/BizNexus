<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Company;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::orderBy('name')->get();

        return view('core.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('core.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:companies,code',
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'base_currency_id' => 'nullable|exists:currencies,id',
            'timezone' => 'nullable|string|max:100',
            'fiscal_year_start' => 'nullable|date',
            'status' => 'required|in:active,inactive',
        ]);

        Company::create($validated);

        return redirect()->route('core.companies.index')
            ->with('success', 'Company created successfully.');
    }

    public function edit(int $id)
    {
        $company = Company::findOrFail($id);

        return view('core.companies.edit', compact('company'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:companies,code,' . $id,
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'base_currency_id' => 'nullable|exists:currencies,id',
            'timezone' => 'nullable|string|max:100',
            'fiscal_year_start' => 'nullable|date',
            'status' => 'required|in:active,inactive',
        ]);

        $company->update($validated);

        return redirect()->route('core.companies.index')
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return redirect()->route('core.companies.index')
            ->with('success', 'Company deleted successfully.');
    }
}
