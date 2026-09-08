<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Scopes\CompanyScope;

class CustomerInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'outstanding_amount',
        'status',
        'journal_id',
        'description',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'outstanding_amount' => 'decimal:4',
    ];

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_POSTED = 'POSTED';
    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Currency::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CustomerInvoiceLine::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(CustomerReceipt::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function isPaid(): bool
    {
        return bccomp($this->outstanding_amount, 0, 4) === 0;
    }

    public function getPaidAmount(): float
    {
        return (float) bcsub($this->total_amount, $this->outstanding_amount, 4);
    }

    public function calculateOutstanding(): void
    {
        $paidAmount = $this->receipts()
            ->where('status', 'POSTED')
            ->sum('amount');

        $this->outstanding_amount = (float) bcsub($this->total_amount, $paidAmount, 4);

        if (bccomp($this->outstanding_amount, 0, 4) <= 0) {
            $this->status = self::STATUS_PAID;
            $this->outstanding_amount = 0;
        } elseif (bccomp($this->outstanding_amount, $this->total_amount, 4) < 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        }
    }

    public function getDaysOutstanding(): int
    {
        if ($this->isPaid()) {
            return 0;
        }

        return (int) now()->diffInDays($this->due_date);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_POSTED,
            self::STATUS_PARTIALLY_PAID,
        ])->where('outstanding_amount', '>', 0);
    }

    public function scopeOverdue($query)
    {
        return $query->pending()
            ->where('due_date', '<', now());
    }
}
