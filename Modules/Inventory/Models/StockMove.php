<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Models\Journal;

/**
 * One movement of stock into (positive quantity) or out of (negative) a warehouse, valued in the functional
 * currency. quantity_after and value_after are the product's company-wide totals after the move.
 * Moves are never edited or deleted; corrections are new moves.
 */
class StockMove extends Model
{
    use BelongsToCompany;

    public const SOURCE_GOODS_RECEIPT = 'goods_receipt';

    public const SOURCE_ADJUSTMENT = 'stock_adjustment';

    public const SOURCE_TRANSFER = 'stock_transfer';

    public const SOURCE_CUSTOMER_INVOICE = 'customer_invoice';

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'move_date',
        'quantity',
        'unit_cost',
        'value',
        'quantity_after',
        'value_after',
        'source_type',
        'source_id',
        'source_line_id',
        'reference',
        'journal_id',
        'created_by',
    ];

    protected $casts = [
        'move_date' => 'date',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:6',
        'value' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'value_after' => 'decimal:4',
    ];

    public static function sourceLabels(): array
    {
        return [
            self::SOURCE_GOODS_RECEIPT => __('Goods receipt'),
            self::SOURCE_ADJUSTMENT => __('Stock adjustment'),
            self::SOURCE_TRANSFER => __('Stock transfer'),
            self::SOURCE_CUSTOMER_INVOICE => __('Customer invoice'),
        ];
    }

    public function sourceLabel(): string
    {
        return self::sourceLabels()[$this->source_type] ?? $this->source_type;
    }

    /**
     * Where the source document can be opened, if it has a screen.
     */
    public function sourceUrl(): ?string
    {
        return match ($this->source_type) {
            self::SOURCE_GOODS_RECEIPT => route('inventory.goods-receipts.show', $this->source_id),
            self::SOURCE_ADJUSTMENT => route('inventory.adjustments.show', $this->source_id),
            self::SOURCE_TRANSFER => route('inventory.transfers.show', $this->source_id),
            self::SOURCE_CUSTOMER_INVOICE => route('finance.customer-invoices.show', $this->source_id),
            default => null,
        };
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
