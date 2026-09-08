<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Requests\StoreBudgetRequest;
use Modules\Finance\Requests\UpdateBudgetRequest;
use Modules\Finance\Models\Budget;
use Modules\Finance\Models\BudgetLine;
use Modules\Finance\Models\Account;
use Modules\Finance\Services\BudgetService;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.budgets.view');

        $budgets = Budget::with('fiscalYear')
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
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

    public function store(StoreBudgetRequest $request)
    {
        $this->checkPermission('finance.budgets.create');

        $validated = $request->validated();

        $companyId = $this->getActiveCompanyId();

        $existing = Budget::where('company_id', $companyId)
            ->where('fiscal_year_id', $validated['fiscal_year_id'])
            ->whereIn('status', [Budget::STATUS_DRAFT, Budget::STATUS_SUBMITTED, Budget::STATUS_APPROVED])
            ->exists();

        if ($existing) {
            return back()->with('error', 'A budget for this fiscal year already exists.')->withInput();
        }

        $budget = Budget::create(array_merge($validated, [
            'company_id' => $companyId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        return redirect()
            ->route('finance.budgets.show', $budget->id)
            ->with('success', 'Budget created successfully');
    }

    public function show(string $id)
    {
        $this->checkPermission('finance.budgets.view');

        $budget = $this->budgetService->getBudgetById($id) ?? Budget::with(['fiscalYear', 'lines.account', 'lines.costCenter'])->findOrFail($id);

        return view('finance.budgets.show', compact('budget'));
    }

    public function edit(string $id)
    {
        $this->checkPermission('finance.budgets.update');

        $budget = Budget::findOrFail($id);

        if (!$budget->isDraft()) {
            return redirect()->route('finance.budgets.show', $budget->id)
                ->with('error', 'Only draft budgets can be edited.');
        }

        $fiscalYears = FiscalYear::orderBy('start_date')->get();

        return view('finance.budgets.edit', compact('budget', 'fiscalYears'));
    }

    public function update(UpdateBudgetRequest $request, string $id)
    {
        $this->checkPermission('finance.budgets.update');

        $budget = Budget::findOrFail($id);

        if (!$budget->isDraft()) {
            return redirect()->route('finance.budgets.show', $budget->id)
                ->with('error', 'Only draft budgets can be edited.');
        }

        $validated = $request->validated();

        $budget->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        return redirect()
            ->route('finance.budgets.show', $budget->id)
            ->with('success', 'Budget updated successfully');
    }

    public function destroy(string $id)
    {
        $this->checkPermission('finance.budgets.delete');

        $budget = Budget::findOrFail($id);

        if (!$budget->isDraft()) {
            return redirect()->route('finance.budgets.index')
                ->with('error', 'Only draft budgets can be deleted.');
        }

        $budget->delete();

        return redirect()
            ->route('finance.budgets.index')
            ->with('success', 'Budget deleted successfully');
    }

    public function submit(string $id)
    {
        $this->checkPermission('finance.budgets.approve');

        $budget = Budget::findOrFail($id);

        if (!$budget->isDraft()) {
            return redirect()->route('finance.budgets.show', $budget->id)
                ->with('error', 'Only draft budgets can be submitted.');
        }

        $budget->update([
            'status' => Budget::STATUS_SUBMITTED,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('finance.budgets.show', $budget->id)
            ->with('success', 'Budget submitted for approval.');
    }

    public function approve(string $id)
    {
        $this->checkPermission('finance.budgets.approve');

        $budget = Budget::with('lines')->findOrFail($id);

        if ($budget->status !== Budget::STATUS_SUBMITTED) {
            return redirect()->route('finance.budgets.show', $budget->id)
                ->with('error', 'Only submitted budgets can be approved.');
        }

        if ($budget->lines->isEmpty()) {
            return redirect()->route('finance.budgets.show', $budget->id)
                ->with('error', 'Cannot approve a budget with no lines.');
        }

        $budget->update([
            'status' => Budget::STATUS_APPROVED,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('finance.budgets.show', $budget->id)
            ->with('success', 'Budget approved successfully.');
    }

    public function reject(Request $request, string $id)
    {
        $this->checkPermission('finance.budgets.approve');

        $budget = Budget::findOrFail($id);

        if ($budget->status !== Budget::STATUS_SUBMITTED) {
            return redirect()->route('finance.budgets.show', $budget->id)
                ->with('error', 'Only submitted budgets can be rejected.');
        }

        $budget->update([
            'status' => Budget::STATUS_REJECTED,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('finance.budgets.show', $budget->id)
            ->with('success', 'Budget rejected.');
    }
}
