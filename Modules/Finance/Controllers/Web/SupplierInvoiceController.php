<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\SupplierInvoiceService;

class SupplierInvoiceController extends Controller
{
    public function __construct(
        private SupplierInvoiceService $invoiceService
    ) {}

    public function index(Request $request)
    {
        $invoices = SupplierInvoice::with('supplier')
            ->orderBy('invoice_date', 'desc')
            ->paginate(20);

        return view('finance.supplier-invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('finance.supplier-invoices.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50|unique:finance_supplier_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'supplier_id' => 'nullable|exists:finance_suppliers,id',
            'tax_id' => 'nullable|exists:finance_taxes,id',
            'subtotal' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ]);

        $invoice = $this->invoiceService->createInvoice($validated);

        return redirect()
            ->route('finance.supplier-invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully');
    }

    public function show(string $id)
    {
        $invoice = SupplierInvoice::with('supplier')->findOrFail($id);

        return view('finance.supplier-invoices.show', compact('invoice'));
    }
}
