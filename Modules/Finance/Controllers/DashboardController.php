<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Finance\Services\FinancialReportService;
use Modules\Core\Services\CompanyContextService;

class DashboardController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId() ?? 1;

        $dashboardData = $this->reportService->getDashboardData($companyId);

        return view('finance.dashboard', [
            'dashboard' => $dashboardData,
        ]);
    }
}
