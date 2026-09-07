<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\FinancialReportService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\ReceiptService;

class ReportController extends Controller
{
    public function __construct(
        protected LedgerService $ledgerService,
        protected FinancialReportService $financialReportService,
        protected PaymentService $paymentService,
        protected ReceiptService $receiptService
    ) {}

    public function generalLedger(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $ledger = $this->ledgerService->getGeneralLedger(
            $companyId,
            $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null,
            $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null
        );

        return view('finance.reports.general-ledger', [
            'ledger' => $ledger,
        ]);
    }

    public function trialBalance(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $trialBalance = $this->ledgerService->getTrialBalance(
            $companyId,
            $request->get('fiscal_period_id'),
            $request->get('date') ? Carbon::parse($request->get('date')) : null
        );

        return view('finance.reports.trial-balance', [
            'trialBalance' => $trialBalance,
        ]);
    }

    public function profitLoss(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $report = $this->financialReportService->getProfitAndLoss(
            $companyId,
            $request->get('fiscal_period_id'),
            $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null,
            $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null
        );

        return view('finance.reports.profit-loss', [
            'report' => $report,
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $report = $this->financialReportService->getBalanceSheet(
            $companyId,
            $request->get('as_of_date') ? Carbon::parse($request->get('as_of_date')) : null,
            $request->get('fiscal_period_id')
        );

        return view('finance.reports.balance-sheet', [
            'report' => $report,
        ]);
    }

    public function cashFlow(Request $request)
    {
        return view('finance.reports.cash-flow');
    }

    public function apReport(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $aging = $this->paymentService->getAPAging($companyId);

        return view('finance.reports.ap-report', [
            'aging' => $aging,
        ]);
    }

    public function arReport(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $aging = $this->receiptService->getARAging($companyId);

        return view('finance.reports.ar-report', [
            'aging' => $aging,
        ]);
    }
}
