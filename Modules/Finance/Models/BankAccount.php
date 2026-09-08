<?php


namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
    protected $fillable = [
        'company_id',
        'bank_name',
        'branch_name',
        'account_name',
        'account_number',
        'currency_id',
        'gl_account_id',
        'opening_balance',
        'current_balance',
        'status',
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

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Currency::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
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

    public function updateBalance(): void
    {
        $totalDeposits = $this->transactions()
            ->where('status', 'COMPLETED')
            ->where('transaction_type', 'DEPOSIT')
            ->sum('amount');

        $totalWithdrawals = $this->transactions()
            ->where('status', 'COMPLETED')
            ->whereIn('transaction_type', ['WITHDRAWAL', 'TRANSFER', 'CHARGE'])
            ->sum('amount');

        $this->current_balance = (float) bcadd($this->opening_balance, $totalDeposits, 4);
        $this->current_balance = (float) bcsub($this->current_balance, $totalWithdrawals, 4);
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
