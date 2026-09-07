<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Finance\Models\SupplierPayment;
use Modules\Finance\Services\PaymentService;
use Modules\Core\Services\CompanyContextService;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $payments = SupplierPayment::with(['supplier', 'bankAccount', 'currency'])
            ->where('company_id', $companyId)
            ->when($request->get('supplier_id'), fn($q, $id) => $q->where('supplier_id', $id))
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->get('start_date'), fn($q, $date) => $q->where('payment_date', '>=', $date))
            ->when($request->get('end_date'), fn($q, $date) => $q->where('payment_date', '<=', $date))
            ->orderBy('payment_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($payments, 'Payments retrieved successfully');
    }

    public function show(int $id)
    {
        $payment = SupplierPayment::with(['supplier', 'bankAccount', 'currency', 'allocations.invoice'])
            ->findOrFail($id);

        return $this->successResponse($payment);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_number' => 'nullable|string|max:50',
            'payment_date' => 'required|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => 'required|exists:supplier_invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $payment = $this->paymentService->createPayment($validated);
            return $this->successResponse($payment, 'Payment created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function post(int $id)
    {
        $payment = SupplierPayment::findOrFail($id);

        try {
            $payment = $this->paymentService->postPayment($payment);
            return $this->successResponse($payment, 'Payment posted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function aging(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $aging = $this->paymentService->getAPAging($companyId);

        return $this->successResponse($aging);
    }
}
