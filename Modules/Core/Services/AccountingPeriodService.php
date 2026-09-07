<?php

namespace Modules\Core\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Exceptions\ClosedPeriodException;

class AccountingPeriodService
{
    public function getPeriodForDate(int $companyId, Carbon $date): ?FiscalPeriod
    {
        return FiscalPeriod::whereHas('fiscalYear', fn($q) => $q->where('company_id', $companyId))
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    public function validateDateForPosting(int $companyId, Carbon $date): FiscalPeriod
    {
        $period = $this->getPeriodForDate($companyId, $date);

        if (!$period) {
            throw new ClosedPeriodException(
                new FiscalPeriod(['period_name' => 'No period found for date: ' . $date->format('Y-m-d')]),
                'post'
            );
        }

        if (!$period->canAcceptPosting()) {
            throw new ClosedPeriodException($period, 'post');
        }

        return $period;
    }

    public function openPeriod(int $periodId): FiscalPeriod
    {
        $period = FiscalPeriod::findOrFail($periodId);

        if ($period->isClosed()) {
            throw new ClosedPeriodException($period, 'open');
        }

        $period->update([
            'status' => 'OPEN',
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return $period;
    }

    public function closePeriod(int $periodId, int $userId): FiscalPeriod
    {
        $period = FiscalPeriod::findOrFail($periodId);

        $period->update([
            'status' => 'CLOSED',
            'closed_at' => now(),
            'closed_by' => $userId,
        ]);

        $this->closeFiscalYearIfAllPeriodsClosed($period->fiscalYear);

        return $period;
    }

    public function lockPeriod(int $periodId): FiscalPeriod
    {
        $period = FiscalPeriod::findOrFail($periodId);

        $period->update(['status' => 'LOCKED']);

        return $period;
    }

    protected function closeFiscalYearIfAllPeriodsClosed(FiscalYear $fiscalYear): void
    {
        $allClosed = $fiscalYear->periods()
            ->whereNotIn('status', ['CLOSED', 'LOCKED'])
            ->count() === 0;

        if ($allClosed) {
            $fiscalYear->update(['status' => 'CLOSED']);
        }
    }

    public function createFiscalYearPeriods(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $periods = [];
        $startDate = $fiscalYear->start_date->copy();
        $endDate = $fiscalYear->end_date;

        $periodNumber = 1;

        while ($startDate->lt($endDate)) {
            $periodEnd = $startDate->copy()->endOfMonth();

            if ($periodEnd->gt($endDate)) {
                $periodEnd = $endDate;
            }

            $periods[] = FiscalPeriod::create([
                'fiscal_year_id' => $fiscalYearId,
                'period_name' => $startDate->format('F'),
                'period_number' => $periodNumber,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $periodEnd->format('Y-m-d'),
                'status' => 'OPEN',
            ]);

            $startDate = $periodEnd->copy()->addDay();
            $periodNumber++;
        }

        return $periods;
    }

    public function getPeriodsForFiscalYear(int $fiscalYearId): array
    {
        return FiscalPeriod::where('fiscal_year_id', $fiscalYearId)
            ->orderBy('period_number')
            ->get()
            ->toArray();
    }

    public function canReopenPeriod(FiscalPeriod $period): bool
    {
        $fiscalYear = $period->fiscalYear;

        if ($fiscalYear->isLocked()) {
            return false;
        }

        return $period->isClosed();
    }

    public function reopenPeriod(int $periodId, int $userId): FiscalPeriod
    {
        $period = FiscalPeriod::findOrFail($periodId);

        if (!$this->canReopenPeriod($period)) {
            throw new \Exception("Cannot reopen period: {$period->period_name}");
        }

        DB::transaction(function () use ($period, $userId) {
            $period->update([
                'status' => 'OPEN',
                'closed_at' => null,
                'closed_by' => null,
            ]);

            $fiscalYear = $period->fiscalYear;
            if ($fiscalYear->status === 'CLOSED') {
                $fiscalYear->update(['status' => 'OPEN']);
            }
        });

        return $period->fresh();
    }
}
