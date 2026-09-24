<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\AccountingPeriodService;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Journal;

class PeriodClosingController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected AccountingPeriodService $periodService
    ) {}

    public function index()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $fiscalYears = FiscalYear::where('company_id', $companyId)
            ->with('periods')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('core.periods.index', compact('fiscalYears'));
    }

    public function closePeriod(int $id)
    {
        $period = $this->findCompanyPeriod($id);

        if ($this->hasUnpostedJournals($period)) {
            return back()->with('error', 'Cannot close period with unposted journals.');
        }

        $this->periodService->closePeriod($period->id, auth()->id());

        return redirect()->route('core.periods.index')
            ->with('success', "Period '{$period->period_name}' closed successfully.");
    }

    public function reopenPeriod(int $id)
    {
        $period = $this->findCompanyPeriod($id);

        $this->periodService->reopenPeriod($period->id, auth()->id());

        return redirect()->route('core.periods.index')
            ->with('success', "Period '{$period->period_name}' reopened successfully.");
    }

    public function lockPeriod(int $id)
    {
        $period = $this->findCompanyPeriod($id);

        $this->periodService->lockPeriod($period->id);

        return redirect()->route('core.periods.index')
            ->with('success', "Period '{$period->period_name}' locked successfully.");
    }

    public function validatePeriod(int $id)
    {
        $period = $this->findCompanyPeriod($id);

        if ($period->status !== 'OPEN') {
            return back()->with('error', 'Only open periods can be validated.');
        }

        if ($this->hasUnpostedJournals($period)) {
            return back()->with('error', 'Cannot close period with unposted journals.');
        }

        return back()->with('success', "Period '{$period->period_name}' is valid for closing.");
    }

    protected function findCompanyPeriod(int $id): FiscalPeriod
    {
        return FiscalPeriod::whereHas('fiscalYear', fn ($query) => $query->where('company_id', $this->companyContext->getActiveCompanyId()))
            ->findOrFail($id);
    }

    /**
     * Unposted journals are found by date because a journal only receives its fiscal period when it is posted.
     */
    protected function hasUnpostedJournals(FiscalPeriod $period): bool
    {
        return Journal::whereBetween('journal_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
            ->whereIn('status', [Journal::STATUS_DRAFT, Journal::STATUS_SUBMITTED, Journal::STATUS_APPROVED])
            ->exists();
    }
}
