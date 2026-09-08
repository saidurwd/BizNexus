<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\BankAccount;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class BankAccountController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.accounts.view');

        $bankAccounts = BankAccount::with(['currency', 'glAccount'])
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get();

        return view('finance.bank-accounts.index', compact('bankAccounts'));
    }

    public function create()
    {
        $this->checkPermission('finance.accounts.create');

        $companyId = $this->getActiveCompanyId();

        $glOptions = \Modules\Finance\Models\Account::where('is_postable', true)
            ->where('company_id', $companyId)
            ->where('account_code', 'like', '1120%')
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        $currencyOptions = \Modules\Core\Models\Currency::orderBy('code')->get(['id', 'code', 'name']);

        return view('finance.bank-accounts.create', compact('glOptions', 'currencyOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkPermission('finance.accounts.create');

        $validated = $request->validate([
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
        $this->checkPermission('finance.accounts.update');

        $bankAccount = BankAccount::findOrFail($id);

        $companyId = $this->getActiveCompanyId();
        $glOptions = \Modules\Finance\Models\Account::where('is_postable', true)
            ->where('company_id', $companyId)
            ->where('account_code', 'like', '1120%')
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        $currencyOptions = \Modules\Core\Models\Currency::orderBy('code')->get(['id', 'code', 'name']);

        return view('finance.bank-accounts.edit', compact('bankAccount', 'glOptions', 'currencyOptions'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->checkPermission('finance.accounts.update');

        $bankAccount = BankAccount::findOrFail($id);

        $validated = $request->validate([
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
        $this->checkPermission('finance.accounts.delete');

        $bankAccount = BankAccount::findOrFail($id);
        $bankAccount->delete();

        return redirect()->route('finance.bank-accounts.index')
            ->with('success', 'Bank account deleted successfully.');
    }
}
