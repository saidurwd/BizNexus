<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Finance\Models\Budget;
use Modules\Finance\Models\BudgetLine;
use Modules\Finance\Models\Account;
use Modules\Core\Models\CostCenter;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class BudgetLineController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function store(Request $request, string $budgetId)
    {
        $this->checkPermission('finance.budgets.update');

        $budget = Budget::findOrFail($budgetId);

        if (!$budget->isDraft()) {
            return redirect()->route('finance.budgets.show', $budgetId)
                ->with('error', 'Budget lines can only be modified in draft status.');
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'period' => 'required|integer|min:1|max:12',
            'budget_amount' => 'required|numeric|min:0',
        ]);

        $validated['budget_id'] = $budgetId;

        BudgetLine::create($validated);

        return redirect()->route('finance.budgets.show', $budgetId)
            ->with('success', 'Budget line added successfully.');
    }

    public function update(Request $request, string $budgetId, string $lineId)
    {
        $this->checkPermission('finance.budgets.update');

        $budget = Budget::findOrFail($budgetId);

        if (!$budget->isDraft()) {
            return redirect()->route('finance.budgets.show', $budgetId)
                ->with('error', 'Budget lines can only be modified in draft status.');
        }

        $line = BudgetLine::where('budget_id', $budgetId)->findOrFail($lineId);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'period' => 'required|integer|min:1|max:12',
            'budget_amount' => 'required|numeric|min:0',
        ]);

        $line->update($validated);

        return redirect()->route('finance.budgets.show', $budgetId)
            ->with('success', 'Budget line updated successfully.');
    }

    public function destroy(string $budgetId, string $lineId)
    {
        $this->checkPermission('finance.budgets.update');

        $budget = Budget::findOrFail($budgetId);

        if (!$budget->isDraft()) {
            return redirect()->route('finance.budgets.show', $budgetId)
                ->with('error', 'Budget lines can only be modified in draft status.');
        }

        $line = BudgetLine::where('budget_id', $budgetId)->findOrFail($lineId);
        $line->delete();

        return redirect()->route('finance.budgets.show', $budgetId)
            ->with('success', 'Budget line deleted successfully.');
    }

    public function accounts(Request $request)
    {
        $this->checkPermission('finance.budgets.view');

        $search = $request->get('q', '');

        $accounts = Account::where('company_id', $this->getActiveCompanyId())
            ->where(function ($query) use ($search) {
                $query->where('account_code', 'like', "%{$search}%")
                      ->orWhere('account_name', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'account_code', 'account_name']);

        return response()->json($accounts);
    }

    public function costCenters(Request $request)
    {
        $this->checkPermission('finance.budgets.view');

        $search = $request->get('q', '');

        $costCenters = CostCenter::where('company_id', $this->getActiveCompanyId())
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'code', 'name']);

        return response()->json($costCenters);
    }
}
