<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;

class BankAccount extends Model
{
    use BelongsToCompany;

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

    /**
     * The full number never leaves the model in arrays or JSON; serialised output carries the masked value.
     *
     * @var array<int, string>
     */
    protected $hidden = ['account_number'];

    /**
     * @var array<int, string>
     */
    protected $appends = ['display_account_number'];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
    ];

    /**
     * The account number for display: in full only with finance.bank-accounts.view-sensitive, otherwise the last four digits.
     */
    protected function displayAccountNumber(): Attribute
    {
        return Attribute::get(function (): ?string {
            $number = $this->account_number;

            if ($number === null || Gate::allows('finance.bank-accounts.view-sensitive')) {
                return $number;
            }

            return '••••'.substr($number, -4);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
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
