<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'status',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'base_currency_id');
    }

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function formatAmount($amount): string
    {
        return number_format($amount, $this->decimal_places, '.', ',');
    }
}
