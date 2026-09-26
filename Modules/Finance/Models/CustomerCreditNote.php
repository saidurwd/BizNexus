<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Concerns\HasAttachments;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;

/**
 * A credit to a customer (returns, price corrections, cancelled services): a sales document with lines and tax
 * codes that reverses revenue and output tax. When it credits an invoice it takes that invoice's currency and
 * rate and reduces what is owed on it; any excess stays as unapplied customer credit.
 */
class CustomerCreditNote extends Model
{
    use BelongsToCompany, HasAttachments;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_POSTED = 'POSTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'company_id',
        'customer_id',
        'customer_invoice_id',
        'note_number',
        'note_date',
        'currency_id',
        'exchange_rate',
        'description',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'applied_amount',
        'status',
        'rejection_reason',
        'journal_id',
        'cost_journal_id',
        'posted_at',
        'created_by',
        'updated_by',
        'approved_by',
    ];

    protected $casts = [
        'note_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'applied_amount' => 'decimal:4',
        'posted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class, 'customer_invoice_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CustomerCreditNoteLine::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * The part of the credit not applied to an invoice, available to the customer.
     */
    public function unappliedAmount(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->applied_amount, 4);
    }
}
