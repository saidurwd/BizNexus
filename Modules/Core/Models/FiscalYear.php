<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Enums\FiscalCalendarPattern;
use Modules\Finance\Models\Budget;

class FiscalYear extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'period_pattern',
        'closing_journal_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_pattern' => FiscalCalendarPattern::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }

    public function isClosed(): bool
    {
        return $this->status === 'CLOSED';
    }

    public function isLocked(): bool
    {
        return $this->status === 'LOCKED';
    }

    public function getCurrentPeriod(): ?FiscalPeriod
    {
        return $this->periods()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
