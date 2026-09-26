<?php

namespace Modules\Assets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Models\Journal;

/**
 * An event in an asset's life. amount is the effect on cost (acquisition, disposal) or on accumulated
 * depreciation (depreciation, impairment, and the accumulated depreciation removed on disposal, kept in
 * details); transfers carry no amount.
 */
class AssetTransaction extends Model
{
    use BelongsToCompany;

    public const TYPE_ACQUISITION = 'acquisition';

    public const TYPE_OPENING_DEPRECIATION = 'opening_depreciation';

    public const TYPE_DEPRECIATION = 'depreciation';

    public const TYPE_IMPAIRMENT = 'impairment';

    public const TYPE_DISPOSAL = 'disposal';

    public const TYPE_TRANSFER = 'transfer';

    protected $fillable = ['company_id', 'fixed_asset_id', 'type', 'transaction_date', 'amount', 'depreciation_run_id', 'journal_id', 'notes', 'details', 'created_by'];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:4',
        'details' => 'array',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_ACQUISITION => __('Acquisition'),
            self::TYPE_OPENING_DEPRECIATION => __('Opening accumulated depreciation'),
            self::TYPE_DEPRECIATION => __('Depreciation'),
            self::TYPE_IMPAIRMENT => __('Impairment'),
            self::TYPE_DISPOSAL => __('Disposal'),
            self::TYPE_TRANSFER => __('Transfer'),
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
