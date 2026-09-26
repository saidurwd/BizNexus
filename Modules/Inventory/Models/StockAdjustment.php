<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Models\Journal;

/**
 * A change to stock in one warehouse that is not a purchase or sale: opening stock, a stock count, damage or
 * loss. Increases are valued at the cost entered (or the current average), decreases at the average cost;
 * posting debits or credits inventory against the inventory adjustment account.
 */
class StockAdjustment extends Model
{
    use BelongsToCompany;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_POSTED = 'POSTED';

    public const REASON_OPENING = 'opening';

    public const REASON_COUNT = 'count';

    public const REASON_DAMAGE = 'damage';

    public const REASON_LOSS = 'loss';

    public const REASON_OTHER = 'other';

    public const REASONS = [self::REASON_OPENING, self::REASON_COUNT, self::REASON_DAMAGE, self::REASON_LOSS, self::REASON_OTHER];

    protected $fillable = [
        'company_id',
        'adjustment_number',
        'warehouse_id',
        'adjustment_date',
        'reason',
        'notes',
        'status',
        'journal_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public static function reasonLabels(): array
    {
        return [
            self::REASON_OPENING => __('Opening stock'),
            self::REASON_COUNT => __('Stock count'),
            self::REASON_DAMAGE => __('Damaged goods'),
            self::REASON_LOSS => __('Loss or theft'),
            self::REASON_OTHER => __('Other'),
        ];
    }

    public function reasonLabel(): string
    {
        return self::reasonLabels()[$this->reason] ?? $this->reason;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class)->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
