<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Services\CustomerInvoiceService;
use Modules\Finance\Services\ReceiptService;
use Modules\Core\Services\CompanyContextService;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerInvoiceService $customerInvoiceService,
        protected ReceiptService $receiptService,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $customers = Customer::where('company_id', $companyId)
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->get();

        return $this->successResponse($customers);
    }

    public function show(int $id)
    {
        $customer = Customer::with(['currency', 'receivableAccount'])
            ->findOrFail($id);

        return $this->successResponse($customer);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'currency_id' => 'nullable|exists:currencies,id',
            'receivable_account_id' => 'nullable|exists:accounts,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $customer = Customer::create($validated);

        return $this->successResponse($customer, 'Customer created successfully', 201);
    }

    public function update(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'currency_id' => 'nullable|exists:currencies,id',
            'receivable_account_id' => 'nullable|exists:accounts,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $customer->update($validated);

        return $this->successResponse($customer->fresh(), 'Customer updated successfully');
    }

    public function invoices(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $invoices = CustomerInvoice::where('customer_id', $id)
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('invoice_date', 'desc')
            ->get();

        return $this->successResponse($invoices);
    }

    public function outstandingInvoices(int $id)
    {
        $customer = Customer::findOrFail($id);

        $outstanding = $this->customerInvoiceService->getOutstandingInvoices($id);

        return $this->successResponse($outstanding);
    }

    public function aging(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);

        $aging = $this->receiptService->getARAging($customer->company_id, $id);

        return $this->successResponse($aging);
    }
}
