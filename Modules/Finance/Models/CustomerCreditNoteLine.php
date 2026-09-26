<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCreditNoteLine extends Model
{
    protected $fillable = [
        'customer_credit_note_id',
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

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CustomerCreditNote::class, 'customer_credit_note_id');
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
