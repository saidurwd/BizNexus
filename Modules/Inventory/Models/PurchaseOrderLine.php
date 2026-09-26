<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Models\Tax;

/**
 * A product ordered, with how much of it has been received and invoiced. received_functional_value is what
 * the receipts credited to goods received not invoiced; invoiced_functional_value is how much of that the
 * matched supplier invoices have cleared.
 */
class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'tax_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'received_quantity',
        'received_functional_value',
        'invoiced_quantity',
        'invoiced_functional_value',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'received_functional_value' => 'decimal:4',
        'invoiced_quantity' => 'decimal:4',
        'invoiced_functional_value' => 'decimal:4',
    ];

    public function outstandingQuantity(): string
    {
        return bcsub((string) $this->quantity, (string) $this->received_quantity, 4);
    }

    public function uninvoicedQuantity(): string
    {
        return bcsub((string) $this->received_quantity, (string) $this->invoiced_quantity, 4);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
