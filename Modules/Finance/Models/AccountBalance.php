<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;

class AccountBalance extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'account_id',
        'fiscal_period_id',
        'balance_date',
        'opening_balance',
        'period_debit',
        'period_credit',
        'closing_balance',
        'balance_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'balance_date' => 'date',
        'opening_balance' => 'decimal:4',
        'period_debit' => 'decimal:4',
        'period_credit' => 'decimal:4',
        'closing_balance' => 'decimal:4',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isDebitBalance(): bool
    {
        return $this->balance_type === 'DEBIT';
    }

    public function isCreditBalance(): bool
    {
        return $this->balance_type === 'CREDIT';
    }
}
