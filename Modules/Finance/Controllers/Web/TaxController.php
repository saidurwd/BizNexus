<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Tax;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $taxes = Tax::orderBy('tax_code')->paginate(20);

        return view('finance.taxes.index', compact('taxes'));
    }

    public function create()
    {
        return view('finance.taxes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tax_code' => 'required|string|max:20|unique:taxes,tax_code',
            'tax_name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'tax_type' => 'required|in:VAT,WITHHOLDING_TAX,INCOME_TAX,OTHER',
            'is_inclusive' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
            'company_id' => 'required|exists:companies,id',
        ]);

        $validated['is_inclusive'] = $validated['is_inclusive'] ?? false;

        Tax::create($validated);

        return redirect()
            ->route('finance.taxes.index')
            ->with('success', 'Tax code created successfully');
    }

    public function show(string $id)
    {
        $tax = Tax::findOrFail($id);

        return view('finance.taxes.show', compact('tax'));
    }

    public function edit(string $id)
    {
        $tax = Tax::findOrFail($id);

        return view('finance.taxes.edit', compact('tax'));
    }

    public function update(Request $request, string $id)
    {
        $tax = Tax::findOrFail($id);

        $validated = $request->validate([
            'tax_code' => 'required|string|max:20|unique:taxes,tax_code,' . $tax->id,
            'tax_name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'tax_type' => 'required|in:VAT,WITHHOLDING_TAX,INCOME_TAX,OTHER',
            'is_inclusive' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
            'company_id' => 'required|exists:companies,id',
        ]);

        $validated['is_inclusive'] = $validated['is_inclusive'] ?? false;

        $tax->update($validated);

        return redirect()
            ->route('finance.taxes.index')
            ->with('success', 'Tax updated successfully');
    }

    public function destroy(string $id)
    {
        $tax = Tax::findOrFail($id);
        $tax->delete();

        return redirect()
            ->route('finance.taxes.index')
            ->with('success', 'Tax deleted successfully');
    }
}
