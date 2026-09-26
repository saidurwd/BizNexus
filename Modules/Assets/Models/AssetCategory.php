<?php

namespace Modules\Assets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Models\Account;

/**
 * A class of assets (IAS 16) with its ledger accounts and default depreciation policy.
 */
class AssetCategory extends Model
{
    use BelongsToCompany;

    public const METHOD_STRAIGHT_LINE = 'straight_line';

    public const METHOD_DECLINING_BALANCE = 'declining_balance';

    public const METHODS = [self::METHOD_STRAIGHT_LINE, self::METHOD_DECLINING_BALANCE];

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'disposal_account_id',
        'depreciation_method',
        'useful_life_months',
        'declining_rate',
        'status',
    ];

    protected $casts = [
        'useful_life_months' => 'integer',
        'declining_rate' => 'decimal:4',
    ];

    public static function methodLabels(): array
    {
        return [
            self::METHOD_STRAIGHT_LINE => __('Straight line'),
            self::METHOD_DECLINING_BALANCE => __('Declining balance'),
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'category_id');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function disposalAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'disposal_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
