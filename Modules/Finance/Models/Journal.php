<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Database\Factories\JournalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Scopes\CompanyScope;
use Modules\Finance\Scopes\BranchScope;
use Modules\Finance\Scopes\DepartmentScope;

class Journal extends Model
{
    use BelongsToCompany, HasFactory;

    public static function factory()
    {
        return JournalFactory::new();
    }

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    protected $fillable = [
        'company_id',
        'branch_id',
        'journal_number',
        'journal_date',
        'posting_date',
        'fiscal_period_id',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'currency_id',
        'exchange_rate',
        'total_debit',
        'total_credit',
        'posted_at',
        'posted_by',
        'reversal_of_journal_id',
        'reversal_reason',
        'reversed_at',
        'reversed_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posting_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
        'posted_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_POSTED = 'POSTED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_REVERSED = 'REVERSED';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)
            ->withoutGlobalScope(CompanyScope::class)
            ->withoutGlobalScope(BranchScope::class)
            ->withoutGlobalScope(DepartmentScope::class);
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function originalJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'reversal_of_journal_id');
    }

    public function reversalJournal(): HasMany
    {
        return $this->hasMany(Journal::class, 'reversal_of_journal_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
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

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    public function canSubmit(): bool
    {
        if (! $this->isDraft() || ! $this->isBalanced()) {
            return false;
        }

        if ($this->lines()->count() < 2) {
            return false;
        }

        foreach ($this->lines as $line) {
            if ($line->debit > 0 && $line->credit > 0) {
                return false;
            }

            if ($line->debit == 0 && $line->credit == 0) {
                return false;
            }

            $account = $line->account;
            if (! $account || ! $account->canReceivePosting()) {
                return false;
            }
        }

        return true;
    }

    public function canApprove(): bool
    {
        return $this->isSubmitted();
    }

    public function canPost(): bool
    {
        return $this->isApproved();
    }

    public function canReverse(): bool
    {
        return $this->isPosted() && ! $this->isReversed();
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED]);
    }

    public function isBalanced(): bool
    {
        return bccomp($this->total_debit, $this->total_credit, 4) === 0;
    }

    public function calculateTotals(): void
    {
        $totals = $this->lines()->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')->first();

        $this->total_debit = $totals->total_debit ?? 0;
        $this->total_credit = $totals->total_credit ?? 0;
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForPeriod($query, int $fiscalPeriodId)
    {
        return $query->where('fiscal_period_id', $fiscalPeriodId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('journal_date', [$startDate, $endDate]);
    }
}
