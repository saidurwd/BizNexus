<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Models\Product;

/**
 * A product ordered, with how much has been delivered and invoiced. Services are invoiced without delivery.
 * delivered_cost_value is the stock cost the deliveries moved to goods delivered not invoiced;
 * invoiced_cost_value is how much of it the matched invoices have moved on to cost of goods sold.
 */
class SalesOrderLine extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'delivered_quantity',
        'delivered_cost_value',
        'invoiced_quantity',
        'invoiced_cost_value',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'delivered_quantity' => 'decimal:4',
        'delivered_cost_value' => 'decimal:4',
        'invoiced_quantity' => 'decimal:4',
        'invoiced_cost_value' => 'decimal:4',
    ];

    /**
     * Goods are delivered before they are invoiced; services are not delivered.
     */
    public function needsDelivery(): bool
    {
        return $this->product?->type !== Product::TYPE_SERVICE;
    }

    public function undeliveredQuantity(): string
    {
        return bcsub((string) $this->quantity, (string) $this->delivered_quantity, 4);
    }

    /**
     * What can be invoiced now: goods delivered and not yet invoiced, or services ordered and not yet invoiced.
     */
    public function billableQuantity(): string
    {
        $limit = $this->needsDelivery() ? (string) $this->delivered_quantity : (string) $this->quantity;

        return bcsub($limit, (string) $this->invoiced_quantity, 4);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
