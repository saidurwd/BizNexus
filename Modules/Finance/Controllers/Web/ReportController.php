<?php

namespace Modules\Finance\Controllers\Web;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Exceptions\MissingAccountMappingException;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\CashAccount;
use Modules\Finance\Models\CustomerReceipt;
use Modules\Finance\Models\JournalLine;
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
        $filters = $request->validate([
            'as_of_date' => ['nullable', 'date'],
            'fiscal_period_id' => ['nullable', 'integer'],
        ]);

        $report = $this->financialReportService->getBalanceSheet(
            $this->getActiveCompanyId(),
            isset($filters['as_of_date']) ? Carbon::parse($filters['as_of_date']) : null,
            isset($filters['fiscal_period_id']) ? (int) $filters['fiscal_period_id'] : null
        );

        return view('finance.reports.balance-sheet', [
            'report' => $report,
        ]);
    }

    public function cashFlow(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $report = $this->financialReportService->getCashFlow(
            $this->getActiveCompanyId(),
            isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : null,
            isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : null
        );

        return view('finance.reports.cash-flow', [
            'report' => $report,
        ]);
    }

    public function apAging(Request $request)
    {
        return $this->agingReport($request, 'payables');
    }

    public function arAging(Request $request)
    {
        return $this->agingReport($request, 'receivables');
    }

    /**
     * Open invoices by days past due at the chosen date, one row per customer or supplier.
     */
    protected function agingReport(Request $request, string $side)
    {
        $filters = $request->validate(['as_of_date' => ['nullable', 'date']]);
        $asOf = isset($filters['as_of_date']) ? CarbonImmutable::parse($filters['as_of_date']) : app(CompanyContextService::class)->today();

        $aging = $side === 'receivables'
            ? $this->receiptService->getARAging($this->getActiveCompanyId(), null, $asOf)
            : $this->paymentService->getAPAging($this->getActiveCompanyId(), null, $asOf);

        return view('finance.reports.aging', [
            'aging' => $aging,
            'title' => $side === 'receivables' ? __('Receivables ageing') : __('Payables ageing'),
            'partyLabel' => $side === 'receivables' ? __('Customer') : __('Supplier'),
            'routeName' => $request->route()->getName(),
            'exportReport' => $side === 'receivables' ? 'ar-aging' : 'ap-aging',
        ]);
    }

    public function paymentRegister(Request $request)
    {
        [$startDate, $endDate] = $this->registerPeriod($request);

        $payments = SupplierPayment::where('company_id', $this->getActiveCompanyId())
            ->with(['supplier', 'currency'])
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate)
            ->orderBy('payment_date', 'desc')
            ->get();
        $totalAmount = $this->postedFunctionalTotal($payments, SupplierPayment::STATUS_POSTED);

        return view('finance.reports.payment-register', compact('payments', 'startDate', 'endDate', 'totalAmount'));
    }

    public function receiptRegister(Request $request)
    {
        [$startDate, $endDate] = $this->registerPeriod($request);

        $receipts = CustomerReceipt::where('company_id', $this->getActiveCompanyId())
            ->with(['customer', 'currency'])
            ->whereDate('receipt_date', '>=', $startDate)
            ->whereDate('receipt_date', '<=', $endDate)
            ->orderBy('receipt_date', 'desc')
            ->get();
        $totalAmount = $this->postedFunctionalTotal($receipts, CustomerReceipt::STATUS_POSTED);

        return view('finance.reports.receipt-register', compact('receipts', 'startDate', 'endDate', 'totalAmount'));
    }

    /**
     * The register period from the request, defaulting to the current month to date.
     *
     * @return array{0: string, 1: string}
     */
    protected function registerPeriod(Request $request): array
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $today = app(CompanyContextService::class)->today();

        return [$filters['start_date'] ?? $today->startOfMonth()->toDateString(), $filters['end_date'] ?? $today->toDateString()];
    }

    /**
     * Total of the posted documents in the functional currency.
     *
     * @param  Collection<int, SupplierPayment|CustomerReceipt>  $documents
     */
    protected function postedFunctionalTotal($documents, string $postedStatus): string
    {
        return $documents->where('status', $postedStatus)
            ->reduce(fn (string $total, $document) => bcadd($total, bcmul((string) $document->amount, (string) ($document->exchange_rate ?: 1), 4), 4), '0.0000');
    }

    public function cashBook(Request $request)
    {
        return $this->treasuryBook($request, 'Cash Book', $this->treasuryAccountIds(CashAccount::class, AccountPurpose::Cash));
    }

    public function bankBook(Request $request)
    {
        return $this->treasuryBook($request, 'Bank Book', $this->treasuryAccountIds(BankAccount::class, AccountPurpose::Bank));
    }

    /**
     * Receipts (debits) and payments (credits) on treasury GL accounts from posted journals, with balances.
     *
     * @param  array<int, int>  $accountIds
     */
    protected function treasuryBook(Request $request, string $title, array $accountIds)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $postedLines = fn () => JournalLine::whereIn('account_id', $accountIds);

        $openingBalance = (float) $postedLines()
            ->whereHas('journal', fn ($query) => $query->posted()->whereDate('journal_date', '<', $startDate))
            ->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance');

        $lines = $postedLines()
            ->with(['journal', 'account'])
            ->whereHas('journal', fn ($query) => $query->posted()->whereDate('journal_date', '>=', $startDate)->whereDate('journal_date', '<=', $endDate))
            ->get()
            ->sortBy(fn (JournalLine $line) => [$line->journal->journal_date->toDateString(), $line->journal_id])
            ->values();

        $closingBalance = $openingBalance + (float) $lines->sum('debit') - (float) $lines->sum('credit');

        return view('finance.reports.treasury-book', compact('title', 'lines', 'openingBalance', 'closingBalance', 'startDate', 'endDate'));
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

    /**
     * GL accounts behind the company's cash or bank accounts, plus the mapped default account.
     *
     * @param  class-string<CashAccount|BankAccount>  $treasuryModel
     * @return array<int, int>
     */
    protected function treasuryAccountIds(string $treasuryModel, AccountPurpose $purpose): array
    {
        $accountIds = $treasuryModel::pluck('gl_account_id')->filter()->all();

        try {
            $accountIds[] = app(DefaultAccountService::class)->forPurpose($this->getActiveCompanyId(), $purpose);
        } catch (MissingAccountMappingException) {
        }

        return array_values(array_unique($accountIds));
    }
}
