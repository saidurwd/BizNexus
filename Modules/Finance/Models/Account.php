<?php


namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Finance\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Account extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\AccountFactory::new();
    }

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
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
        'currency_id',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'is_postable' => 'boolean',
        'level' => 'integer',
    ];


    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
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
        return $this->belongsTo(\Modules\Core\Models\Currency::class);
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
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
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
        return $this->isPostable() && !$this->isGroup();
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
            ->whereHas('journal', fn($q) => $q->where('status', 'POSTED'))
            ->exists();
    }

    public function getBalanceAttribute(): float
    {
        $totalDebit = $this->journalLines()
            ->whereHas('journal', fn($q) => $q->posted())
            ->sum('debit');

        $totalCredit = $this->journalLines()
            ->whereHas('journal', fn($q) => $q->posted())
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
