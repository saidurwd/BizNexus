<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Concerns\HasAttachments;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Scopes\BranchScope;

class CustomerInvoice extends Model
{
    use BelongsToCompany, HasAttachments;

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
        'cost_journal_id',
        'description',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
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
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function costJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'cost_journal_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CustomerInvoiceLine::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ReceiptAllocation::class, 'customer_invoice_id');
    }

    /**
     * Credit notes issued against this invoice.
     */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CustomerCreditNote::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
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
        $paidAmount = $this->allocations()
            ->whereHas('receipt', fn ($q) => $q->where('status', 'POSTED'))
            ->sum('amount');
        $creditedAmount = $this->creditNotes()
            ->where('status', CustomerCreditNote::STATUS_POSTED)
            ->sum('applied_amount');

        $this->outstanding_amount = (float) bcsub(bcsub((string) $this->total_amount, (string) $paidAmount, 4), (string) $creditedAmount, 4);

        if (bccomp($this->outstanding_amount, 0, 4) <= 0) {
            $this->status = self::STATUS_PAID;
            $this->outstanding_amount = 0;
        } elseif (bccomp($this->outstanding_amount, $this->total_amount, 4) < 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        }
    }

    /**
     * Days past the due date at the given date (the company's business date by default); 0 when not yet due or paid.
     */
    public function getDaysOutstanding(?CarbonInterface $asOf = null): int
    {
        if ($this->isPaid()) {
            return 0;
        }

        $asOf ??= app(CompanyContextService::class)->today();

        return max(0, (int) $this->due_date->copy()->startOfDay()->diffInDays($asOf->copy()->startOfDay(), false));
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
