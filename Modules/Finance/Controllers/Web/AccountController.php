<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Account;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class AccountController extends Controller
{
    public function __construct(
        protected ChartOfAccountsService $chartOfAccounts,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.accounts.view');

        $companyId = $this->getActiveCompanyId();
        $tree = $this->chartOfAccounts->getAccountTree($companyId);

        $accounts = $this->flattenTree($tree);

        return view('finance.accounts.index', [
            'accounts' => $accounts,
        ]);
    }

    public function create()
    {
        $this->checkPermission('finance.accounts.create');

        $companyId = $this->getActiveCompanyId();
        $tree = $this->chartOfAccounts->getAccountTree($companyId);
        $parentAccounts = $this->flattenTree($tree);

        return view('finance.accounts.create', compact('parentAccounts'));
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

    protected function flattenTree(array $tree, int $level = 0, ?int $parentId = null): array
    {
        $flat = [];

        foreach ($tree as $node) {
            $node['level'] = $level;
            $node['indent'] = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
            $flat[] = $node;

            if (!empty($node['children'])) {
                $flat = array_merge($flat, $this->flattenTree($node['children'], $level + 1, $node['id']));
            }
        }

        return $flat;
    }
}
