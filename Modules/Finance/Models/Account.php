<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Finance\Enums\CashFlowCategory;

class Account extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return AccountFactory::new();
    }

    protected $fillable = [
        'company_id',
        'parent_id',
        'account_code',
        'account_name',
        'account_type',
        'account_category_id',
        'normal_balance',
        'level',
        'is_group',
        'is_postable',
        'revalue_foreign_currency',
        'is_control_account',
        'cash_flow_category',
        'is_current',
        'currency_id',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'revalue_foreign_currency' => 'boolean',
        'is_control_account' => 'boolean',
        'cash_flow_category' => CashFlowCategory::class,
        'is_current' => 'boolean',
        'is_group' => 'boolean',
        'is_postable' => 'boolean',
        'level' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPostable(): bool
    {
        return $this->is_postable && $this->isActive();
    }

    public function isGroup(): bool
    {
        return $this->is_group;
    }

    public function canReceivePosting(): bool
    {
        return $this->isPostable() && ! $this->isGroup();
    }

    public function isDebitNormal(): bool
    {
        return $this->normal_balance === 'DEBIT';
    }

    public function isCreditNormal(): bool
    {
        return $this->normal_balance === 'CREDIT';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePostable($query)
    {
        return $query->where('is_postable', true)
            ->where('is_group', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    public function isLocked(): bool
    {
        return $this->journalLines()
            ->whereHas('journal', fn ($q) => $q->where('status', 'POSTED'))
            ->exists();
    }

    public function getBalanceAttribute(): float
    {
        $totalDebit = $this->journalLines()
            ->whereHas('journal', fn ($q) => $q->posted())
            ->sum('debit');

        $totalCredit = $this->journalLines()
            ->whereHas('journal', fn ($q) => $q->posted())
            ->sum('credit');

        if ($this->isDebitNormal()) {
            return $totalDebit - $totalCredit;
        }

        return $totalCredit - $totalDebit;
    }

    public function getFullCodeAttribute(): string
    {
        $codes = [$this->account_code];

        $parent = $this->parent;
        while ($parent) {
            array_unshift($codes, $parent->account_code);
            $parent = $parent->parent;
        }

        return implode('.', $codes);
    }
}
