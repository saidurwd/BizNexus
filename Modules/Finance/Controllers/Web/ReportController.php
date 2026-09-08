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

        $asOfDate = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::today();

        return view('finance.reports.trial-balance', [
            'trialBalance' => $trialBalance,
            'accounts' => $trialBalance['accounts'],
            'asOfDate' => $asOfDate->format('Y-m-d'),
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
        $companyId = $request->get('company_id', 1);

        $report = $this->financialReportService->getBalanceSheet(
            $companyId,
            $request->get('as_of_date') ? Carbon::parse($request->get('as_of_date')) : null,
            $request->get('fiscal_period_id')
        );

        return view('finance.reports.balance-sheet', [
            'report' => $report,
            'asOfDate' => $report['as_of_date'],
            'assets' => $report['assets']['accounts'],
            'liabilities' => $report['liabilities']['accounts'],
            'equity' => $report['equity']['accounts'],
            'totalAssets' => $report['assets']['total'],
            'totalLiabilities' => $report['liabilities']['total'],
            'totalEquity' => $report['equity']['total'],
            'totalLiabilitiesEquity' => $report['total_liabilities_equity'],
        ]);
    }

    public function cashFlow(Request $request)
    {
        $companyId = $request->get('company_id', 1);
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now()->endOfMonth();

        $cashFlow = $this->financialReportService->getCashFlow($companyId, $startDate, $endDate);

        return view('finance.reports.cash-flow', [
            'startDate' => $cashFlow['start_date'],
            'endDate' => $cashFlow['end_date'],
            'operatingActivities' => $cashFlow['operating_activities'],
            'investingActivities' => $cashFlow['investing_activities'],
            'financingActivities' => $cashFlow['financing_activities'],
            'operatingTotal' => $cashFlow['operating_total'],
            'investingTotal' => $cashFlow['investing_total'],
            'financingTotal' => $cashFlow['financing_total'],
            'netChange' => $cashFlow['net_change'],
        ]);
    }

    public function apReport(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $aging = $this->paymentService->getAPAging($companyId);
        $apAging = collect($aging['invoices'])
            ->groupBy('supplier_name')
            ->map(function ($invoices, $supplierName) {
                $summary = [
                    'supplier_name' => $supplierName,
                    'current' => 0,
                    'days_1_30' => 0,
                    'days_31_60' => 0,
                    'days_61_90' => 0,
                    'over_90_days' => 0,
                    'total' => 0,
                ];

                foreach ($invoices as $invoice) {
                    $amount = $invoice['amount'];
                    $summary['total'] += $amount;

                    if (in_array($invoice['bucket'], ['days_91_180', 'days_180_plus'])) {
                        $summary['over_90_days'] += $amount;
                    } else {
                        $summary[$invoice['bucket']] += $amount;
                    }
                }

                return $summary;
            })
            ->values();

        return view('finance.reports.ap', [
            'apAging' => $apAging,
            'asOfDate' => Carbon::today()->toDateString(),
        ]);
    }

    public function arReport(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $aging = $this->receiptService->getARAging($companyId);
        $arAging = collect($aging['invoices'])
            ->groupBy('customer_name')
            ->map(function ($invoices, $customerName) {
                $summary = [
                    'customer_name' => $customerName,
                    'current' => 0,
                    'days_1_30' => 0,
                    'days_31_60' => 0,
                    'days_61_90' => 0,
                    'over_90_days' => 0,
                    'total' => 0,
                ];

                foreach ($invoices as $invoice) {
                    $amount = $invoice['amount'];
                    $summary['total'] += $amount;

                    if (in_array($invoice['bucket'], ['days_91_180', 'days_180_plus'])) {
                        $summary['over_90_days'] += $amount;
                    } else {
                        $summary[$invoice['bucket']] += $amount;
                    }
                }

                return $summary;
            })
            ->values();

        return view('finance.reports.ar', [
            'arAging' => $arAging,
            'asOfDate' => Carbon::today()->toDateString(),
        ]);
    }

    public function budgetVsActual(Request $request)
    {
        return view('finance.reports.budget-vs-actual');
    }

    public function paymentRegister(Request $request)
    {
        $companyId = $request->get('company_id', 1);
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->subMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now();

        $payments = \Modules\Finance\Models\SupplierPayment::with(['supplier', 'bankAccount'])
            ->where('company_id', $companyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('finance.reports.payment-register', [
            'payments' => $payments,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'totalAmount' => $payments->sum('amount'),
        ]);
    }

    public function receiptRegister(Request $request)
    {
        $companyId = $request->get('company_id', 1);
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->subMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now();

        $receipts = \Modules\Finance\Models\CustomerReceipt::with(['customer', 'bankAccount'])
            ->where('company_id', $companyId)
            ->whereBetween('receipt_date', [$startDate, $endDate])
            ->orderBy('receipt_date', 'desc')
            ->get();

        return view('finance.reports.receipt-register', [
            'receipts' => $receipts,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'totalAmount' => $receipts->sum('amount'),
        ]);
    }

    public function cashBook(Request $request)
    {
        $companyId = $request->get('company_id', 1);
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->subMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now();

        $transactions = \Modules\Finance\Models\BankTransaction::with(['bankAccount'])
            ->whereHas('bankAccount', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->where('account_type', 'CASH');
            })
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'asc')
            ->get();

        $openingBalance = \Modules\Finance\Models\BankAccount::where('company_id', $companyId)
            ->where('account_type', 'CASH')
            ->sum('opening_balance');

        $closingBalance = $openingBalance + $transactions->where('transaction_type', 'DEPOSIT')->sum('amount') - $transactions->where('transaction_type', 'WITHDRAWAL')->sum('amount') - $transactions->where('transaction_type', 'CHARGE')->sum('amount');

        return view('finance.reports.cash-book', [
            'transactions' => $transactions,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);
    }

    public function bankBook(Request $request)
    {
        $companyId = $request->get('company_id', 1);
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->subMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now();

        $transactions = \Modules\Finance\Models\BankTransaction::with(['bankAccount'])
            ->whereHas('bankAccount', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->whereIn('account_type', ['BANK', 'PETTY_CASH']);
            })
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'asc')
            ->get();

        $openingBalance = \Modules\Finance\Models\BankAccount::where('company_id', $companyId)
            ->whereIn('account_type', ['BANK', 'PETTY_CASH'])
            ->sum('opening_balance');

        $closingBalance = $openingBalance + $transactions->where('transaction_type', 'DEPOSIT')->sum('amount') - $transactions->where('transaction_type', 'WITHDRAWAL')->sum('amount') - $transactions->where('transaction_type', 'CHARGE')->sum('amount') - $transactions->where('transaction_type', 'TRANSFER')->sum('amount');

        return view('finance.reports.bank-book', [
            'transactions' => $transactions,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);
    }

    public function management(Request $request)
    {
        $companyId = $request->get('company_id', 1);
        $fiscalYearId = $request->get('fiscal_year_id');

        $budgetData = $this->budgetService->getBudgetVsActual($companyId, $fiscalYearId);

        return view('finance.reports.management', [
            'budgetData' => $budgetData,
            'fiscalYearId' => $fiscalYearId,
        ]);
    }
}
