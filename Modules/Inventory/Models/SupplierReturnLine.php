<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quantity returned on one order line. receipt_value is what goods received not invoiced is debited with
 * (the receipt's functional value for these units); stock_value is what left inventory at average cost.
 */
class SupplierReturnLine extends Model
{
    protected $fillable = ['supplier_return_id', 'purchase_order_line_id', 'product_id', 'quantity', 'receipt_value', 'stock_value'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'receipt_value' => 'decimal:4',
        'stock_value' => 'decimal:4',
    ];

    public function supplierReturn(): BelongsTo
    {
        return $this->belongsTo(SupplierReturn::class);
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
