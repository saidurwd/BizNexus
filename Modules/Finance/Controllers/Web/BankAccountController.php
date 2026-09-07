<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\BankAccount;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $bankAccounts = BankAccount::with(['currency', 'glAccount'])
            ->where('company_id', $companyId)
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get();

        return view('finance.bank-accounts.index', compact('bankAccounts'));
    }

    public function create()
    {
        $glOptions = \Modules\Finance\Models\Account::where('is_postable', true)
            ->where('account_code', 'like', '1120%')
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        $currencyOptions = \Modules\Core\Models\Currency::orderBy('code')->get(['id', 'code', 'name']);

        return view('finance.bank-accounts.create', compact('glOptions', 'currencyOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'currency_id' => 'required|exists:currencies,id',
            'gl_account_id' => 'required|exists:accounts,id',
            'opening_balance' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['current_balance'] = $validated['opening_balance'];
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        BankAccount::create($validated);

        return redirect()->route('finance.bank-accounts.index')
            ->with('success', 'Bank account created successfully.');
    }

    public function edit(int $id)
    {
        $bankAccount = BankAccount::findOrFail($id);

        $glOptions = \Modules\Finance\Models\Account::where('is_postable', true)
            ->where('account_code', 'like', '1120%')
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        $currencyOptions = \Modules\Core\Models\Currency::orderBy('code')->get(['id', 'code', 'name']);

        return view('finance.bank-accounts.edit', compact('bankAccount', 'glOptions', 'currencyOptions'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $bankAccount = BankAccount::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'currency_id' => 'required|exists:currencies,id',
            'gl_account_id' => 'required|exists:accounts,id',
            'opening_balance' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['updated_by'] = auth()->id();

        $bankAccount->update($validated);

        return redirect()->route('finance.bank-accounts.index')
            ->with('success', 'Bank account updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $bankAccount = BankAccount::findOrFail($id);
        $bankAccount->delete();

        return redirect()->route('finance.bank-accounts.index')
            ->with('success', 'Bank account deleted successfully.');
    }
}
