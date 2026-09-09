<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\CustomerReceipt;
use Modules\Finance\Services\ReceiptService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class ReceiptController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.customers.view');

        $receipts = CustomerReceipt::with(['customer', 'bankAccount'])
            ->orderBy('receipt_date', 'desc')
            ->paginate(20);

        return view('finance.receipts.index', compact('receipts'));
    }

    public function create()
    {
        $this->checkPermission('finance.customers.create');

        return view('finance.receipts.create');
    }

    public function bankReceipts(Request $request)
    {
        $this->checkPermission('finance.customers.view');

        $receipts = CustomerReceipt::with(['customer', 'bankAccount'])
            ->whereNotNull('bank_account_id')
            ->orderBy('receipt_date', 'desc')
            ->paginate(20);

        return view('finance.receipts.index', [
            'receipts' => $receipts,
            'isBankReceipts' => true,
        ]);
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.customers.create');

        $validated = $request->validate([
            'receipt_date' => 'required|date',
            'receipt_type' => 'required|in:CASH,BANK_TRANSFER,CHECK',
            'receipt_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payer_type' => 'required|in:CUSTOMER,SUPPLIER,OTHER',
            'payer_name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',        ]);

        $validated['company_id'] = $this->getActiveCompanyId();

        $receipt = $this->receiptService->createReceipt($validated);

        return redirect()
            ->route('finance.receipts.show', $receipt->id)
            ->with('success', 'Receipt created successfully');
    }

    public function show(string $id)
    {
        $receipt = CustomerReceipt::with(['customer', 'bankAccount'])->findOrFail($id);

        return view('finance.receipts.show', compact('receipt'));
    }

    public function submit(string $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        if (!$receipt->isDraft()) {
            return back()->with('error', 'Only draft receipts can be submitted.');
        }

        $receipt = $this->receiptService->submitReceipt($receipt);

        return back()->with('success', 'Receipt submitted successfully.');
    }

    public function approve(string $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        if (!$receipt->isSubmitted()) {
            return back()->with('error', 'Only submitted receipts can be approved.');
        }

        $receipt = $this->receiptService->approveReceipt($receipt);

        return back()->with('success', 'Receipt approved successfully.');
    }

    public function reject(Request $request, string $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        if (!$receipt->isSubmitted()) {
            return back()->with('error', 'Only submitted receipts can be rejected.');
        }

        $receipt = $this->receiptService->rejectReceipt($receipt, $request->get('reason'));

        return back()->with('success', 'Receipt rejected.');
    }

    public function cancel(string $id)
    {
        $receipt = CustomerReceipt::findOrFail($id);

        if ($receipt->isPosted()) {
            return back()->with('error', 'Posted receipts cannot be cancelled directly.');
        }

        $receipt = $this->receiptService->cancelReceipt($receipt);

        return back()->with('success', 'Receipt cancelled.');
    }
}
