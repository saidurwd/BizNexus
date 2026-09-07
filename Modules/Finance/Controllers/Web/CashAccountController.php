<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\CashAccount;

class CashAccountController extends Controller
{
    public function index()
    {
        $cashAccounts = CashAccount::with('glAccount')
            ->orderBy('code')
            ->get();

        return view('finance::cash-accounts.index', compact('cashAccounts'));
    }

    public function create()
    {
        $glOptions = \Modules\Finance\Models\Account::where('is_postable', true)
            ->whereIn('account_code', ['1110', '1120'])
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        return view('finance::cash-accounts.create', compact('glOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:cash_accounts,code',
            'name' => 'required|string|max:255',
            'gl_account_id' => 'required|exists:accounts,id',
            'account_type' => 'required|in:CASH,PETTY_CASH,BANK',
            'currency_code' => 'required|string|size:3',
            'opening_balance' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $validated['company_id'] = 1;
        $validated['current_balance'] = $validated['opening_balance'];
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        CashAccount::create($validated);

        return redirect()->route('finance.cash-accounts.index')
            ->with('success', 'Cash account created successfully.');
    }

    public function edit(int $id)
    {
        $cashAccount = CashAccount::findOrFail($id);
        $glOptions = \Modules\Finance\Models\Account::where('is_postable', true)
            ->whereIn('account_code', ['1110', '1120'])
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        return view('finance::cash-accounts.edit', compact('cashAccount', 'glOptions'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $cashAccount = CashAccount::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:cash_accounts,code,' . $id,
            'name' => 'required|string|max:255',
            'gl_account_id' => 'required|exists:accounts,id',
            'account_type' => 'required|in:CASH,PETTY_CASH,BANK',
            'currency_code' => 'required|string|size:3',
            'opening_balance' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $cashAccount->update($validated);

        return redirect()->route('finance.cash-accounts.index')
            ->with('success', 'Cash account updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $cashAccount = CashAccount::findOrFail($id);
        $cashAccount->delete();

        return redirect()->route('finance.cash-accounts.index')
            ->with('success', 'Cash account deleted successfully.');
    }
}
