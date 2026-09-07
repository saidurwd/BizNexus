<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Account;
use Carbon\Carbon;

class LedgerService
{
    public function getAccountStatement(
        int $accountId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $fiscalPeriodId = null
    ): array {
        $account = Account::findOrFail($accountId);

        $query = JournalLine::with(['journal', 'costCenter', 'department', 'branch'])
            ->where('account_id', $accountId)
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

        $lines = $query->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->orderBy('journals.journal_date', 'asc')
            ->orderBy('journals.id', 'asc')
            ->select('journal_lines.*')
            ->get();

        $openingBalance = $this->calculateOpeningBalance($account, $startDate, $endDate, $fiscalPeriodId);

        $runningBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        $entries = [];
        foreach ($lines as $line) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;

            if ($account->isDebitNormal()) {
                $runningBalance = bcadd($runningBalance, bcsub($debit, $credit, 4), 4);
            } else {
                $runningBalance = bcadd($runningBalance, bcsub($credit, $debit, 4), 4);
            }

            $totalDebit = bcadd($totalDebit, $debit, 4);
            $totalCredit = bcadd($totalCredit, $credit, 4);

            $entries[] = [
                'date' => $line->journal->journal_date,
                'journal_number' => $line->journal->journal_number,
                'description' => $line->description ?? $line->journal->description,
                'reference' => $line->reference,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
                'cost_center' => $line->costCenter?->name,
                'department' => $line->department?->name,
                'branch' => $line->branch?->name,
            ];
        }

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->account_code,
                'name' => $account->account_name,
                'type' => $account->account_type,
                'normal_balance' => $account->normal_balance,
            ],
            'period' => [
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
                'fiscal_period_id' => $fiscalPeriodId,
            ],
            'opening_balance' => $openingBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $runningBalance,
            'entries' => $entries,
        ];
    }

    protected function calculateOpeningBalance(
        Account $account,
        ?Carbon $startDate,
        ?Carbon $endDate,
        ?int $fiscalPeriodId
    ): float {
        $query = JournalLine::where('account_id', $account->id)
            ->whereHas('journal', fn($q) => $q->posted());

        if ($startDate) {
            $query->whereHas('journal', fn($q) => $q->where('journal_date', '<', $startDate));
        }

        if ($endDate) {
            $query->whereDoesntHave('journal', fn($q) => $q->where('journal_date', '>=', $endDate));
        }

        if ($fiscalPeriodId) {
            $query->whereHas('journal', fn($q) => $q->where('fiscal_period_id', '<', $fiscalPeriodId));
        }

        $totalDebit = (float) $query->clone()->sum('debit');
        $totalCredit = (float) $query->clone()->sum('credit');

        if ($account->isDebitNormal()) {
            return bcsub($totalDebit, $totalCredit, 4);
        }

        return bcsub($totalCredit, $totalDebit, 4);
    }

    public function getTrialBalance(int $companyId, ?int $fiscalPeriodId = null, ?Carbon $date = null): array
    {
        $query = Account::where('company_id', $companyId)
            ->postable()
            ->with(['journalLines' => function ($q) use ($fiscalPeriodId, $date) {
                $q->whereHas('journal', fn($j) => $j->posted());

                if ($fiscalPeriodId) {
                    $q->whereHas('journal', fn($j) => $j->where('fiscal_period_id', $fiscalPeriodId));
                }

                if ($date) {
                    $q->whereHas('journal', fn($j) => $j->where('journal_date', '<=', $date));
                }
            }]);

        $accounts = $query->get();

        $totalDebit = 0;
        $totalCredit = 0;
        $accountBalances = [];

        foreach ($accounts as $account) {
            $debit = 0;
            $credit = 0;

            foreach ($account->journalLines as $line) {
                $debit = bcadd($debit, $line->debit, 4);
                $credit = bcadd($credit, $line->credit, 4);
            }

            if ($account->isDebitNormal()) {
                $balance = bcsub($debit, $credit, 4);
                if (bccomp($balance, 0, 4) >= 0) {
                    $totalDebit = bcadd($totalDebit, $balance, 4);
                } else {
                    $totalCredit = bcadd($totalCredit, abs((float) $balance), 4);
                }
            } else {
                $balance = bcsub($credit, $debit, 4);
                if (bccomp($balance, 0, 4) >= 0) {
                    $totalCredit = bcadd($totalCredit, $balance, 4);
                } else {
                    $totalDebit = bcadd($totalDebit, abs((float) $balance), 4);
                }
            }

            $accountBalances[] = [
                'account_id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        }

        $isBalanced = bccomp($totalDebit, $totalCredit, 4) === 0;

        return [
            'company_id' => $companyId,
            'fiscal_period_id' => $fiscalPeriodId,
            'date' => $date?->format('Y-m-d'),
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => $isBalanced,
            'difference' => bcsub($totalDebit, $totalCredit, 4),
            'accounts' => $accountBalances,
        ];
    }

    public function getGeneralLedger(
        int $companyId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $fiscalPeriodId = null,
        ?int $accountId = null
    ): array {
        $query = JournalLine::with(['journal', 'account', 'costCenter'])
            ->whereHas('journal', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->posted();
            })
            ->whereHas('account', fn($q) => $q->postable());

        if ($startDate) {
            $query->whereHas('journal', fn($q) => $q->where('journal_date', '>=', $startDate));
        }

        if ($endDate) {
            $query->whereHas('journal', fn($q) => $q->where('journal_date', '<=', $endDate));
        }

        if ($fiscalPeriodId) {
            $query->whereHas('journal', fn($q) => $q->where('fiscal_period_id', $fiscalPeriodId));
        }

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        $lines = $query->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->orderBy('journals.journal_date', 'asc')
            ->orderBy('journals.id', 'asc')
            ->select('journal_lines.*')
            ->get();

        $groupedLines = [];
        foreach ($lines as $line) {
            $key = $line->account_id;
            if (!isset($groupedLines[$key])) {
                $groupedLines[$key] = [
                    'account' => [
                        'id' => $line->account->id,
                        'code' => $line->account->account_code,
                        'name' => $line->account->account_name,
                    ],
                    'entries' => [],
                    'total_debit' => 0,
                    'total_credit' => 0,
                ];
            }

            $groupedLines[$key]['entries'][] = [
                'date' => $line->journal->journal_date,
                'journal_number' => $line->journal->journal_number,
                'description' => $line->description ?? $line->journal->description,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'cost_center' => $line->costCenter?->name,
            ];

            $groupedLines[$key]['total_debit'] = bcadd($groupedLines[$key]['total_debit'], $line->debit, 4);
            $groupedLines[$key]['total_credit'] = bcadd($groupedLines[$key]['total_credit'], $line->credit, 4);
        }

        return [
            'company_id' => $companyId,
            'period' => [
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
                'fiscal_period_id' => $fiscalPeriodId,
            ],
            'accounts' => array_values($groupedLines),
        ];
    }
}
