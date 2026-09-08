<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Company;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::with('company')->orderBy('name')->get();

        return view('core.branches.index', compact('branches'));
    }

    public function create()
    {
        $companies = Company::orderBy('name')->get();

        return view('core.branches.create', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
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
        $branch = Branch::findOrFail($id);
        $companies = Company::orderBy('name')->get();

        return view('core.branches.edit', compact('branch', 'companies'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:50|unique:branches,code,' . $id,
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
        $branch = Branch::findOrFail($id);
        $branch->delete();

        return redirect()->route('core.branches.index')
            ->with('success', 'Branch deleted successfully.');
    }
}
