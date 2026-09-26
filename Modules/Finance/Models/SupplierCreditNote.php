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
use Modules\Inventory\Models\PurchaseOrder;

/**
 * A credit received from a supplier (returns, price corrections): a purchase document with lines and tax codes
 * that reverses cost and input tax. When it credits an invoice it takes that invoice's currency and rate and
 * reduces what is owed on it; any excess stays as unapplied credit with the supplier.
 */
class SupplierCreditNote extends Model
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
        'supplier_id',
        'supplier_invoice_id',
        'purchase_order_id',
        'credit_note_number',
        'credit_note_date',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'applied_amount',
        'reason',
        'status',
        'rejection_reason',
        'journal_id',
        'posted_by',
        'posted_at',
        'created_by',
        'updated_by',
        'approved_by',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'posted_at' => 'datetime',
        'exchange_rate' => 'decimal:8',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'applied_amount' => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierCreditNoteLine::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
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

    public function unappliedAmount(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->applied_amount, 4);
    }
}
