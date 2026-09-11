<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Finance\Models\Account;
use Modules\Finance\Services\ChartOfAccountsService;
use Modules\Core\Services\CompanyContextService;

class AccountController extends Controller
{
    public function __construct(
        protected ChartOfAccountsService $chartOfAccounts,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $accounts = Account::where('company_id', $companyId)
            ->when($request->get('type'), fn($q, $type) => $q->where('account_type', $type))
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('account_code')
            ->get();

        return $this->successResponse($accounts);
    }

    public function tree(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $tree = $this->chartOfAccounts->getAccountTree($companyId);

        return $this->successResponse($tree);
    }

    public function show(int $id)
    {
        $account = Account::with(['parent', 'currency', 'journalLines' => function ($q) {
            $q->limit(10)->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        return $this->successResponse($account);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'parent_id' => 'nullable|exists:accounts,id',
            'account_code' => 'required|string|max:50|unique:accounts,account_code,NULL,id,company_id,' . $request->company_id,
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'account_category_id' => 'nullable|exists:account_categories,id',
            'normal_balance' => 'nullable|in:DEBIT,CREDIT',
            'currency_id' => 'nullable|exists:currencies,id',
            'status' => 'nullable|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $account = $this->chartOfAccounts->create($validated);

        return $this->successResponse($account, 'Account created successfully', 201);
    }

    public function update(Request $request, int $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'account_name' => 'sometimes|string|max:255',
            'account_category_id' => 'nullable|exists:account_categories,id',
            'currency_id' => 'nullable|exists:currencies,id',
            'status' => 'nullable|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $account = $this->chartOfAccounts->update($account, $validated);

        return $this->successResponse($account, 'Account updated successfully');
    }

    public function destroy(int $id)
    {
        $account = Account::findOrFail($id);

        try {
            $this->chartOfAccounts->delete($account);
            return $this->successResponse(null, 'Account deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function statement(Request $request, int $id)
    {
        $account = Account::findOrFail($id);

        $startDate = $request->get('start_date') ? \Carbon\Carbon::parse($request->get('start_date')) : null;
        $endDate = $request->get('end_date') ? \Carbon\Carbon::parse($request->get('end_date')) : null;

        $statement = app(\Modules\Finance\Services\LedgerService::class)
            ->getAccountStatement($id, $startDate, $endDate, $request->get('fiscal_period_id'));

        return $this->successResponse($statement);
    }
}
