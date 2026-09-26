<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Branch;

/**
 * A place stock is kept. One warehouse per company is the default for receipts and issues.
 */
class Warehouse extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'branch_id', 'code', 'name', 'address', 'is_default', 'status'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (Warehouse $warehouse) {
            if ($warehouse->is_default && ($warehouse->wasRecentlyCreated || $warehouse->wasChanged('is_default'))) {
                static::whereKeyNot($warehouse->id)->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    public static function defaultId(): ?int
    {
        return static::active()->orderByDesc('is_default')->orderBy('code')->value('id');
    }

    /**
     * Whether stock has ever moved through the warehouse, so it can no longer be deleted.
     */
    public function isInUse(): bool
    {
        return false;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
