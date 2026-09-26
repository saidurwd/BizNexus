<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;

class Supplier extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return SupplierFactory::new();
    }

    protected $fillable = [
        'company_id',
        'supplier_code',
        'name',
        'contact_person',
        'address',
        'phone',
        'email',
        'tax_number',
        'country_code',
        'currency_id',
        'payment_term_id',
        'payable_account_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
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

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    /**
     * The due date of an invoice dated on the given day under this party's payment term (on the day without one).
     */
    public function dueDateFor(CarbonInterface $invoiceDate): CarbonImmutable
    {
        return $this->paymentTerm?->dueDate($invoiceDate) ?? CarbonImmutable::parse($invoiceDate)->startOfDay();
    }
}
