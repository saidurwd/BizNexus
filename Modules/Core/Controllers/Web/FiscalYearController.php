<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Enums\FiscalCalendarPattern;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\AccountingPeriodService;
use Modules\Core\Services\CompanyContextService;

class FiscalYearController extends Controller
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

        return view('core.fiscal-years.index', compact('fiscalYears'));
    }

    public function create()
    {
        return view('core.fiscal-years.create');
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date|before:'.Carbon::parse($request->input('start_date'))->addDays(380)->toDateString(),
            'period_pattern' => ['required', Rule::enum(FiscalCalendarPattern::class)],
        ]);

        $overlaps = FiscalYear::where('company_id', $companyId)
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages(['start_date' => 'The fiscal year overlaps an existing fiscal year.']);
        }

        $validated['company_id'] = $companyId;
        $validated['status'] = 'OPEN';
        $validated['is_current'] = false;
        $validated['created_by'] = auth()->id();

        $fiscalYear = FiscalYear::create($validated);

        $this->periodService->createFiscalYearPeriods($fiscalYear->id);

        return redirect()->route('core.periods.index')
            ->with('success', 'Fiscal year created with its periods and an adjustment period.');
    }
}
