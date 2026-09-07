<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends Model
{
    protected $fillable = [
        'fiscal_year_id',
        'period_name',
        'period_number',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['CLOSED', 'LOCKED']);
    }

    public function isClosing(): bool
    {
        return $this->status === 'CLOSING';
    }

    public function canAcceptPosting(): bool
    {
        return $this->status === 'OPEN';
    }

    public function containsDate($date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['CLOSED', 'LOCKED']);
    }
}
