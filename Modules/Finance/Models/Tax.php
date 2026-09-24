<?php

namespace Modules\Finance\Models;

use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Company;

class Tax extends Model
{
    use BelongsToCompany, HasFactory;

    protected static function newFactory()
    {
        return TaxFactory::new();
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
        return $this->belongsTo(Company::class);
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

    /**
     * Intermediate precision for tax arithmetic; results are rounded half-up to 4 places only once.
     */
    protected const CALCULATION_SCALE = 12;

    public function calculateTax(float|string $amount): float
    {
        $amount = (string) $amount;
        $divisor = $this->is_inclusive ? bcadd('100', (string) $this->rate, self::CALCULATION_SCALE) : '100';

        return (float) bcround(
            bcdiv(bcmul($amount, (string) $this->rate, self::CALCULATION_SCALE), $divisor, self::CALCULATION_SCALE),
            4
        );
    }

    public function calculateTaxExclusive(float|string $amount): float
    {
        if ($this->is_inclusive) {
            return (float) $amount;
        }

        return $this->calculateTax($amount);
    }

    public function calculateGrossFromNet(float|string $netAmount): float
    {
        if ($this->is_inclusive) {
            return (float) $netAmount;
        }

        return (float) bcadd((string) $netAmount, (string) $this->calculateTax($netAmount), 4);
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
