<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Currency;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierCreditNote;
use Modules\Finance\Models\SupplierInvoice;

/**
 * An order to a supplier. Approved orders are received (goods receipts) and billed (supplier invoices matched
 * to the order lines); the order closes when everything has been received and invoiced.
 */
class PurchaseOrder extends Model
{
    use BelongsToCompany;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';

    public const STATUS_RECEIVED = 'RECEIVED';

    public const STATUS_CLOSED = 'CLOSED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_APPROVED, self::STATUS_PARTIALLY_RECEIVED, self::STATUS_RECEIVED, self::STATUS_CLOSED, self::STATUS_REJECTED, self::STATUS_CANCELLED];

    /**
     * Orders goods can still be received against.
     */
    public const RECEIVABLE_STATUSES = [self::STATUS_APPROVED, self::STATUS_PARTIALLY_RECEIVED];

    protected $fillable = [
        'company_id',
        'branch_id',
        'order_number',
        'supplier_id',
        'supplier_reference',
        'order_date',
        'expected_date',
        'warehouse_id',
        'currency_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
        'rejection_reason',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
    ];

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function canReceive(): bool
    {
        return in_array($this->status, self::RECEIVABLE_STATUSES, true);
    }

    /**
     * Whether goods received on the order are still waiting for the supplier's invoice.
     */
    public function hasUninvoicedReceipts(): bool
    {
        return $this->lines->contains(fn (PurchaseOrderLine $line) => bccomp($line->uninvoicedQuantity(), '0', 4) > 0);
    }

    /**
     * Whether goods returned after they were invoiced still wait for the supplier's credit note.
     */
    public function hasCreditDue(): bool
    {
        return $this->lines->contains(fn (PurchaseOrderLine $line) => bccomp($line->creditDueQuantity(), '0', 4) > 0);
    }

    /**
     * Whether received goods can be sent back to the supplier.
     */
    public function canReturn(): bool
    {
        return ! in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_REJECTED, self::STATUS_CANCELLED], true)
            && $this->lines->contains(fn (PurchaseOrderLine $line) => bccomp((string) $line->received_quantity, '0', 4) > 0);
    }

    /**
     * Order status after a receipt, return or invoice: fully or partly received, or closed once settled.
     */
    public function refreshReceiptStatus(): void
    {
        $lines = $this->lines()->get();
        $allReceived = $lines->every(fn (PurchaseOrderLine $line) => bccomp($line->outstandingQuantity(), '0', 4) <= 0);
        $anyReceived = $lines->contains(fn (PurchaseOrderLine $line) => bccomp((string) $line->received_quantity, '0', 4) > 0);

        $settled = $lines->every(fn (PurchaseOrderLine $line) => bccomp((string) $line->invoiced_quantity, (string) $line->received_quantity, 4) === 0);

        $this->update(['status' => match (true) {
            $allReceived && $settled => self::STATUS_CLOSED,
            $allReceived => self::STATUS_RECEIVED,
            $anyReceived || ! $settled => self::STATUS_PARTIALLY_RECEIVED,
            in_array($this->status, [self::STATUS_PARTIALLY_RECEIVED, self::STATUS_RECEIVED, self::STATUS_CLOSED], true) => self::STATUS_APPROVED,
            default => $this->status,
        }]);
    }

    public function currencyCode(): string
    {
        return $this->currency?->code ?? $this->company->baseCurrency?->code ?? 'XXX';
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SupplierReturn::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(SupplierCreditNote::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
