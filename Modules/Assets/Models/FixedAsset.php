<?php

namespace Modules\Assets\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Department;
use Modules\Finance\Models\Supplier;

/**
 * An item of property, plant and equipment (IAS 16), carried at cost less accumulated depreciation and
 * impairment. Depreciation runs monthly from the month it is put into service; depreciated_until is the last
 * month end charged. Amounts are in the company's functional currency.
 */
class FixedAsset extends Model
{
    use BelongsToCompany;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_FULLY_DEPRECIATED = 'FULLY_DEPRECIATED';

    public const STATUS_DISPOSED = 'DISPOSED';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_FULLY_DEPRECIATED, self::STATUS_DISPOSED];

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_SUPPLIER_INVOICE_LINE = 'supplier_invoice_line';

    public const SOURCE_GOODS_RECEIPT_LINE = 'goods_receipt_line';

    protected $fillable = [
        'company_id',
        'asset_number',
        'name',
        'description',
        'category_id',
        'branch_id',
        'department_id',
        'location',
        'custodian',
        'serial_number',
        'tag',
        'supplier_id',
        'acquisition_date',
        'in_service_date',
        'cost',
        'residual_value',
        'depreciation_method',
        'useful_life_months',
        'declining_rate',
        'accumulated_depreciation',
        'months_depreciated',
        'depreciated_until',
        'status',
        'source_type',
        'source_id',
        'disposed_on',
        'disposal_proceeds',
        'created_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'in_service_date' => 'date',
        'depreciated_until' => 'date',
        'disposed_on' => 'date',
        'cost' => 'decimal:4',
        'residual_value' => 'decimal:4',
        'declining_rate' => 'decimal:4',
        'accumulated_depreciation' => 'decimal:4',
        'disposal_proceeds' => 'decimal:4',
        'useful_life_months' => 'integer',
        'months_depreciated' => 'integer',
    ];

    public function bookValue(): string
    {
        return bcsub((string) $this->cost, (string) $this->accumulated_depreciation, 4);
    }

    /**
     * What is still to be depreciated: book value less residual value.
     */
    public function depreciableAmount(): string
    {
        $amount = bcsub($this->bookValue(), (string) $this->residual_value, 4);

        return bccomp($amount, '0', 4) > 0 ? $amount : '0';
    }

    public function remainingLifeMonths(): int
    {
        return max(0, $this->useful_life_months - $this->months_depreciated);
    }

    public function isDepreciating(): bool
    {
        return $this->status === self::STATUS_ACTIVE && bccomp($this->depreciableAmount(), '0', 4) > 0;
    }

    /**
     * The month end of the next month to charge.
     */
    public function nextDepreciationMonth(): CarbonImmutable
    {
        return $this->depreciated_until
            ? CarbonImmutable::parse($this->depreciated_until)->startOfMonth()->addMonth()->endOfMonth()->startOfDay()
            : CarbonImmutable::parse($this->in_service_date)->endOfMonth()->startOfDay();
    }

    /**
     * Depreciation for the next month, unrounded. Straight line spreads what is left to depreciate evenly over
     * the remaining months (so impairments and opening balances are absorbed); declining balance applies the
     * annual rate to the book value. The last month of the useful life takes whatever is left.
     */
    public function nextMonthDepreciation(): string
    {
        $depreciable = $this->depreciableAmount();

        if (bccomp($depreciable, '0', 4) <= 0) {
            return '0';
        }

        if ($this->remainingLifeMonths() <= 1) {
            return $depreciable;
        }

        $charge = $this->depreciation_method === AssetCategory::METHOD_DECLINING_BALANCE
            ? bcdiv(bcmul($this->bookValue(), (string) ($this->declining_rate ?? '0'), 8), '1200', 8)
            : bcdiv($depreciable, (string) $this->remainingLifeMonths(), 8);

        return bccomp($charge, $depreciable, 8) > 0 ? $depreciable : $charge;
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => __('In use'),
            self::STATUS_FULLY_DEPRECIATED => __('Fully depreciated'),
            self::STATUS_DISPOSED => __('Disposed'),
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AssetTransaction::class)->orderByDesc('transaction_date')->orderByDesc('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
