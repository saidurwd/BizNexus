<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Account;
use Modules\Finance\Services\ChartOfAccountsService;

class AccountController extends Controller
{
    public function __construct(
        protected ChartOfAccountsService $chartOfAccounts
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $accounts = Account::where('company_id', $companyId)
            ->when($request->get('type'), fn($q, $type) => $q->where('account_type', $type))
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('account_code')
            ->get();

        return view('finance.accounts.index', [
            'accounts' => $accounts,
        ]);
    }

    public function create()
    {
        return view('finance.accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'parent_id' => 'nullable|exists:accounts,id',
            'account_code' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'normal_balance' => 'nullable|in:DEBIT,CREDIT',
            'status' => 'nullable|in:active,inactive',
        ]);

        $account = $this->chartOfAccounts->create($validated);

        return redirect()->route('finance.accounts.index')->with('success', 'Account created successfully.');
    }

    public function show(int $id)
    {
        $account = Account::with(['parent', 'journalLines' => fn($q) => $q->limit(10)])
            ->findOrFail($id);

        return view('finance.accounts.show', [
            'account' => $account,
        ]);
    }

    public function edit(int $id)
    {
        $account = Account::findOrFail($id);

        return view('finance.accounts.edit', [
            'account' => $account,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'account_name' => 'sometimes|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $this->chartOfAccounts->update($account, $validated);

        return redirect()->route('finance.accounts.show', $id)->with('success', 'Account updated successfully.');
    }

    public function destroy(int $id)
    {
        $account = Account::findOrFail($id);

        try {
            $this->chartOfAccounts->delete($account);
            return redirect()->route('finance.accounts.index')->with('success', 'Account deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
