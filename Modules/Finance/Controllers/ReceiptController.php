<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\CustomerReceipt;
use Modules\Finance\Services\ReceiptService;

class ReceiptController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $receipts = CustomerReceipt::with(['customer', 'bankAccount', 'currency'])
            ->where('company_id', $companyId)
            ->when($request->get('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->get('start_date'), fn ($q, $date) => $q->where('receipt_date', '>=', $date))
            ->when($request->get('end_date'), fn ($q, $date) => $q->where('receipt_date', '<=', $date))
            ->orderBy('receipt_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($receipts, 'Receipts retrieved successfully');
    }

    public function show(int $id)
    {
        $receipt = CustomerReceipt::with(['customer', 'bankAccount', 'currency', 'allocations.invoice'])
            ->findOrFail($id);

        return $this->successResponse($receipt);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:customers,id',
            'receipt_number' => 'nullable|string|max:50',
            'receipt_date' => 'required|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'receipt_method' => 'nullable|string|max:50',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => 'required|exists:customer_invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $receipt = $this->receiptService->createReceipt($validated);

            return $this->successResponse($receipt, 'Receipt created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function post(int $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        try {
            $receipt = $this->receiptService->postReceipt($receipt);

            return $this->successResponse($receipt, 'Receipt posted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function submit(int $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        try {
            $receipt = $this->receiptService->submitReceipt($receipt);

            return $this->successResponse($receipt, 'Receipt submitted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function approve(int $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        try {
            $receipt = $this->receiptService->approveReceipt($receipt);

            return $this->successResponse($receipt, 'Receipt approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function reject(Request $request, int $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        try {
            $receipt = $this->receiptService->rejectReceipt($receipt, $request->get('reason'));

            return $this->successResponse($receipt, 'Receipt rejected');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function update(Request $request, int $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        if (! $receipt->isDraft()) {
            return $this->errorResponse('Only draft receipts can be updated', 400);
        }

        $validated = $request->validate([
            'receipt_number' => 'required|string|max:50|unique:customer_receipts,receipt_number,'.$id,
            'receipt_date' => 'required|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'receipt_method' => 'nullable|string|max:50',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => 'required|exists:customer_invoices,id',
            'allocations.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $receipt = $this->receiptService->updateReceipt($receipt, $validated);

            return $this->successResponse($receipt, 'Receipt updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function cancel(int $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        try {
            $receipt = $this->receiptService->cancelReceipt($receipt);

            return $this->successResponse($receipt, 'Receipt cancelled');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function aging(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $aging = $this->receiptService->getARAging($companyId);

        return $this->successResponse($aging);
    }
}
