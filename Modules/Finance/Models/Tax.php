<?php


namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Scopes\CompanyScope;

class Tax extends Model
{

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
    protected $fillable = [
        'company_id',
        'tax_code',
        'tax_name',
        'tax_type',
        'rate',
        'is_inclusive',
        'input_account_id',
        'output_account_id',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_inclusive' => 'boolean',
    ];

    public const TYPE_VAT = 'VAT';
    public const TYPE_WITHHOLDING_TAX = 'WITHHOLDING_TAX';
    public const TYPE_INCOME_TAX = 'INCOME_TAX';
    public const TYPE_OTHER = 'OTHER';

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function inputAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'input_account_id');
    }

    public function outputAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'output_account_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function calculateTax(float $amount): float
    {
        if ($this->is_inclusive) {
            return (float) bcmul($amount, bcdiv($this->rate, bcadd(100, $this->rate, 4), 4), 4);
        }

        return (float) bcmul($amount, bcdiv($this->rate, 100, 4), 4);
    }

    public function calculateTaxExclusive(float $amount): float
    {
        if ($this->is_inclusive) {
            return $amount;
        }

        return (float) bcmul($amount, bcdiv($this->rate, 100, 4), 4);
    }

    public function calculateGrossFromNet(float $netAmount): float
    {
        if ($this->is_inclusive) {
            return $netAmount;
        }

        return (float) bcmul($netAmount, bcadd(1, bcdiv($this->rate, 100, 4), 4), 4);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('tax_type', $type);
    }
}
