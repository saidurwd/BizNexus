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
        $this->checkPermission('finance.accounts.view');

        $accounts = Account::when($request->get('type'), fn($q, $type) => $q->where('account_type', $type))
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('account_code')
            ->get();

        return view('finance.accounts.index', [
            'accounts' => $accounts,
        ]);
    }

    public function create()
    {
        $this->checkPermission('finance.accounts.create');

        return view('finance.accounts.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.accounts.create');

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:accounts,id',
            'account_code' => 'required|string|max:50|unique:accounts,account_code',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|string',
            'account_category' => 'nullable|string',
            'normal_balance' => 'required|in:DEBIT,CREDIT',
            'level' => 'required|integer|min:1',
            'is_group' => 'boolean',
            'is_postable' => 'boolean',
            'currency_id' => 'nullable|exists:currencies,id',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        Account::create($validated);

        return redirect()->route('finance.accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function show(int $id)
    {
        $this->checkPermission('finance.accounts.view');

        $account = Account::findOrFail($id);

        return view('finance.accounts.show', compact('account'));
    }

    public function edit(int $id)
    {
        $this->checkPermission('finance.accounts.update');

        $account = Account::findOrFail($id);

        return view('finance.accounts.edit', compact('account'));
    }

    public function update(Request $request, int $id)
    {
        $this->checkPermission('finance.accounts.update');

        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:accounts,id',
            'account_code' => 'required|string|max:50|unique:accounts,account_code,' . $id,
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|string',
            'account_category' => 'nullable|string',
            'normal_balance' => 'required|in:DEBIT,CREDIT',
            'level' => 'required|integer|min:1',
            'is_group' => 'boolean',
            'is_postable' => 'boolean',
            'currency_id' => 'nullable|exists:currencies,id',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $account->update($validated);

        return redirect()->route('finance.accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->checkPermission('finance.accounts.delete');

        $account = Account::findOrFail($id);
        $account->delete();

        return redirect()->route('finance.accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
