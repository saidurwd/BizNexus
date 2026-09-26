<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\PurchaseOrderLine;

class SupplierInvoiceLine extends Model
{
    protected $fillable = [
        'supplier_invoice_id',
        'purchase_order_line_id',
        'product_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_id',
        'supply_type',
        'is_reverse_charge',
        'tax_amount',
        'discount_amount',
        'total_amount',
    ];

    protected $casts = [
        'is_reverse_charge' => 'boolean',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
