<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoiceLine extends Model
{
    protected $fillable = [
        'supplier_invoice_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_id',
        'tax_amount',
        'discount_amount',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];

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

    public function calculateTotals(): void
    {
        $this->subtotal = (float) bcmul($this->quantity, $this->unit_price, 4);
        $this->subtotal = (float) bcsub($this->subtotal, $this->discount_amount ?? 0, 4);

        if ($this->tax) {
            $this->tax_amount = $this->tax->is_inclusive
                ? (float) bcmul($this->subtotal, bcdiv($this->tax->rate, bcadd(100, $this->tax->rate, 4), 4), 4)
                : (float) bcmul($this->subtotal, bcdiv($this->tax->rate, 100, 4), 4);
        }

        $this->total_amount = (float) bcadd($this->subtotal, $this->tax_amount ?? 0, 4);
    }
}
