<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\SupplierInvoiceService;
use Modules\Core\Services\CompanyContextService;

class SupplierInvoiceController extends Controller
{
    public function __construct(
        protected SupplierInvoiceService $supplierInvoiceService,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $invoices = SupplierInvoice::with(['supplier', 'currency'])
            ->where('company_id', $companyId)
            ->when($request->get('supplier_id'), fn($q, $id) => $q->where('supplier_id', $id))
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->get('start_date'), fn($q, $date) => $q->where('invoice_date', '>=', $date))
            ->when($request->get('end_date'), fn($q, $date) => $q->where('invoice_date', '<=', $date))
            ->orderBy('invoice_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($invoices, 'Invoices retrieved successfully');
    }

    public function show(int $id)
    {
        $invoice = SupplierInvoice::with(['supplier', 'lines.account', 'lines.tax', 'currency', 'journal'])
            ->findOrFail($id);

        return $this->successResponse($invoice);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'nullable|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax_id' => 'nullable|exists:taxes,id',
            'lines.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $invoice = $this->supplierInvoiceService->createInvoice($validated);
            return $this->successResponse($invoice, 'Invoice created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function post(int $id)
    {
        $invoice = SupplierInvoice::findOrFail($id);

        try {
            $invoice = $this->supplierInvoiceService->postInvoice($invoice);
            return $this->successResponse($invoice, 'Invoice posted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function cancel(int $id)
    {
        $invoice = SupplierInvoice::findOrFail($id);

        try {
            $invoice = $this->supplierInvoiceService->cancelInvoice($invoice);
            return $this->successResponse($invoice, 'Invoice cancelled');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
