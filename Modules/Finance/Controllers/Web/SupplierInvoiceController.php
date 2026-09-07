<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\Tax;

class SupplierInvoiceController extends Controller
{
    public function index()
    {
        $invoices = \Modules\Finance\Models\SupplierInvoice::with(['supplier', 'tax'])
            ->orderByDesc('invoice_date')
            ->get();

        return view('finance.supplier-invoices.index', compact('invoices'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->get();
        $taxes = Tax::where('status', 'active')->get();

        return view('finance.supplier-invoices.create', compact('suppliers', 'taxes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:50|unique:finance_supplier_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'tax_id' => 'nullable|exists:taxes,id',
            'subtotal' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,submitted,approved,paid,cancelled',
        ]);

        \Modules\Finance\Models\SupplierInvoice::create($validated);

        return redirect()->route('finance.supplier-invoices.index')
            ->with('success', 'Supplier invoice created successfully.');
    }

    public function show(int $id)
    {
        $invoice = \Modules\Finance\Models\SupplierInvoice::with(['supplier', 'tax', 'lines'])
            ->findOrFail($id);

        return view('finance.supplier-invoices.show', compact('invoice'));
    }
}
