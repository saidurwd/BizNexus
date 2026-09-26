<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Modules\Core\Services\DefaultAccountService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Enums\CashFlowCategory;
use Modules\Finance\Exceptions\MissingAccountMappingException;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;

class FinancialReportService
{
    public function getProfitAndLoss(
        int $companyId,
        ?int $fiscalPeriodId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $revenueAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'REVENUE')
            ->postable()
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'EXPENSE')
            ->postable()
            ->get();

        $totalRevenue = 0;
        $totalExpenses = 0;

        $revenueDetails = [];
        foreach ($revenueAccounts as $account) {
            $amount = $this->getAccountBalance($account->id, $startDate, $endDate, $fiscalPeriodId);
            $totalRevenue = bcadd($totalRevenue, $amount, 4);

            $revenueDetails[] = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'amount' => $amount,
            ];
        }

        $expenseDetails = [];
        foreach ($expenseAccounts as $account) {
            $amount = $this->getAccountBalance($account->id, $startDate, $endDate, $fiscalPeriodId);
            $totalExpenses = bcadd($totalExpenses, $amount, 4);

            $expenseDetails[] = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'amount' => $amount,
            ];
        }

        $grossProfit = $totalRevenue;
        $netProfit = bcsub($grossProfit, $totalExpenses, 4);

        return [
            'company_id' => $companyId,
            'period' => [
                'fiscal_period_id' => $fiscalPeriodId,
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'accounts' => $revenueDetails,
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'accounts' => $expenseDetails,
            ],
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'net_profit_margin' => $totalRevenue > 0
                ? bcmul(bcdiv($netProfit, $totalRevenue, 4), 100, 2)
                : 0,
        ];
    }

    public function getBalanceSheet(int $companyId, ?Carbon $asOfDate = null, ?int $fiscalPeriodId = null): array
    {
        $asOfDate = $asOfDate ?? Carbon::now();

        $assetAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'ASSET')
            ->postable()
            ->get();

        $liabilityAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'LIABILITY')
            ->postable()
            ->get();

        $equityAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'EQUITY')
            ->postable()
            ->get();

        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        $assetDetails = [];
        foreach ($assetAccounts as $account) {
            $amount = $this->getAccountBalance($account->id, null, $asOfDate, $fiscalPeriodId);
            $totalAssets = bcadd($totalAssets, $amount, 4);

            $assetDetails[] = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'amount' => $amount,
            ];
        }

        $liabilityDetails = [];
        foreach ($liabilityAccounts as $account) {
            $amount = $this->getAccountBalance($account->id, null, $asOfDate, $fiscalPeriodId);
            $totalLiabilities = bcadd($totalLiabilities, $amount, 4);

            $liabilityDetails[] = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'amount' => $amount,
            ];
        }

        $revenueQuery = JournalLine::whereHas('account', fn ($q) => $q->where('account_type', 'REVENUE'))
            ->whereHas('journal', fn ($q) => $q->where('company_id', $companyId)->posted());

        $expenseQuery = JournalLine::whereHas('account', fn ($q) => $q->where('account_type', 'EXPENSE'))
            ->whereHas('journal', fn ($q) => $q->where('company_id', $companyId)->posted());

        if ($fiscalPeriodId) {
            $revenueQuery->whereHas('journal', fn ($q) => $q->where('fiscal_period_id', $fiscalPeriodId));
            $expenseQuery->whereHas('journal', fn ($q) => $q->where('fiscal_period_id', $fiscalPeriodId));
        } else {
            $revenueQuery->whereHas('journal', fn ($q) => $q->where('journal_date', '<=', $asOfDate));
            $expenseQuery->whereHas('journal', fn ($q) => $q->where('journal_date', '<=', $asOfDate));
        }

        $totalRevenue = (float) bcsub(
            $revenueQuery->clone()->sum('credit'),
            $revenueQuery->clone()->sum('debit'),
            4
        );

        $totalExpenses = (float) bcsub(
            $expenseQuery->clone()->sum('debit'),
            $expenseQuery->clone()->sum('credit'),
            4
        );

        $currentYearProfit = bcsub($totalRevenue, $totalExpenses, 4);

        $equityDetails = [];
        foreach ($equityAccounts as $account) {
            $amount = $this->getAccountBalance($account->id, null, $asOfDate, $fiscalPeriodId);
            $totalEquity = bcadd($totalEquity, $amount, 4);

            $equityDetails[] = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'amount' => $amount,
            ];
        }

        $totalEquity = bcadd($totalEquity, $currentYearProfit, 4);

        return [
            'company_id' => $companyId,
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'fiscal_period_id' => $fiscalPeriodId,
            'assets' => [
                'total' => $totalAssets,
                'accounts' => $assetDetails,
            ],
            'liabilities' => [
                'total' => $totalLiabilities,
                'accounts' => $liabilityDetails,
            ],
            'equity' => [
                'total' => $totalEquity,
                'accounts' => $equityDetails,
                'current_year_profit' => $currentYearProfit,
            ],
            'total_liabilities_equity' => bcadd($totalLiabilities, $totalEquity, 4),
            'check' => [
                'total_assets' => $totalAssets,
                'total_liabilities_equity' => bcadd($totalLiabilities, $totalEquity, 4),
                'is_balanced' => bccomp($totalAssets, bcadd($totalLiabilities, $totalEquity, 4), 4) === 0,
            ],
        ];
    }

    protected function getAccountBalance(int $accountId, ?Carbon $startDate, ?Carbon $endDate, ?int $fiscalPeriodId): float
    {
        $account = Account::findOrFail($accountId);

        $query = JournalLine::where('account_id', $accountId)
            ->whereHas('journal', fn ($q) => $q->posted());

        if ($startDate) {
            $query->whereHas('journal', fn ($q) => $q->where('journal_date', '>=', $startDate));
        }

        if ($endDate) {
            $query->whereHas('journal', fn ($q) => $q->where('journal_date', '<=', $endDate));
        }

        if ($fiscalPeriodId) {
            $query->whereHas('journal', fn ($q) => $q->where('fiscal_period_id', $fiscalPeriodId));
        }

        $totalDebit = (float) $query->clone()->sum('debit');
        $totalCredit = (float) $query->clone()->sum('credit');

        if ($account->isDebitNormal()) {
            return bcsub($totalDebit, $totalCredit, 4);
        }

        return bcsub($totalCredit, $totalDebit, 4);
    }

    /**
     * Statement of cash flows (IAS 7, direct method). Every posted movement on a cash-and-equivalents account
     * is classified by the account on the other side of the entry (its cash flow category, operating when
     * unclassified). Revaluation of foreign-currency cash is shown separately as the effect of exchange rates.
     *
     * @return array{start_date: string, end_date: string, opening_cash: string, closing_cash: string, operating_activities: list<array{description: string, amount: string}>, investing_activities: list<array{description: string, amount: string}>, financing_activities: list<array{description: string, amount: string}>, operating_total: string, investing_total: string, financing_total: string, fx_effect: string, net_change: string, is_reconciled: bool}
     */
    public function getCashFlow(int $companyId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = ($startDate ?: Carbon::now()->startOfMonth())->copy()->startOfDay();
        $endDate = ($endDate ?: Carbon::now()->endOfMonth())->copy()->startOfDay();
        $cashAccountIds = $this->cashAccountIds($companyId);

        $journals = Journal::where('company_id', $companyId)
            ->posted()
            ->whereDate('journal_date', '>=', $startDate->toDateString())
            ->whereDate('journal_date', '<=', $endDate->toDateString())
            ->whereHas('lines', fn ($query) => $query->whereIn('account_id', $cashAccountIds))
            ->with('lines.account')
            ->get();

        $byCategory = ['operating' => [], 'investing' => [], 'financing' => []];
        $fxEffect = '0.0000';

        foreach ($journals as $journal) {
            $cashLines = $journal->lines->filter(fn (JournalLine $line) => in_array((int) $line->account_id, $cashAccountIds, true));
            $cashMovement = $cashLines->reduce(fn (string $total, JournalLine $line) => bcadd($total, bcsub((string) $line->debit, (string) $line->credit, 4), 4), '0.0000');

            if ($cashLines->contains(fn (JournalLine $line) => $line->line_type === JournalLine::TYPE_FX_REVALUATION)) {
                $fxEffect = bcadd($fxEffect, $cashMovement, 4);

                continue;
            }

            if (bccomp($cashMovement, '0', 4) === 0) {
                continue;
            }

            foreach ($journal->lines->reject(fn (JournalLine $line) => in_array((int) $line->account_id, $cashAccountIds, true)) as $line) {
                $category = match ($line->account?->cash_flow_category) {
                    CashFlowCategory::Investing => 'investing',
                    CashFlowCategory::Financing => 'financing',
                    default => 'operating',
                };
                $description = trim(($line->account?->account_code ?? '').' '.($line->account?->account_name ?? ''));
                $byCategory[$category][$description] = bcadd($byCategory[$category][$description] ?? '0.0000', bcsub((string) $line->credit, (string) $line->debit, 4), 4);
            }
        }

        $activities = [];
        $totals = [];
        foreach ($byCategory as $category => $amounts) {
            ksort($amounts);
            $activities[$category] = collect($amounts)
                ->reject(fn (string $amount) => bccomp($amount, '0', 4) === 0)
                ->map(fn (string $amount, string $description) => ['description' => $description, 'amount' => $amount])
                ->values()
                ->all();
            $totals[$category] = array_reduce($amounts, fn (string $total, string $amount) => bcadd($total, $amount, 4), '0.0000');
        }

        $openingCash = $this->cashBalance($cashAccountIds, $startDate->copy()->subDay());
        $closingCash = $this->cashBalance($cashAccountIds, $endDate);
        $netChange = bcadd(bcadd(bcadd($totals['operating'], $totals['investing'], 4), $totals['financing'], 4), $fxEffect, 4);

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'opening_cash' => $openingCash,
            'closing_cash' => $closingCash,
            'operating_activities' => $activities['operating'],
            'investing_activities' => $activities['investing'],
            'financing_activities' => $activities['financing'],
            'operating_total' => $totals['operating'],
            'investing_total' => $totals['investing'],
            'financing_total' => $totals['financing'],
            'fx_effect' => $fxEffect,
            'net_change' => $netChange,
            'is_reconciled' => bccomp(bcadd($openingCash, $netChange, 4), $closingCash, 4) === 0,
        ];
    }

    /**
     * Accounts classified as cash and cash equivalents, or the mapped cash and bank accounts when none are classified.
     *
     * @return list<int>
     */
    protected function cashAccountIds(int $companyId): array
    {
        $classified = Account::where('company_id', $companyId)
            ->where('cash_flow_category', CashFlowCategory::CashAndEquivalents)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($classified !== []) {
            return $classified;
        }

        return collect([AccountPurpose::Cash, AccountPurpose::Bank])
            ->map(function (AccountPurpose $purpose) use ($companyId) {
                try {
                    return app(DefaultAccountService::class)->forPurpose($companyId, $purpose);
                } catch (MissingAccountMappingException) {
                    return null;
                }
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $accountIds
     */
    protected function cashBalance(array $accountIds, Carbon $asOf): string
    {
        $totals = JournalLine::whereIn('account_id', $accountIds)
            ->whereHas('journal', fn ($query) => $query->posted()->whereDate('journal_date', '<=', $asOf->toDateString()))
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return bcsub((string) $totals->total_debit, (string) $totals->total_credit, 4);
    }
}
