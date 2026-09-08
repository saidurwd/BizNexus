<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankTransaction;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class BankReceiptController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index()
    {
        $this->checkPermission('finance.accounts.view');

        $receipts = BankTransaction::with(['bankAccount'])
            ->where('transaction_type', 'DEPOSIT')
            ->orderByDesc('transaction_date')
            ->get();

        return view('finance.bank-receipts.index', compact('receipts'));
    }

    public function create()
    {
        $this->checkPermission('finance.accounts.create');

        $bankAccounts = BankAccount::where('status', 'active')->get();

        return view('finance.bank-receipts.create', compact('bankAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkPermission('finance.accounts.create');

        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        $validated['transaction_number'] = 'BR-' . now()->format('Ymd') . '-' . str_pad((string) (BankTransaction::count() + 1), 4, '0', STR_PAD_LEFT);
        $validated['transaction_type'] = 'DEPOSIT';
        $validated['status'] = BankTransaction::STATUS_COMPLETED;
        $validated['created_by'] = auth()->id();

        BankTransaction::create($validated);

        return redirect()->route('finance.bank-receipts.index')
            ->with('success', 'Bank receipt created successfully.');
    }
}
