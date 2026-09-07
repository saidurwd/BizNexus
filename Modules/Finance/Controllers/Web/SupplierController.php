<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Supplier;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $suppliers = Supplier::where('company_id', $companyId)
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->get();

        return view('finance.suppliers.index', [
            'suppliers' => $suppliers,
        ]);
    }

    public function create()
    {
        return view('finance.suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'supplier_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        Supplier::create($validated);

        return redirect()->route('finance.suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function show(int $id)
    {
        $supplier = Supplier::with(['invoices', 'payments'])
            ->findOrFail($id);

        return view('finance.suppliers.show', [
            'supplier' => $supplier,
        ]);
    }
}
