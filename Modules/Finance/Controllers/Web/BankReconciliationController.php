<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankReconciliation;

class BankReconciliationController extends Controller
{
    public function index()
    {
        $reconciliations = BankReconciliation::with(['bankAccount', 'reconciledBy'])
            ->orderByDesc('statement_date')
            ->get();

        return view('finance.bank-reconciliation.index', compact('reconciliations'));
    }

    public function create()
    {
        $bankAccounts = BankAccount::where('status', 'active')->get();

        return view('finance.bank-reconciliation.create', compact('bankAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric|min:0',
            'book_balance' => 'required|numeric|min:0',
        ]);

        $reconciliation = new BankReconciliation($validated);
        $reconciliation->company_id = 1;
        $reconciliation->calculateDifference();
        $reconciliation->save();

        return redirect()->route('finance.bank-reconciliation.index')
            ->with('success', 'Bank reconciliation created successfully.');
    }
}
