<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Receipt;
use Modules\Finance\Services\ReceiptService;

class ReceiptController extends Controller
{
    public function __construct(
        private ReceiptService $receiptService
    ) {}

    public function index(Request $request)
    {
        $receipts = Receipt::with('receiptAccount')
            ->orderBy('receipt_date', 'desc')
            ->paginate(20);

        return view('finance.receipts.index', compact('receipts'));
    }

    public function create()
    {
        return view('finance.receipts.create');
    }

    public function store(Request $request)
    {
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
        $receipt = Receipt::with('receiptAccount')->findOrFail($id);

        return view('finance.receipts.show', compact('receipt'));
    }
}
