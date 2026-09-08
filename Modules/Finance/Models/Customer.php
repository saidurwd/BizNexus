<?php


namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
    protected $fillable = [
        'company_id',
        'customer_code',
        'name',
        'contact_person',
        'address',
        'phone',
        'email',
        'tax_number',
        'currency_id',
        'receivable_account_id',
        'status',
        'created_by',
        'updated_by',
    ];


    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Currency::class);
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CustomerInvoice::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(CustomerReceipt::class);
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

    public function getOutstandingBalance(): float
    {
        return $this->invoices()
            ->whereNotIn('status', ['CANCELLED', 'PAID'])
            ->sum('outstanding_amount');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
