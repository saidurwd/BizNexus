<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A signed quantity change for one product. For a stock count, system_quantity is what the books showed and
 * quantity the counted difference. unit_cost is an optional cost for increases; value is set when posted.
 */
class StockAdjustmentLine extends Model
{
    protected $fillable = ['stock_adjustment_id', 'product_id', 'system_quantity', 'quantity', 'unit_cost', 'value'];

    protected $casts = [
        'system_quantity' => 'decimal:4',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:6',
        'value' => 'decimal:4',
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
