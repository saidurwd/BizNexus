<?php

namespace Modules\Finance\Models;

use Carbon\CarbonInterface;
use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'country_code',
        'region_code',
        'rate',
        'is_inclusive',
        'is_group',
        'is_recoverable',
        'input_account_id',
        'output_account_id',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_inclusive' => 'boolean',
        'is_group' => 'boolean',
        'is_recoverable' => 'boolean',
    ];

    public const TYPE_VAT = 'VAT';

    public const TYPE_WITHHOLDING_TAX = 'WITHHOLDING_TAX';

    public const TYPE_INCOME_TAX = 'INCOME_TAX';

    public const TYPE_OTHER = 'OTHER';

    public const TYPE_SALES_TAX = 'SALES_TAX';

    public const TYPE_EXCISE = 'EXCISE';

    public const TYPES = [self::TYPE_VAT, self::TYPE_SALES_TAX, self::TYPE_EXCISE, self::TYPE_WITHHOLDING_TAX, self::TYPE_INCOME_TAX, self::TYPE_OTHER];

    protected static function booted(): void
    {
        static::created(function (Tax $tax) {
            if (! $tax->rates()->exists()) {
                $tax->rates()->create(['rate' => $tax->rate ?? 0, 'effective_from' => '1900-01-01']);
            }
        });
    }

    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    /**
     * Components of a tax group in calculation order; pivot is_compound marks tax charged on earlier taxes.
     */
    public function components(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'tax_group_components', 'group_tax_id', 'component_tax_id')
            ->withPivot(['sequence', 'is_compound'])
            ->orderByPivot('sequence');
    }

    /**
     * The rate (percent) in force on the date.
     */
    public function rateOn(CarbonInterface $date): string
    {
        $rate = $this->rates()
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString()))
            ->orderByDesc('effective_from')
            ->value('rate');

        return (string) ($rate ?? $this->rate ?? '0');
    }

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
