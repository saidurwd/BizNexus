<?php


namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Scopes\CompanyScope;

class Budget extends Model
{

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
    protected $fillable = [
        'company_id',
        'fiscal_year_id',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\FiscalYear::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
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

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getTotalBudgeted(): float
    {
        return (float) $this->lines()->sum('budget_amount');
    }

    public function getTotalActual(?int $period = null): float
    {
        $query = \Modules\Finance\Models\JournalLine::query()
            ->whereHas('journal', fn($q) => $q->posted())
            ->whereHas('account', fn($q) => $q->where('account_type', 'EXPENSE'));

        if ($period) {
            $query->whereHas('journal.fiscalPeriod', fn($q) => $q->where('period', $period));
        }

        $totalDebit = (float) $query->clone()->sum('debit');
        $totalCredit = (float) $query->clone()->sum('credit');

        return $totalDebit - $totalCredit;
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
