<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quantity received for one order line: value in the order currency, functional_value in the company's.
 */
class GoodsReceiptLine extends Model
{
    protected $fillable = ['goods_receipt_id', 'purchase_order_line_id', 'product_id', 'quantity', 'unit_price', 'value', 'functional_value'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'value' => 'decimal:4',
        'functional_value' => 'decimal:4',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
