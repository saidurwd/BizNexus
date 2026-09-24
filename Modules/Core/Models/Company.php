<?php

namespace Modules\Core\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\Budget;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\Tax;

class Company extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return CompanyFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            $company->tenant_id ??= auth()->user()?->tenant_id ?? Tenant::default()->id;
        });

        static::updating(function (Company $company) {
            if ($company->isDirty('tenant_id')) {
                throw new LogicException('A company cannot be moved to another tenant.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'legal_name',
        'address',
        'phone',
        'email',
        'tax_number',
        'country_code',
        'registration_number',
        'base_currency_id',
        'timezone',
        'fiscal_year_start',
        'status',
        'require_mfa',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fiscal_year_start' => 'date',
        'require_mfa' => 'boolean',
    ];

    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function userCompanies(): HasMany
    {
        return $this->hasMany(UserCompany::class);
    }

    public function currentFiscalYear()
    {
        return $this->fiscalYears()->where('is_current', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
