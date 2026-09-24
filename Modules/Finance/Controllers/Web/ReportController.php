<?php

namespace Modules\Finance\Controllers\Web;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Jobs\GenerateReportJob;
use Modules\Finance\Models\CustomerReceipt;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\SupplierPayment;
use Modules\Finance\Services\BudgetService;
use Modules\Finance\Services\FinancialReportService;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\ReceiptService;

class ReportController extends Controller
{
    public function __construct(
        protected LedgerService $ledgerService,
        protected FinancialReportService $financialReportService,
        protected PaymentService $paymentService,
        protected ReceiptService $receiptService,
        protected BudgetService $budgetService,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function generalLedger(Request $request)
    {

        $ledger = $this->ledgerService->getGeneralLedger(
            $this->getActiveCompanyId(),
            $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null,
            $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null
        );

        return view('finance.reports.general-ledger', [
            'ledger' => $ledger,
        ]);
    }

    public function trialBalance(Request $request)
    {

        $trialBalance = $this->ledgerService->getTrialBalance(
            $this->getActiveCompanyId(),
            $request->get('fiscal_period_id'),
            $request->get('date') ? Carbon::parse($request->get('date')) : null
        );

        $asOfDate = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::today();

        return view('finance.reports.trial-balance', [
            'trialBalance' => $trialBalance,
            'accounts' => $trialBalance['accounts'],
            'asOfDate' => $asOfDate->format('Y-m-d'),
        ]);
    }

    public function profitLoss(Request $request)
    {

        $report = $this->financialReportService->getProfitAndLoss(
            $this->getActiveCompanyId(),
            $request->get('fiscal_period_id'),
            $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null,
            $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null
        );

        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now()->endOfMonth();

        return view('finance.reports.income-statement', [
            'report' => $report,
            'revenue' => $report['revenue']['accounts'],
            'expenses' => $report['expenses']['accounts'],
            'totalRevenue' => $report['revenue']['total'],
            'totalExpenses' => $report['expenses']['total'],
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);
    }

    public function balanceSheet(Request $request)
    {

        $report = $this->financialReportService->getBalanceSheet(
            $this->getActiveCompanyId(),
            $request->get('as_of_date') ? Carbon::parse($request->get('as_of_date')) : null,
            $request->get('fiscal_period_id')
        );

        return view('finance.reports.balance-sheet', [
            'report' => $report,
        ]);
    }

    public function cashFlow(Request $request)
    {

        $report = $this->financialReportService->getCashFlow(
            $this->getActiveCompanyId(),
            $request->get('fiscal_period_id'),
            $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null,
            $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null
        );

        return view('finance.reports.cash-flow', [
            'report' => $report,
        ]);
    }

    public function apAging()
    {

        $aging = $this->paymentService->getAPAging($this->getActiveCompanyId());

        return view('finance.reports.ap', [
            'apAging' => $aging,
        ]);
    }

    public function arAging()
    {

        $aging = $this->receiptService->getARAging($this->getActiveCompanyId());

        return view('finance.reports.ar', [
            'arAging' => $aging,
        ]);
    }

    public function paymentRegister()
    {

        $payments = SupplierPayment::where('company_id', $this->getActiveCompanyId())
            ->with('supplier')
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('finance.reports.payment-register', compact('payments'));
    }

    public function receiptRegister()
    {

        $receipts = CustomerReceipt::where('company_id', $this->getActiveCompanyId())
            ->with('customer')
            ->orderBy('receipt_date', 'desc')
            ->get();

        return view('finance.reports.receipt-register', compact('receipts'));
    }

    public function cashBook()
    {

        $transactions = Journal::where('company_id', $this->getActiveCompanyId())
            ->whereHas('lines', fn ($q) => $q->whereHas('account', fn ($q2) => $q2->where('account_code', 'like', '1110%')))
            ->with('lines.account')
            ->orderBy('journal_date', 'desc')
            ->get();

        return view('finance.reports.cash-book', compact('transactions'));
    }

    public function bankBook()
    {

        $transactions = Journal::where('company_id', $this->getActiveCompanyId())
            ->whereHas('lines', fn ($q) => $q->whereHas('account', fn ($q2) => $q2->where('account_code', 'like', '1120%')))
            ->with('lines.account')
            ->orderBy('journal_date', 'desc')
            ->get();

        return view('finance.reports.bank-book', compact('transactions'));
    }

    public function management()
    {

        $fiscalYearId = FiscalYear::where('company_id', $this->getActiveCompanyId())
            ->where('is_current', true)
            ->value('id');

        $budgetData = $this->budgetService->getBudgetVsActual($this->getActiveCompanyId(), $fiscalYearId);

        return view('finance.reports.management', [
            'budgetData' => $budgetData,
            'fiscalYearId' => $fiscalYearId,
        ]);
    }

    public function generateAsync(Request $request)
    {

        $request->validate([
            'report_type' => 'required|string|in:trial_balance,general_ledger,profit_loss,balance_sheet,cash_flow',
            'filters' => 'array',
            'format' => 'nullable|string|in:pdf,excel',
        ]);

        GenerateReportJob::dispatch(
            $request->input('report_type'),
            $request->input('filters', []),
            auth()->id(),
            $this->getActiveCompanyId(),
            $request->input('format', 'pdf')
        );

        return back()->with('success', 'Report generation started. You will be notified when it\'s ready.');
    }
}
