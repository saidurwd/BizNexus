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
            ->orderByRaw("CAST(account_code AS UNSIGNED)")
            ->get(['id', 'account_code', 'account_name']);

        return view('finance.journals.create', compact('accounts', 'companyId'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.journals.create');

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'journal_date' => 'required|date',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        try {
            $journal = $this->journalService->create($validated);
            return redirect()->route('finance.journals.show', $journal->id)->with('success', 'Journal created successfully.');
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
}
