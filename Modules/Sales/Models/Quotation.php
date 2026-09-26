<?php

namespace Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Currency;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Customer;

/**
 * A price offer to a customer. Once accepted it becomes a sales order; it commits nothing on its own.
 */
class Quotation extends Model
{
    use BelongsToCompany;

    protected $table = 'sales_quotations';

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SENT = 'SENT';

    public const STATUS_ACCEPTED = 'ACCEPTED';

    public const STATUS_DECLINED = 'DECLINED';

    public const STATUS_CONVERTED = 'CONVERTED';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_ACCEPTED, self::STATUS_DECLINED, self::STATUS_CONVERTED];

    protected $fillable = [
        'company_id',
        'branch_id',
        'quotation_number',
        'customer_id',
        'customer_reference',
        'quotation_date',
        'valid_until',
        'currency_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT], true);
    }

    public function canConvert(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_ACCEPTED], true);
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null
            && in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT], true)
            && $this->valid_until->lt(app(CompanyContextService::class)->today());
    }

    public function currencyCode(): string
    {
        return $this->currency?->code ?? $this->company->baseCurrency?->code ?? 'XXX';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class, 'sales_quotation_id')->orderBy('id');
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class, 'sales_quotation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
