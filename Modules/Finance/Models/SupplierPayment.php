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
use Modules\Finance\Scopes\BranchScope;

class SupplierPayment extends Model
{
    use BelongsToCompany, HasAttachments;

    protected $fillable = [
        'company_id',
        'branch_id',
        'supplier_id',
        'payment_number',
        'payment_date',
        'currency_id',
        'exchange_rate',
        'amount',
        'withholding_tax_id',
        'withholding_amount',
        'payment_method',
        'bank_account_id',
        'reference',
        'description',
        'status',
        'journal_id',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    protected $casts = [
        'withholding_amount' => 'decimal:4',
        'payment_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'amount' => 'decimal:4',
    ];

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_POSTED = 'POSTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function withholdingTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'withholding_tax_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
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

    public function getAllocatedAmount(): float
    {
        return (float) $this->allocations()->sum('amount');
    }

    public function getUnallocatedAmount(): float
    {
        return (float) bcsub($this->amount, $this->getAllocatedAmount(), 4);
    }
}
