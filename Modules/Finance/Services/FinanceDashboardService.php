<?php

namespace Modules\Finance\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\DefaultAccountService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Enums\CashFlowCategory;
use Modules\Finance\Exceptions\MissingAccountMappingException;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\CustomerReceipt;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierPayment;

/**
 * Headline figures for the home dashboard, in the company's functional currency. Balances are as at the
 * business date; revenue, expenses and profit cover the fiscal year to date.
 */
class FinanceDashboardService
{
    public function __construct(protected DefaultAccountService $defaultAccounts) {}

    /**
     * @return array{fiscal_year_start: string, as_of: string, total_revenue: string, total_expenses: string, net_profit: string, total_assets: string, total_liabilities: string, total_equity: string, cash_and_equivalents: string, cash_balance: string, bank_balance: string, accounts_receivable: string, accounts_payable: string, overdue_receivables: string, payables_due_soon: string}
     */
    public function summary(int $companyId, CarbonInterface $asOf): array
    {
        $yearStart = $this->fiscalYearStart($companyId, $asOf);
        $accounts = Account::where('company_id', $companyId)->get(['id', 'account_type', 'cash_flow_category'])->keyBy('id');
        $balances = $this->netDebitByAccount($companyId, null, $asOf);
        $yearToDate = $this->netDebitByAccount($companyId, $yearStart, $asOf);

        $sumOf = fn (Collection $netDebits, callable $filter): string => $netDebits
            ->filter(fn (string $net, int $accountId) => $accounts->has($accountId) && $filter($accounts[$accountId]))
            ->reduce(fn (string $total, string $net) => bcadd($total, $net, 4), '0.0000');
        $ofType = fn (string $type) => fn (Account $account) => $account->account_type === $type;

        $revenue = bcmul($sumOf($yearToDate, $ofType('REVENUE')), '-1', 4);
        $expenses = $sumOf($yearToDate, $ofType('EXPENSE'));
        $assets = $sumOf($balances, $ofType('ASSET'));
        $liabilities = bcmul($sumOf($balances, $ofType('LIABILITY')), '-1', 4);
        $mappedBalance = fn (AccountPurpose $purpose, bool $creditNormal = false) => $this->mappedBalance($companyId, $purpose, $balances, $creditNormal);
        $isCashEquivalent = fn (Account $account) => $account->cash_flow_category === CashFlowCategory::CashAndEquivalents;
        $cashAndEquivalents = $accounts->contains($isCashEquivalent)
            ? $sumOf($balances, $isCashEquivalent)
            : bcadd($mappedBalance(AccountPurpose::Cash), $mappedBalance(AccountPurpose::Bank), 4);

        return [
            'fiscal_year_start' => $yearStart->toDateString(),
            'as_of' => $asOf->toDateString(),
            'total_revenue' => $revenue,
            'total_expenses' => $expenses,
            'net_profit' => bcsub($revenue, $expenses, 4),
            'total_assets' => $assets,
            'total_liabilities' => $liabilities,
            'total_equity' => bcsub($assets, $liabilities, 4),
            'cash_and_equivalents' => $cashAndEquivalents,
            'cash_balance' => $mappedBalance(AccountPurpose::Cash),
            'bank_balance' => $mappedBalance(AccountPurpose::Bank),
            'accounts_receivable' => $mappedBalance(AccountPurpose::Receivable),
            'accounts_payable' => $mappedBalance(AccountPurpose::Payable, creditNormal: true),
            'overdue_receivables' => $this->functionalOutstanding(CustomerInvoice::pending()->whereDate('due_date', '<', $asOf->toDateString())),
            'payables_due_soon' => $this->functionalOutstanding(SupplierInvoice::pending()->whereDate('due_date', '<=', $asOf->addDays(7)->toDateString())),
        ];
    }

    /**
     * Revenue and expenses per calendar month for the months up to and including the business date's month.
     *
     * @return array{labels: list<string>, revenue: list<float>, expenses: list<float>}
     */
    public function monthlyPerformance(int $companyId, CarbonInterface $asOf, int $months = 12): array
    {
        $firstMonth = CarbonImmutable::parse($asOf)->startOfMonth()->subMonths($months - 1);

        $rows = JournalLine::query()
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.company_id', $companyId)
            ->whereIn('journals.status', Journal::LEDGER_STATUSES)
            ->whereIn('accounts.account_type', ['REVENUE', 'EXPENSE'])
            ->whereDate('journals.journal_date', '>=', $firstMonth->toDateString())
            ->whereDate('journals.journal_date', '<=', $asOf->toDateString())
            ->groupBy('journals.journal_date', 'accounts.account_type')
            ->selectRaw('journals.journal_date as journal_date, accounts.account_type as account_type, SUM(journal_lines.debit) - SUM(journal_lines.credit) as net_debit')
            ->get();

        $monthly = [];
        foreach ($rows as $row) {
            $month = substr((string) $row->journal_date, 0, 7);
            $monthly[$month][$row->account_type] = bcadd($monthly[$month][$row->account_type] ?? '0', (string) $row->net_debit, 4);
        }

        $performance = ['labels' => [], 'revenue' => [], 'expenses' => []];
        for ($month = $firstMonth; $month->lte($asOf); $month = $month->addMonth()) {
            $key = $month->format('Y-m');
            $performance['labels'][] = $month->locale(app()->getLocale())->isoFormat('MMM YYYY');
            $performance['revenue'][] = -1 * (float) ($monthly[$key]['REVENUE'] ?? 0);
            $performance['expenses'][] = (float) ($monthly[$key]['EXPENSE'] ?? 0);
        }

        return $performance;
    }

    /**
     * Documents waiting on someone, keyed by the permission needed to act on them.
     *
     * @return list<array{label: string, count: int, route: string, parameters: array<string, string>, permission: string}>
     */
    public function attentionItems(CarbonInterface $asOf): array
    {
        $submitted = fn (string $model, string $label, string $route, string $permission) => [
            'label' => $label,
            'count' => $model::where('status', 'SUBMITTED')->count(),
            'route' => $route,
            'parameters' => ['status' => 'SUBMITTED'],
            'permission' => $permission,
        ];

        return array_values(array_filter([
            $submitted(Journal::class, 'Journals awaiting approval', 'finance.journals.index', 'finance.journals.approve'),
            $submitted(CustomerInvoice::class, 'Customer invoices awaiting approval', 'finance.customer-invoices.index', 'finance.customer-invoices.approve'),
            $submitted(SupplierInvoice::class, 'Supplier invoices awaiting approval', 'finance.supplier-invoices.index', 'finance.supplier-invoices.approve'),
            $submitted(SupplierPayment::class, 'Payments awaiting approval', 'finance.payments.index', 'finance.payments.approve'),
            $submitted(CustomerReceipt::class, 'Receipts awaiting approval', 'finance.receipts.index', 'finance.receipts.approve'),
            [
                'label' => 'Overdue customer invoices',
                'count' => CustomerInvoice::pending()->whereDate('due_date', '<', $asOf->toDateString())->count(),
                'route' => 'finance.customer-invoices.index',
                'parameters' => ['overdue' => '1'],
                'permission' => 'finance.customer-invoices.view',
            ],
            [
                'label' => 'Draft journals',
                'count' => Journal::draft()->count(),
                'route' => 'finance.journals.index',
                'parameters' => ['status' => 'DRAFT'],
                'permission' => 'finance.journals.view',
            ],
        ], fn (array $item) => $item['count'] > 0));
    }

    /**
     * Customers owing the most past their due date, in the functional currency.
     *
     * @return Collection<int, array{customer: string, amount: string, invoices: int}>
     */
    public function topOverdueCustomers(CarbonInterface $asOf, int $limit = 5): Collection
    {
        return CustomerInvoice::pending()
            ->whereDate('due_date', '<', $asOf->toDateString())
            ->with('customer:id,name')
            ->get(['id', 'customer_id', 'outstanding_amount', 'exchange_rate'])
            ->groupBy('customer_id')
            ->map(fn (Collection $invoices) => [
                'customer' => $invoices->first()->customer?->name ?? '—',
                'amount' => $invoices->reduce(fn (string $total, CustomerInvoice $invoice) => bcadd($total, bcmul((string) $invoice->outstanding_amount, (string) ($invoice->exchange_rate ?: 1), 4), 4), '0'),
                'invoices' => $invoices->count(),
            ])
            ->sortByDesc(fn (array $row) => (float) $row['amount'])
            ->take($limit)
            ->values();
    }

    /**
     * Net debit (debit − credit) per account from posted journals, optionally from a start date.
     *
     * @return Collection<int, string>
     */
    protected function netDebitByAccount(int $companyId, ?CarbonInterface $from, CarbonInterface $to): Collection
    {
        return JournalLine::query()
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.company_id', $companyId)
            ->whereIn('journals.status', Journal::LEDGER_STATUSES)
            ->when($from, fn ($query) => $query->whereDate('journals.journal_date', '>=', $from->toDateString()))
            ->whereDate('journals.journal_date', '<=', $to->toDateString())
            ->groupBy('journal_lines.account_id')
            ->selectRaw('journal_lines.account_id as account_id, SUM(journal_lines.debit) - SUM(journal_lines.credit) as net_debit')
            ->pluck('net_debit', 'account_id')
            ->map(fn ($net) => bcadd((string) $net, '0', 4));
    }

    protected function fiscalYearStart(int $companyId, CarbonInterface $asOf): CarbonImmutable
    {
        $start = FiscalYear::where('company_id', $companyId)
            ->whereDate('start_date', '<=', $asOf->toDateString())
            ->whereDate('end_date', '>=', $asOf->toDateString())
            ->value('start_date');

        return $start ? CarbonImmutable::parse($start)->startOfDay() : CarbonImmutable::parse($asOf)->startOfYear();
    }

    /**
     * @param  Collection<int, string>  $balances
     */
    protected function mappedBalance(int $companyId, AccountPurpose $purpose, Collection $balances, bool $creditNormal): string
    {
        try {
            $netDebit = $balances->get($this->defaultAccounts->forPurpose($companyId, $purpose), '0.0000');
        } catch (MissingAccountMappingException) {
            return '0.0000';
        }

        return $creditNormal ? bcmul($netDebit, '-1', 4) : $netDebit;
    }

    protected function functionalOutstanding($invoices): string
    {
        return $invoices->get(['outstanding_amount', 'exchange_rate'])
            ->reduce(fn (string $total, $invoice) => bcadd($total, bcmul((string) $invoice->outstanding_amount, (string) ($invoice->exchange_rate ?: 1), 4), 4), '0.0000');
    }
}
