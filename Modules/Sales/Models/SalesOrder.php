<?php

namespace Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Currency;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Inventory\Models\Warehouse;

/**
 * A customer's order. Confirmed orders are delivered (delivery notes) and invoiced (customer invoices matched
 * to the order lines); the order closes when everything has been delivered and invoiced.
 */
class SalesOrder extends Model
{
    use BelongsToCompany;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_PARTIALLY_DELIVERED = 'PARTIALLY_DELIVERED';

    public const STATUS_DELIVERED = 'DELIVERED';

    public const STATUS_CLOSED = 'CLOSED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_CONFIRMED, self::STATUS_PARTIALLY_DELIVERED, self::STATUS_DELIVERED, self::STATUS_CLOSED, self::STATUS_CANCELLED];

    /**
     * Orders that can still be delivered or invoiced.
     */
    public const OPEN_STATUSES = [self::STATUS_CONFIRMED, self::STATUS_PARTIALLY_DELIVERED, self::STATUS_DELIVERED];

    protected $fillable = [
        'company_id',
        'branch_id',
        'order_number',
        'customer_id',
        'sales_quotation_id',
        'customer_reference',
        'order_date',
        'delivery_date',
        'warehouse_id',
        'currency_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
        'created_by',
        'updated_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'confirmed_at' => 'datetime',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function canDeliver(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PARTIALLY_DELIVERED], true)
            && $this->lines->contains(fn (SalesOrderLine $line) => $line->needsDelivery() && bccomp($line->undeliveredQuantity(), '0', 4) > 0);
    }

    public function canInvoice(): bool
    {
        return $this->isOpen() && $this->lines->contains(fn (SalesOrderLine $line) => bccomp($line->billableQuantity(), '0', 4) > 0);
    }

    /**
     * Status after a delivery or an invoice: partly or fully delivered, or closed once all is invoiced.
     */
    public function refreshFulfilmentStatus(): void
    {
        if (! $this->isOpen()) {
            return;
        }

        $lines = $this->lines()->with('product')->get();
        $deliverable = $lines->filter(fn (SalesOrderLine $line) => $line->needsDelivery());
        $allDelivered = $deliverable->every(fn (SalesOrderLine $line) => bccomp($line->undeliveredQuantity(), '0', 4) <= 0);
        $anyDelivered = $deliverable->contains(fn (SalesOrderLine $line) => bccomp((string) $line->delivered_quantity, '0', 4) > 0);
        $allInvoiced = $lines->every(fn (SalesOrderLine $line) => bccomp((string) $line->invoiced_quantity, (string) $line->quantity, 4) >= 0);

        $this->update(['status' => match (true) {
            $allDelivered && $allInvoiced => self::STATUS_CLOSED,
            $allDelivered && $deliverable->isNotEmpty() => self::STATUS_DELIVERED,
            $anyDelivered => self::STATUS_PARTIALLY_DELIVERED,
            default => self::STATUS_CONFIRMED,
        }]);
    }

    public function currencyCode(): string
    {
        return $this->currency?->code ?? $this->company->baseCurrency?->code ?? 'XXX';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'sales_quotation_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class)->orderBy('id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CustomerInvoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
