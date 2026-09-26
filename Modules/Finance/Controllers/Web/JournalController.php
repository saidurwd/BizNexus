<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\Request;
use Modules\Core\Models\Branch;
use Modules\Core\Models\BusinessUnit;
use Modules\Core\Models\Company;
use Modules\Core\Models\CostCenter;
use Modules\Core\Models\Department;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Tax;
use Modules\Finance\Services\JournalService;

class JournalController extends Controller
{
    use FiltersDocumentLists;

    public function __construct(
        protected JournalService $journalService,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {

        $companyId = $this->getActiveCompanyId();
        $company = $companyId ? Company::find($companyId) : null;

        $query = Journal::with(['lines.account', 'fiscalPeriod']);
        $filters = $this->applyListFilters($query, $request, 'journal_date', ['journal_number', 'description']);
        $journals = $query
            ->orderByDesc('journal_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('finance.journals.index', [
            'journals' => $journals,
            'company' => $company,
            'filters' => $filters,
        ]);
    }

    public function create()
    {

        $companyId = $this->getActiveCompanyId();
        $accounts = Account::postable()
            ->where('company_id', $companyId)
            ->orderByRaw('CAST(account_code AS UNSIGNED)')
            ->get(['id', 'account_code', 'account_name']);

        $fiscalPeriods = FiscalPeriod::whereHas('fiscalYear', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->where('status', 'OPEN')->orderBy('period_number')->get();

        $costCenters = CostCenter::where('company_id', $companyId)->where('status', 'active')->get();
        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();
        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->get();
        $businessUnits = BusinessUnit::where('company_id', $companyId)->where('status', 'active')->get();
        $taxes = Tax::where('company_id', $companyId)->where('status', 'active')->get();

        return view('finance.journals.create', compact('accounts', 'companyId', 'fiscalPeriods', 'costCenters', 'departments', 'branches', 'businessUnits', 'taxes'));
    }

    public function edit(int $id)
    {

        $journal = Journal::with('lines.account')
            ->findOrFail($id);

        if (! $journal->isDraft()) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', 'Only draft journals can be edited.');
        }

        $companyId = $this->getActiveCompanyId();
        $accounts = Account::postable()
            ->where('company_id', $companyId)
            ->orderByRaw('CAST(account_code AS UNSIGNED)')
            ->get(['id', 'account_code', 'account_name']);

        return view('finance.journals.edit', compact('journal', 'accounts', 'companyId'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate(['journal_date' => 'required|date',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();
        $validated['branch_id'] = $this->getActiveBranchId();

        try {
            $journal = $this->journalService->create($validated);

            return redirect()->route('finance.journals.show', $journal->id)->with('success', 'Journal created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, int $id)
    {

        $journal = Journal::findOrFail($id);

        if (! $journal->isDraft()) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', 'Only draft journals can be updated.');
        }

        $validated = $request->validate([
            'journal_date' => 'required|date',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->journalService->update($journal, $validated);

            return redirect()->route('finance.journals.show', $journal->id)->with('success', 'Journal updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(int $id)
    {

        $journal = Journal::with(['lines.account', 'fiscalPeriod', 'postedBy', 'createdBy', 'branch'])
            ->findOrFail($id);

        return view('finance.journals.show', [
            'journal' => $journal,
        ]);
    }

    public function destroy(int $id)
    {

        $journal = Journal::findOrFail($id);

        if ($journal->status !== 'DRAFT') {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', 'Only draft journals can be deleted.');
        }

        $journal->delete();

        return redirect()->route('finance.journals.index')
            ->with('success', 'Journal deleted successfully.');
    }

    public function submit(int $id)
    {

        $journal = Journal::findOrFail($id);

        try {
            $this->journalService->submit($journal);

            return redirect()->route('finance.journals.show', $journal->id)
                ->with('success', 'Journal submitted for approval.');
        } catch (\Exception $e) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', $e->getMessage());
        }
    }

    public function approve(int $id)
    {

        $journal = Journal::findOrFail($id);

        try {
            $this->journalService->approve($journal);

            return redirect()->route('finance.journals.show', $journal->id)
                ->with('success', 'Journal approved successfully.');
        } catch (\Exception $e) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', $e->getMessage());
        }
    }

    public function reject(int $id)
    {

        $journal = Journal::findOrFail($id);

        try {
            $this->journalService->reject($journal);

            return redirect()->route('finance.journals.show', $journal->id)
                ->with('success', 'Journal rejected.');
        } catch (\Exception $e) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', $e->getMessage());
        }
    }

    public function post(int $id)
    {

        $journal = Journal::findOrFail($id);

        try {
            $this->journalService->post($journal);

            return redirect()->route('finance.journals.show', $journal->id)
                ->with('success', 'Journal posted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', $e->getMessage());
        }
    }

    public function reverse(int $id)
    {

        $journal = Journal::findOrFail($id);

        try {
            $this->journalService->reverse($journal);

            return redirect()->route('finance.journals.show', $journal->id)
                ->with('success', 'Journal reversed successfully.');
        } catch (\Exception $e) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(int $id)
    {

        $journal = Journal::findOrFail($id);

        try {
            $this->journalService->cancel($journal);

            return redirect()->route('finance.journals.show', $journal->id)
                ->with('success', 'Journal cancelled.');
        } catch (\Exception $e) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', $e->getMessage());
        }
    }

    public function addLine(Request $request, int $id)
    {

        $journal = Journal::findOrFail($id);

        if (! $journal->isDraft()) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', 'Can only add lines to draft journals.');
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'nullable|string|max:500',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'project_id' => 'nullable|exists:projects,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $this->journalService->addLine($journal, $validated);

            return redirect()->route('finance.journals.show', $journal->id)
                ->with('success', 'Line added successfully.');
        } catch (\Exception $e) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', $e->getMessage());
        }
    }

    public function updateLine(Request $request, int $lineId)
    {

        $line = JournalLine::findOrFail($lineId);
        $journal = $line->journal;

        if (! $journal->isDraft()) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', 'Can only update lines in draft journals.');
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'nullable|string|max:500',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'project_id' => 'nullable|exists:projects,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $this->journalService->updateLine($line, $validated);

            return redirect()->route('finance.journals.show', $journal->id)
                ->with('success', 'Line updated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', $e->getMessage());
        }
    }

    public function removeLine(int $lineId)
    {

        $line = JournalLine::findOrFail($lineId);
        $journal = $line->journal;

        if (! $journal->isDraft()) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', 'Can only remove lines from draft journals.');
        }

        try {
            $this->journalService->removeLine($line);

            return redirect()->route('finance.journals.show', $journal->id)
                ->with('success', 'Line removed successfully.');
        } catch (\Exception $e) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', $e->getMessage());
        }
    }
}
