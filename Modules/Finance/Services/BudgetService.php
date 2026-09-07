<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\Budget;
use Modules\Finance\Models\BudgetLine;
use Modules\Finance\Models\Account;
use Carbon\Carbon;

class BudgetService
{
    public function getBudgetVsActual(int $companyId, int $fiscalYearId, ?int $accountId = null, ?int $costCenterId = null): array
    {
        $budget = Budget::where('company_id', $companyId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('status', 'active')
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
            $actual = $this->getActualSpending($budgetLine->account_id, $budgetLine->cost_center_id, $budget->fiscal_year_id);
            $budgetAmount = $budgetLine->budget_amount;
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

    public function getActualSpending(int $accountId, ?int $costCenterId, int $fiscalYearId): float
    {
        $fiscalYear = \Modules\Core\Models\FiscalYear::findOrFail($fiscalYearId);

        $query = \Modules\Finance\Models\JournalLine::where('account_id', $accountId)
            ->whereHas('journal', function ($q) use ($fiscalYear) {
                $q->where('company_id', $fiscalYear->company_id)
                  ->where('status', 'POSTED')
                  ->whereBetween('journal_date', [$fiscalYear->start_date, $fiscalYear->end_date]);
            });

        if ($costCenterId) {
            $query->where('cost_center_id', $costCenterId);
        }

        return $query->sum('debit') - $query->sum('credit');
    }

    public function checkBudgetAvailability(int $accountId, ?int $costCenterId, int $fiscalYearId, float $requestedAmount): bool
    {
        $budgetData = $this->getBudgetVsActual($accountId, $costCenterId, $fiscalYearId);
        
        return ($budgetData['actual'] + $requestedAmount) <= $budgetData['budget'];
    }

    public function getRemainingBudget(int $accountId, ?int $costCenterId, int $fiscalYearId): float
    {
        $budgetData = $this->getBudgetVsActual($accountId, $costCenterId, $fiscalYearId);
        
        return max(0, $budgetData['budget'] - $budgetData['actual']);
    }
}
