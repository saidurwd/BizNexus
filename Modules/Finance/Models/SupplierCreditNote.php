<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Scopes\CompanyScope;

class SupplierCreditNote extends Model
{
    protected $fillable = [
        'company_id',
        'supplier_id',
        'supplier_invoice_id',
        'credit_note_number',
        'credit_note_date',
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
        'credit_note_date' => 'date',
        'posted_at' => 'datetime',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
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
