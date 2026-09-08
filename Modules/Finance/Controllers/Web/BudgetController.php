<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Budget;
use Modules\Finance\Models\BudgetLine;
use Modules\Finance\Models\Account;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class BudgetController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.budgets.view');

        $budgets = Budget::with('fiscalYear')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('finance.budgets.index', compact('budgets'));
    }

    public function create()
    {
        $this->checkPermission('finance.budgets.create');

        $fiscalYears = FiscalYear::orderBy('start_date')->get();

        return view('finance.budgets.create', compact('fiscalYears'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.budgets.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'status' => 'required|in:DRAFT,APPROVED,ACTIVE,CLOSED',
        ]);

        $budget = Budget::create($validated);

        return redirect()
            ->route('finance.budgets.show', $budget->id)
            ->with('success', 'Budget created successfully');
    }

    public function show(string $id)
    {
        $this->checkPermission('finance.budgets.view');

        $budget = Budget::with(['fiscalYear', 'lines.account'])->findOrFail($id);

        return view('finance.budgets.show', compact('budget'));
    }

    public function edit(string $id)
    {
        $this->checkPermission('finance.budgets.update');

        $budget = Budget::findOrFail($id);
        $fiscalYears = FiscalYear::orderBy('start_date')->get();

        return view('finance.budgets.edit', compact('budget', 'fiscalYears'));
    }

    public function update(Request $request, string $id)
    {
        $this->checkPermission('finance.budgets.update');

        $budget = Budget::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:DRAFT,APPROVED,ACTIVE,CLOSED',
        ]);

        $budget->update($validated);

        return redirect()
            ->route('finance.budgets.show', $budget->id)
            ->with('success', 'Budget updated successfully');
    }

    public function destroy(string $id)
    {
        $this->checkPermission('finance.budgets.delete');

        $budget = Budget::findOrFail($id);
        $budget->delete();

        return redirect()
            ->route('finance.budgets.index')
            ->with('success', 'Budget deleted successfully');
    }
}
