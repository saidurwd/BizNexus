<?php


namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAccount extends Model
{

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
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
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }
}
