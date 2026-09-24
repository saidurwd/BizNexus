<?php

namespace Modules\Core\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\FiscalCalendarPattern;
use Modules\Core\Exceptions\ClosedPeriodException;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;

class AccountingPeriodService
{
    /**
     * The period containing the date: a regular period, or the year's adjustment period when requested.
     */
    public function getPeriodForDate(int $companyId, Carbon $date, bool $adjustmentPeriod = false): ?FiscalPeriod
    {
        return FiscalPeriod::whereHas('fiscalYear', fn ($q) => $q->where('company_id', $companyId))
            ->where('is_adjustment', $adjustmentPeriod)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->first();
    }

    public function validateDateForPosting(int $companyId, Carbon $date, bool $adjustmentPeriod = false): FiscalPeriod
    {
        $period = $this->getPeriodForDate($companyId, $date, $adjustmentPeriod);

        if (! $period) {
            throw new ClosedPeriodException(
                new FiscalPeriod(['period_name' => ($adjustmentPeriod ? 'No adjustment period found for date: ' : 'No period found for date: ').$date->format('Y-m-d')]),
                'post'
            );
        }

        if (! $period->canAcceptPosting()) {
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

        return $period;
    }

    public function lockPeriod(int $periodId): FiscalPeriod
    {
        $period = FiscalPeriod::findOrFail($periodId);

        $period->update(['status' => 'LOCKED']);

        return $period;
    }

    /**
     * Create the regular periods of the year following its calendar pattern, plus the adjustment period
     * on the last day of the year used for audit adjustments and the year-end closing entry.
     *
     * @return array<int, FiscalPeriod>
     */
    public function createFiscalYearPeriods(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $pattern = $fiscalYear->period_pattern ?? FiscalCalendarPattern::Monthly;
        $yearEnd = $fiscalYear->end_date->copy()->startOfDay();
        $periodStart = $fiscalYear->start_date->copy()->startOfDay();
        $weeks = $pattern->weeksPerPeriod();
        $periods = [];

        for ($number = 1; $periodStart->lte($yearEnd); $number++) {
            $isLast = $weeks !== null && $number === count($weeks);
            $periodEnd = match (true) {
                $weeks === null => $periodStart->copy()->endOfMonth()->startOfDay(),
                $isLast => $yearEnd->copy(),
                default => $periodStart->copy()->addWeeks($weeks[$number - 1])->subDay(),
            };
            $periodEnd = $periodEnd->gt($yearEnd) ? $yearEnd->copy() : $periodEnd;

            $periods[] = FiscalPeriod::create([
                'fiscal_year_id' => $fiscalYearId,
                'period_name' => $weeks === null ? $periodStart->format('F Y') : 'P'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'period_number' => $number,
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
                'status' => 'OPEN',
            ]);

            $periodStart = $periodEnd->copy()->addDay();
        }

        $periods[] = $this->ensureAdjustmentPeriod($fiscalYear);

        return $periods;
    }

    /**
     * The year's adjustment period (last day of the year), created when it does not exist yet.
     */
    public function ensureAdjustmentPeriod(FiscalYear $fiscalYear): FiscalPeriod
    {
        return FiscalPeriod::firstOrCreate(
            ['fiscal_year_id' => $fiscalYear->id, 'is_adjustment' => true],
            [
                'period_name' => 'Adjustment '.$fiscalYear->name,
                'period_number' => (int) FiscalPeriod::where('fiscal_year_id', $fiscalYear->id)->max('period_number') + 1,
                'start_date' => $fiscalYear->end_date->toDateString(),
                'end_date' => $fiscalYear->end_date->toDateString(),
                'status' => 'OPEN',
            ]
        );
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

        if ($fiscalYear->isLocked() || $fiscalYear->isClosed()) {
            return false;
        }

        return $period->isClosed();
    }

    public function reopenPeriod(int $periodId, int $userId): FiscalPeriod
    {
        $period = FiscalPeriod::findOrFail($periodId);

        if (! $this->canReopenPeriod($period)) {
            throw new \Exception("Cannot reopen period: {$period->period_name}");
        }

        DB::transaction(function () use ($period) {
            $period->update([
                'status' => 'OPEN',
                'closed_at' => null,
                'closed_by' => null,
            ]);

        });

        return $period->fresh();
    }
}
