<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Services\FinancialReportService;

class DashboardController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $dashboardData = $this->reportService->getDashboardData($companyId);

        return view('finance.dashboard', [
            'dashboard' => $dashboardData,
        ]);
    }
}
