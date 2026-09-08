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
            'receipt_account_id' => 'required|exists:finance_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payer_type' => 'required|in:CUSTOMER,SUPPLIER,OTHER',
            'payer_name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ]);

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
}
