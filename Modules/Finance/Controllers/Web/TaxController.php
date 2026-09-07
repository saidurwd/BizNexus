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
            'tax_code' => 'required|string|max:20|unique:finance_taxes,tax_code',
            'tax_name' => 'required|string|max:100',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tax_type' => 'required|in:SALES,PURCHASE,VAT',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ]);

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
}
