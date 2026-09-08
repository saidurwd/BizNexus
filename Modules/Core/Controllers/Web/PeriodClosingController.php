<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Services\AccountingPeriodService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Models\FiscalPeriod;

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
        $period = FiscalPeriod::findOrFail($id);
        $userId = auth()->id();

        $this->periodService->closePeriod($id, $userId);

        return redirect()->route('core.periods.index')
            ->with('success', "Period '{$period->period_name}' closed successfully.");
    }

    public function reopenPeriod(int $id)
    {
        $this->periodService->reopenPeriod($id, auth()->id());

        $period = FiscalPeriod::findOrFail($id);

        return redirect()->route('core.periods.index')
            ->with('success', "Period '{$period->period_name}' reopened successfully.");
    }

    public function lockPeriod(int $id)
    {
        $this->periodService->lockPeriod($id);

        $period = FiscalPeriod::findOrFail($id);

        return redirect()->route('core.periods.index')
            ->with('success', "Period '{$period->period_name}' locked successfully.");
    }

    public function validatePeriod(int $id)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $period = FiscalPeriod::findOrFail($id);

        if ($period->fiscalYear->company_id !== $companyId) {
            abort(403, 'Access denied');
        }

        if ($period->status !== 'OPEN') {
            return back()->with('error', 'Only open periods can be validated.');
        }

        $hasUnpostedJournals = \Modules\Finance\Models\Journal::where('fiscal_period_id', $id)
            ->whereIn('status', ['DRAFT', 'SUBMITTED', 'APPROVED'])
            ->exists();

        if ($hasUnpostedJournals) {
            return back()->with('error', 'Cannot close period with unposted journals.');
        }

        return back()->with('success', "Period '{$period->period_name}' is valid for closing.");
    }
}
