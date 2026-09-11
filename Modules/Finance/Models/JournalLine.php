<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Scopes\BranchScope;
use Modules\Finance\Scopes\CompanyScope;
use Modules\Finance\Scopes\DepartmentScope;

class JournalLine extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\JournalLineFactory::new();
    }

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
        static::addGlobalScope(new BranchScope);
        static::addGlobalScope(new DepartmentScope);
    }
    protected $fillable = [
        'journal_id',
        'account_id',
        'description',
        'debit',
        'credit',
        'currency_debit',
        'currency_credit',
        'cost_center_id',
        'department_id',
        'branch_id',
        'business_unit_id',
        'project_id',
        'tax_id',
        'reference',
    ];

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'currency_debit' => 'decimal:4',
        'currency_credit' => 'decimal:4',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\CostCenter::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Branch::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function getAmount(): float
    {
        return (float) ($this->debit ?: $this->credit);
    }

    public function isDebit(): bool
    {
        return $this->debit > 0;
    }

    public function isCredit(): bool
    {
        return $this->credit > 0;
    }

    public function getSignedAmount(): float
    {
        return $this->debit - $this->credit;
    }
}
