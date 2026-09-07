<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\SupplierInvoiceService;
use Modules\Finance\Services\PaymentService;
use Modules\Core\Services\CompanyContextService;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierInvoiceService $supplierInvoiceService,
        protected PaymentService $paymentService,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $suppliers = Supplier::where('company_id', $companyId)
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->get();

        return $this->successResponse($suppliers);
    }

    public function show(int $id)
    {
        $supplier = Supplier::with(['currency', 'payableAccount'])
            ->findOrFail($id);

        return $this->successResponse($supplier);
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
            'currency_id' => 'nullable|exists:currencies,id',
            'payable_account_id' => 'nullable|exists:accounts,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $supplier = Supplier::create($validated);

        return $this->successResponse($supplier, 'Supplier created successfully', 201);
    }

    public function update(Request $request, int $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'currency_id' => 'nullable|exists:currencies,id',
            'payable_account_id' => 'nullable|exists:accounts,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $supplier->update($validated);

        return $this->successResponse($supplier->fresh(), 'Supplier updated successfully');
    }

    public function invoices(Request $request, int $id)
    {
        $supplier = Supplier::findOrFail($id);

        $invoices = SupplierInvoice::where('supplier_id', $id)
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('invoice_date', 'desc')
            ->get();

        return $this->successResponse($invoices);
    }

    public function outstandingInvoices(int $id)
    {
        $supplier = Supplier::findOrFail($id);

        $outstanding = $this->supplierInvoiceService->getOutstandingInvoices($id);

        return $this->successResponse($outstanding);
    }

    public function aging(Request $request, int $id)
    {
        $supplier = Supplier::findOrFail($id);

        $aging = $this->paymentService->getAPAging($supplier->company_id, $id);

        return $this->successResponse($aging);
    }
}
