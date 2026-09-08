<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\AccountingPeriodService;

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
            'end_date' => 'required|date|after:start_date',
        ]);

        $validated['company_id'] = $companyId;
        $validated['status'] = 'OPEN';
        $validated['is_current'] = false;
        $validated['created_by'] = auth()->id();

        $fiscalYear = FiscalYear::create($validated);

        $this->periodService->createFiscalYearPeriods($fiscalYear->id);

        return redirect()->route('core.periods.index')
            ->with('success', 'Fiscal year created successfully with monthly periods.');
    }
}
