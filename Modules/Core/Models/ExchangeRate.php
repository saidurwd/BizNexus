<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExchangeRate extends Model
{
    protected $fillable = [
        'company_id',
        'currency_id',
        'rate_date',
        'exchange_rate',
        'source',
        'status',
        'created_by',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'exchange_rate' => 'decimal:8',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('rate_date', '<=', $date)
            ->orderBy('rate_date', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
