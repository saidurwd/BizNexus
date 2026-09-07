<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\FinancialReportService;
use Modules\Core\Services\CompanyContextService;

class ReportController extends Controller
{
    public function __construct(
        protected LedgerService $ledgerService,
        protected FinancialReportService $financialReportService,
        protected CompanyContextService $companyContext
    ) {}

    public function trialBalance(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $trialBalance = $this->ledgerService->getTrialBalance(
            $companyId,
            $request->get('fiscal_period_id'),
            $request->get('date') ? Carbon::parse($request->get('date')) : null
        );

        return $this->successResponse($trialBalance);
    }

    public function generalLedger(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $ledger = $this->ledgerService->getGeneralLedger(
            $companyId,
            $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null,
            $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null,
            $request->get('fiscal_period_id'),
            $request->get('account_id')
        );

        return $this->successResponse($ledger);
    }

    public function accountStatement(Request $request, int $accountId)
    {
        $statement = $this->ledgerService->getAccountStatement(
            $accountId,
            $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null,
            $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null,
            $request->get('fiscal_period_id')
        );

        return $this->successResponse($statement);
    }

    public function profitAndLoss(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $report = $this->financialReportService->getProfitAndLoss(
            $companyId,
            $request->get('fiscal_period_id'),
            $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null,
            $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null
        );

        return $this->successResponse($report);
    }

    public function balanceSheet(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $report = $this->financialReportService->getBalanceSheet(
            $companyId,
            $request->get('as_of_date') ? Carbon::parse($request->get('as_of_date')) : null,
            $request->get('fiscal_period_id')
        );

        return $this->successResponse($report);
    }

    public function dashboard(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $dashboard = $this->financialReportService->getDashboardData($companyId);

        return $this->successResponse($dashboard);
    }
}
