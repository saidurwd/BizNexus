<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\Tax;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class SupplierInvoiceController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index()
    {
        $this->checkPermission('finance.suppliers.view');

        $invoices = \Modules\Finance\Models\SupplierInvoice::with(['supplier', 'tax'])
            ->orderByDesc('invoice_date')
            ->get();

        return view('finance.supplier-invoices.index', compact('invoices'));
    }

    public function create()
    {
        $this->checkPermission('finance.suppliers.create');

        $suppliers = Supplier::where('status', 'active')->get();
        $taxes = Tax::where('status', 'active')->get();

        return view('finance.supplier-invoices.create', compact('suppliers', 'taxes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.create');

        $validated = $request->validate([
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
        $this->checkPermission('finance.suppliers.view');

        $invoice = \Modules\Finance\Models\SupplierInvoice::with(['supplier', 'tax', 'lines'])
            ->findOrFail($id);

        return view('finance.supplier-invoices.show', compact('invoice'));
    }

    public function submit(int $id)
    {
        $this->checkPermission('finance.suppliers.approve');

        $invoice = \Modules\Finance\Models\SupplierInvoice::findOrFail($id);

        if (!$invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can be submitted.');
        }

        $invoice = app(\Modules\Finance\Services\SupplierInvoiceService::class)->submitInvoice($invoice);

        return back()->with('success', 'Invoice submitted successfully.');
    }

    public function approve(int $id)
    {
        $this->checkPermission('finance.suppliers.approve');

        $invoice = \Modules\Finance\Models\SupplierInvoice::findOrFail($id);

        if (!$invoice->isSubmitted()) {
            return back()->with('error', 'Only submitted invoices can be approved.');
        }

        $invoice = app(\Modules\Finance\Services\SupplierInvoiceService::class)->approveInvoice($invoice);

        return back()->with('success', 'Invoice approved successfully.');
    }

    public function reject(Request $request, int $id)
    {
        $this->checkPermission('finance.suppliers.approve');

        $invoice = \Modules\Finance\Models\SupplierInvoice::findOrFail($id);

        if (!$invoice->isSubmitted()) {
            return back()->with('error', 'Only submitted invoices can be rejected.');
        }

        $invoice = app(\Modules\Finance\Services\SupplierInvoiceService::class)->rejectInvoice($invoice, $request->get('reason'));

        return back()->with('success', 'Invoice rejected.');
    }
}
