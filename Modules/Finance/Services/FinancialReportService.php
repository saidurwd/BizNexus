<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
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

        $revenueQuery = JournalLine::whereHas('account', fn($q) => $q->where('account_type', 'REVENUE'))
            ->whereHas('journal', fn($q) => $q->where('company_id', $companyId)->posted());

        $expenseQuery = JournalLine::whereHas('account', fn($q) => $q->where('account_type', 'EXPENSE'))
            ->whereHas('journal', fn($q) => $q->where('company_id', $companyId)->posted());

        if ($fiscalPeriodId) {
            $revenueQuery->whereHas('journal', fn($q) => $q->where('fiscal_period_id', $fiscalPeriodId));
            $expenseQuery->whereHas('journal', fn($q) => $q->where('fiscal_period_id', $fiscalPeriodId));
        } else {
            $revenueQuery->whereHas('journal', fn($q) => $q->where('journal_date', '<=', $asOfDate));
            $expenseQuery->whereHas('journal', fn($q) => $q->where('journal_date', '<=', $asOfDate));
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
            ->whereHas('journal', fn($q) => $q->posted());

        if ($startDate) {
            $query->whereHas('journal', fn($q) => $q->where('journal_date', '>=', $startDate));
        }

        if ($endDate) {
            $query->whereHas('journal', fn($q) => $q->where('journal_date', '<=', $endDate));
        }

        if ($fiscalPeriodId) {
            $query->whereHas('journal', fn($q) => $q->where('fiscal_period_id', $fiscalPeriodId));
        }

        $totalDebit = (float) $query->clone()->sum('debit');
        $totalCredit = (float) $query->clone()->sum('credit');

        if ($account->isDebitNormal()) {
            return bcsub($totalDebit, $totalCredit, 4);
        }

        return bcsub($totalCredit, $totalDebit, 4);
    }

    public function getDashboardData(int $companyId): array
    {
        $asOfDate = Carbon::now();

        $assetAccounts = Account::where('company_id', $companyId)
            ->whereIn('account_type', ['ASSET'])
            ->postable()
            ->get();

        $revenueAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'REVENUE')
            ->postable()
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where('account_type', 'EXPENSE')
            ->postable()
            ->get();

        $liabilityAccounts = Account::where('company_id', $companyId)
            ->whereIn('account_type', ['LIABILITY'])
            ->postable()
            ->get();

        $totalAssets = 0;
        $totalRevenue = 0;
        $totalExpenses = 0;
        $totalLiabilities = 0;

        foreach ($assetAccounts as $account) {
            $totalAssets = bcadd($totalAssets, $this->getAccountBalance($account->id, null, $asOfDate, null), 4);
        }

        foreach ($revenueAccounts as $account) {
            $totalRevenue = bcadd($totalRevenue, $this->getAccountBalance($account->id, null, $asOfDate, null), 4);
        }

        foreach ($expenseAccounts as $account) {
            $totalExpenses = bcadd($totalExpenses, $this->getAccountBalance($account->id, null, $asOfDate, null), 4);
        }

        foreach ($liabilityAccounts as $account) {
            $totalLiabilities = bcadd($totalLiabilities, $this->getAccountBalance($account->id, null, $asOfDate, null), 4);
        }

        $cashAccount = Account::where('company_id', $companyId)
            ->where('account_code', 'like', '1110%')
            ->postable()
            ->first();

        $bankAccount = Account::where('company_id', $companyId)
            ->where('account_code', 'like', '1120%')
            ->postable()
            ->first();

        $receivableAccount = Account::where('company_id', $companyId)
            ->where('account_code', 'like', '1130%')
            ->postable()
            ->first();

        $payableAccount = Account::where('company_id', $companyId)
            ->where('account_code', 'like', '2100%')
            ->postable()
            ->first();

        return [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit' => bcsub($totalRevenue, $totalExpenses, 4),
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => bcsub($totalAssets, $totalLiabilities, 4),
            'cash_balance' => $cashAccount ? $this->getAccountBalance($cashAccount->id, null, $asOfDate, null) : 0,
            'bank_balance' => $bankAccount ? $this->getAccountBalance($bankAccount->id, null, $asOfDate, null) : 0,
            'accounts_receivable' => $receivableAccount ? $this->getAccountBalance($receivableAccount->id, null, $asOfDate, null) : 0,
            'accounts_payable' => $payableAccount ? $this->getAccountBalance($payableAccount->id, null, $asOfDate, null) : 0,
        ];
    }

    public function getCashFlow(int $companyId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?: Carbon::now()->startOfMonth();
        $endDate = $endDate ?: Carbon::now()->endOfMonth();

        $operatingActivities = [];
        $investingActivities = [];
        $financingActivities = [];

        $operatingTotal = 0;
        $investingTotal = 0;
        $financingTotal = 0;

        $journalLines = JournalLine::whereHas('journal', function ($q) use ($companyId, $startDate, $endDate) {
                $q->where('company_id', $companyId)
                  ->where('status', 'POSTED')
                  ->whereBetween('journal_date', [$startDate, $endDate]);
            })
            ->with('account')
            ->get();

        foreach ($journalLines as $line) {
            $amount = (float) $line->debit - (float) $line->credit;
            $accountCode = $line->account->account_code ?? '';

            if (str_starts_with($accountCode, '1110') || str_starts_with($accountCode, '1120')) {
                $operatingActivities[] = [
                    'description' => $line->description ?? $line->account->account_name ?? 'Operating Activity',
                    'amount' => $amount,
                ];
                $operatingTotal += $amount;
            } elseif (str_starts_with($accountCode, '1200') || str_starts_with($accountCode, '1300')) {
                $investingActivities[] = [
                    'description' => $line->description ?? $line->account->account_name ?? 'Investing Activity',
                    'amount' => $amount,
                ];
                $investingTotal += $amount;
            } elseif (str_starts_with($accountCode, '2100') || str_starts_with($accountCode, '2200')) {
                $financingActivities[] = [
                    'description' => $line->description ?? $line->account->account_name ?? 'Financing Activity',
                    'amount' => $amount,
                ];
                $financingTotal += $amount;
            }
        }

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'operating_activities' => $operatingActivities,
            'investing_activities' => $investingActivities,
            'financing_activities' => $financingActivities,
            'operating_total' => $operatingTotal,
            'investing_total' => $investingTotal,
            'financing_total' => $financingTotal,
            'net_change' => $operatingTotal + $investingTotal + $financingTotal,
        ];
    }
}
