<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Journal;
use Modules\Finance\Services\JournalService;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\FinancialReportService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class JournalController extends Controller
{
    public function __construct(
        protected JournalService $journalService,
        protected LedgerService $ledgerService,
        protected FinancialReportService $reportService,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.journals.view');

        $journals = Journal::with(['lines.account', 'fiscalPeriod'])
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('journal_date', 'desc')
            ->paginate(20);

        return view('finance.journals.index', [
            'journals' => $journals,
        ]);
    }

    public function create()
    {
        $this->checkPermission('finance.journals.create');

        $companyId = $this->getActiveCompanyId();
        $accounts = \Modules\Finance\Models\Account::postable()
            ->where('company_id', $companyId)
            ->orderByRaw("CAST(account_code AS UNSIGNED)")
            ->get(['id', 'account_code', 'account_name']);

        $fiscalPeriods = \Modules\Core\Models\FiscalPeriod::whereHas('fiscalYear', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->where('status', 'OPEN')->orderBy('period_number')->get();

        $costCenters = \Modules\Core\Models\CostCenter::where('company_id', $companyId)->where('status', 'active')->get();
        $departments = \Modules\Core\Models\Department::where('company_id', $companyId)->where('status', 'active')->get();
        $branches = \Modules\Core\Models\Branch::where('company_id', $companyId)->where('status', 'active')->get();
        $businessUnits = \Modules\Core\Models\BusinessUnit::where('company_id', $companyId)->where('status', 'active')->get();
        $projects = \Modules\Core\Models\Project::where('company_id', $companyId)->where('status', 'active')->get();
        $taxes = \Modules\Finance\Models\Tax::where('company_id', $companyId)->where('status', 'active')->get();

        return view('finance.journals.create', compact('accounts', 'companyId', 'fiscalPeriods', 'costCenters', 'departments', 'branches', 'businessUnits', 'projects', 'taxes'));
    }

    public function edit(int $id)
    {
        $this->checkPermission('finance.journals.update');

        $journal = Journal::with('lines.account')->findOrFail($id);

        if (!$journal->isDraft()) {
            return redirect()->route('finance.journals.show', $journal->id)
                ->with('error', 'Only draft journals can be edited.');
        }

        $companyId = $this->getActiveCompanyId();
        $accounts = \Modules\Finance\Models\Account::postable()
            ->where('company_id', $companyId)
            ->orderByRaw("CAST(account_code AS UNSIGNED)")
            ->get(['id', 'account_code', 'account_name']);

        return view('finance.journals.edit', compact('journal', 'accounts', 'companyId'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.journals.create');

        $validated = $request->validate([            'journal_date' => 'required|date',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();

        try {
            $journal = $this->journalService->create($validated);
            return redirect()->route('finance.journals.show', $journal->id)->with('success', 'Journal created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, int $id)
    {
        $this->checkPermission('finance.journals.update');

        $journal = Journal::findOrFail($id);

        if (!$journal->isDraft()) {
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
        $this->checkPermission('finance.journals.view');

        $journal = Journal::with(['lines.account', 'fiscalPeriod', 'postedBy', 'createdBy'])
            ->findOrFail($id);

        return view('finance.journals.show', [
            'journal' => $journal,
        ]);
    }

    public function generalLedger(Request $request)
    {
        $this->checkPermission('finance.journals.view');

        $ledger = $this->ledgerService->getGeneralLedger(
            $this->getActiveCompanyId(),
            $request->get('start_date') ? \Carbon\Carbon::parse($request->get('start_date')) : null,
            $request->get('end_date') ? \Carbon\Carbon::parse($request->get('end_date')) : null
        );

        return view('finance::reports.general-ledger', [
            'ledger' => $ledger,
        ]);
    }

    public function destroy(int $id)
    {
        $this->checkPermission('finance.journals.delete');

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
        $this->checkPermission('finance.journals.approve');

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
        $this->checkPermission('finance.journals.approve');

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
        $this->checkPermission('finance.journals.approve');

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
        $this->checkPermission('finance.journals.post');

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
        $this->checkPermission('finance.journals.post');

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
        $this->checkPermission('finance.journals.delete');

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
        $this->checkPermission('finance.journals.update');

        $journal = Journal::findOrFail($id);

        if (!$journal->isDraft()) {
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
        $this->checkPermission('finance.journals.update');

        $line = \Modules\Finance\Models\JournalLine::findOrFail($lineId);
        $journal = $line->journal;

        if (!$journal->isDraft()) {
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
        $this->checkPermission('finance.journals.update');

        $line = \Modules\Finance\Models\JournalLine::findOrFail($lineId);
        $journal = $line->journal;

        if (!$journal->isDraft()) {
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
