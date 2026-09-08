<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\Budget;
use Modules\Finance\Models\BudgetLine;
use Modules\Finance\Jobs\SendBudgetAlertJob;
use Modules\Finance\Models\Account;
use Carbon\Carbon;

class BudgetService
{
    public function getBudgetVsActual(int $companyId, ?int $fiscalYearId = null, ?int $accountId = null, ?int $costCenterId = null): array
    {
        if (!$fiscalYearId) {
            $currentFiscalYear = \Modules\Core\Models\FiscalYear::where('company_id', $companyId)
                ->where('status', 'ACTIVE')
                ->first();

            if ($currentFiscalYear) {
                $fiscalYearId = $currentFiscalYear->id;
            } else {
                return [
                    'budget' => 0,
                    'actual' => 0,
                    'variance' => 0,
                    'variance_percent' => 0,
                    'lines' => [],
                ];
            }
        }

        $budget = Budget::with('fiscalYear')
            ->where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('status', Budget::STATUS_APPROVED)
            ->first();

        if (!$budget) {
            return [
                'budget' => 0,
                'actual' => 0,
                'variance' => 0,
                'variance_percent' => 0,
                'lines' => [],
            ];
        }

        $query = BudgetLine::where('budget_id', $budget->id);

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        if ($costCenterId) {
            $query->where('cost_center_id', $costCenterId);
        }

        $budgetLines = $query->get();

        $totalBudget = 0;
        $totalActual = 0;
        $lines = [];

        foreach ($budgetLines as $budgetLine) {
            $actual = $this->getActualSpending(
                $budgetLine->account_id,
                $budgetLine->cost_center_id,
                $budget->fiscalYear->start_date,
                $budget->fiscalYear->end_date
            );
            $budgetAmount = (float) $budgetLine->budget_amount;
            $variance = $budgetAmount - $actual;
            $variancePercent = $budgetAmount > 0 ? ($variance / $budgetAmount) * 100 : 0;

            $totalBudget += $budgetAmount;
            $totalActual += $actual;

            $lines[] = [
                'account' => $budgetLine->account,
                'cost_center' => $budgetLine->costCenter,
                'budget' => $budgetAmount,
                'actual' => $actual,
                'variance' => $variance,
                'variance_percent' => $variancePercent,
            ];
        }

        $totalVariance = $totalBudget - $totalActual;
        $totalVariancePercent = $totalBudget > 0 ? ($totalVariance / $totalBudget) * 100 : 0;

        return [
            'budget' => $totalBudget,
            'actual' => $totalActual,
            'variance' => $totalVariance,
            'variance_percent' => $totalVariancePercent,
            'lines' => $lines,
        ];
    }

    public function getActualSpending(int $accountId, ?int $costCenterId, string $startDate, string $endDate): float
    {
        $query = \Modules\Finance\Models\JournalLine::where('account_id', $accountId)
            ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'POSTED')
                  ->whereBetween('journal_date', [$startDate, $endDate]);
            });

        if ($costCenterId) {
            $query->where('cost_center_id', $costCenterId);
        }

        $totalDebit = (float) $query->clone()->sum('debit');
        $totalCredit = (float) $query->clone()->sum('credit');

        return $totalDebit - $totalCredit;
    }

    public function checkBudgetAvailability(int $accountId, ?int $costCenterId, int $fiscalYearId, float $requestedAmount): bool
    {
        $fiscalYear = \Modules\Core\Models\FiscalYear::findOrFail($fiscalYearId);

        $actualSpending = $this->getActualSpending(
            $accountId,
            $costCenterId,
            $fiscalYear->start_date,
            $fiscalYear->end_date
        );

        $budgetData = $this->getBudgetVsActual($accountId, $costCenterId, $fiscalYearId);
        
        return ($budgetData['actual'] + $requestedAmount) <= $budgetData['budget'];
    }

    public function getRemainingBudget(int $accountId, ?int $costCenterId, int $fiscalYearId): float
    {
        $budgetData = $this->getBudgetVsActual($accountId, $costCenterId, $fiscalYearId);
        
        return max(0, $budgetData['budget'] - $budgetData['actual']);
    }

    public function getBudgetById(int $id): ?Budget
    {
        return Budget::with(['fiscalYear', 'lines.account', 'lines.costCenter'])->find($id);
    }
}
