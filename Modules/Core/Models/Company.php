<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\UserCompany;

class Company extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\CompanyFactory::new();
    }
    protected $fillable = [
        'code',
        'name',
        'legal_name',
        'address',
        'phone',
        'email',
        'tax_number',
        'registration_number',
        'base_currency_id',
        'timezone',
        'fiscal_year_start',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fiscal_year_start' => 'date',
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
        return $this->hasMany(\Modules\Finance\Models\Account::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\Journal::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\Supplier::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\Customer::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\BankAccount::class);
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
        return $this->hasMany(\Modules\Finance\Models\Tax::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\Budget::class);
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
