<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Models\Currency;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\Supplier;

/**
 * Goods received against a purchase order. Posting puts stock items into the warehouse at the order price
 * and debits inventory (or expense for non-stock items) against goods received not invoiced.
 */
class GoodsReceipt extends Model
{
    use BelongsToCompany;

    public const STATUS_POSTED = 'POSTED';

    protected $fillable = [
        'company_id',
        'receipt_number',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'receipt_date',
        'delivery_note',
        'currency_id',
        'exchange_rate',
        'status',
        'notes',
        'journal_id',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'exchange_rate' => 'decimal:8',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class)->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
