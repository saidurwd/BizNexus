<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Services\CustomerInvoiceService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class CustomerInvoiceController extends Controller
{
    public function __construct(
        protected CustomerInvoiceService $invoiceService,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.customers.view');

        $invoices = CustomerInvoice::with('customer')
            ->orderBy('invoice_date', 'desc')
            ->paginate(20);

        return view('finance.customer-invoices.index', compact('invoices'));
    }

    public function create()
    {
        $this->checkPermission('finance.customers.create');

        return view('finance.customer-invoices.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.customers.create');

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50|unique:finance_customer_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'customer_id' => 'nullable|exists:finance_customers,id',
            'tax_id' => 'nullable|exists:finance_taxes,id',
            'subtotal' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $invoice = $this->invoiceService->createInvoice($validated);

        return redirect()
            ->route('finance.customer-invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully');
    }

    public function show(string $id)
    {
        $this->checkPermission('finance.customers.view');

        $invoice = CustomerInvoice::with('customer')->findOrFail($id);

        return view('finance.customer-invoices.show', compact('invoice'));
    }
}
