<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Company;

class CashAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'gl_account_id',
        'code',
        'name',
        'account_type',
        'currency_code',
        'opening_balance',
        'current_balance',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }
}
