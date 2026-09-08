<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Scopes\CompanyScope;

class CustomerDebitNote extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'customer_invoice_id',
        'debit_note_number',
        'debit_note_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'reason',
        'status',
        'posted_by',
        'posted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'debit_note_date' => 'date',
        'posted_at' => 'datetime',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class, 'customer_invoice_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'posted_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function canBePosted(): bool
    {
        return in_array($this->status, ['approved']);
    }
}
