<?php

namespace Modules\Finance\Models;

use Database\Factories\JournalLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;
use Modules\Core\Models\Branch;
use Modules\Core\Models\CostCenter;
use Modules\Core\Models\Department;
use Modules\Finance\Scopes\BranchScope;

class JournalLine extends Model
{
    public const TYPE_STANDARD = 'standard';

    /**
     * System line absorbing the functional-currency rounding difference of a foreign-currency journal.
     */
    public const TYPE_FX_ROUNDING = 'fx_rounding';

    use BelongsToCompany, HasFactory;

    protected static function newFactory()
    {
        return JournalLineFactory::new();
    }

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function (JournalLine $line) {
            $journalCompanyId = Journal::withoutGlobalScopes()->whereKey($line->journal_id)->value('company_id');

            $line->company_id ??= $journalCompanyId;

            if ((int) $line->company_id !== (int) $journalCompanyId) {
                throw new UnauthorizedCompanyAccessException((int) $line->company_id, auth()->id());
            }
        });
    }

    protected $fillable = [
        'journal_id',
        'account_id',
        'description',
        'debit',
        'credit',
        'currency_debit',
        'currency_credit',
        'line_type',
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
        return $this->belongsTo(CostCenter::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
