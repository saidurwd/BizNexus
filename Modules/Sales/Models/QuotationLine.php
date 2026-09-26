<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Models\Product;

class QuotationLine extends Model
{
    protected $table = 'sales_quotation_lines';

    protected $fillable = ['sales_quotation_id', 'product_id', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_id', 'subtotal', 'tax_amount', 'total_amount'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'sales_quotation_id');
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
