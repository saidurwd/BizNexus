<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Enums\ExchangeRateType;

/**
 * Functional-currency units of the company per one unit of the foreign currency, for a date and rate type.
 */
class ExchangeRate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'currency_id',
        'rate_date',
        'rate_type',
        'exchange_rate',
        'source',
        'status',
        'created_by',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'rate_type' => ExchangeRateType::class,
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
