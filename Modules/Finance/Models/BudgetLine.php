<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    protected $fillable = [
        'budget_id',
        'account_id',
        'cost_center_id',
        'period',
        'budget_amount',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:4',
        'period' => 'integer',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\CostCenter::class);
    }

    public function getActualAmount(): float
    {
        $query = JournalLine::query()
            ->where('account_id', $this->account_id)
            ->where('cost_center_id', $this->cost_center_id)
            ->whereHas('journal', fn($q) => $q->posted())
            ->whereHas('journal.fiscalPeriod', fn($q) => $q->where('period_number', $this->period));

        $totalDebit = (float) $query->clone()->sum('debit');
        $totalCredit = (float) $query->clone()->sum('credit');

        return $totalDebit - $totalCredit;
    }

    public function getVariance(): float
    {
        return (float) bcsub($this->budget_amount, $this->getActualAmount(), 4);
    }

    public function getVariancePercentage(): float
    {
        if ((float) $this->budget_amount === 0.0) {
            return 0.0;
        }

        return (float) bcmul(
            bcdiv($this->getVariance(), $this->budget_amount, 4),
            100,
            2
        );
    }
}
