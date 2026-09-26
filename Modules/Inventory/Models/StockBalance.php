<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;

/**
 * Quantity of a product in one warehouse. Value is kept per product (company-wide weighted average).
 */
class StockBalance extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'product_id', 'warehouse_id', 'quantity'];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
