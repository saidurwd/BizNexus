<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\Tax;

/**
 * An item the company buys or sells. Stock items are held in warehouses and valued at weighted average cost
 * (IAS 2): stock_quantity and stock_value are the company-wide totals, in the company's functional currency.
 */
class Product extends Model
{
    use BelongsToCompany, HasFactory;

    public const TYPE_STOCK = 'stock';

    public const TYPE_NON_STOCK = 'non_stock';

    public const TYPE_SERVICE = 'service';

    public const TYPES = [self::TYPE_STOCK, self::TYPE_NON_STOCK, self::TYPE_SERVICE];

    public const COSTING_WEIGHTED_AVERAGE = 'weighted_average';

    /**
     * Ledger accounts a product needs, by role; each falls back to the product's category.
     */
    public const ACCOUNT_ROLES = ['inventory', 'cogs', 'revenue', 'expense'];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    protected $fillable = [
        'company_id',
        'sku',
        'name',
        'description',
        'type',
        'category_id',
        'unit_id',
        'barcode',
        'purchase_price',
        'sales_price',
        'purchase_tax_id',
        'sales_tax_id',
        'inventory_account_id',
        'cogs_account_id',
        'revenue_account_id',
        'expense_account_id',
        'preferred_supplier_id',
        'reorder_level',
        'reorder_quantity',
        'costing_method',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:4',
        'sales_price' => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'reorder_quantity' => 'decimal:4',
        'stock_quantity' => 'decimal:4',
        'stock_value' => 'decimal:4',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_STOCK => __('Stock item'),
            self::TYPE_NON_STOCK => __('Non-stock item'),
            self::TYPE_SERVICE => __('Service'),
        ];
    }

    public function isStocked(): bool
    {
        return $this->type === self::TYPE_STOCK;
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    /**
     * The product's account for a role (inventory, cogs, revenue or expense), falling back to its category's.
     */
    public function accountIdFor(string $role): ?int
    {
        $column = "{$role}_account_id";

        return $this->{$column} ?? $this->category?->{$column};
    }

    /**
     * Weighted average unit cost in the functional currency; zero while nothing is in stock.
     */
    public function averageCost(): string
    {
        return bccomp((string) $this->stock_quantity, '0', 4) > 0
            ? bcdiv((string) $this->stock_value, (string) $this->stock_quantity, 6)
            : '0';
    }

    /**
     * Whether the product has stock or history, so it can no longer be deleted or change type.
     */
    public function isInUse(): bool
    {
        return bccomp((string) $this->stock_quantity, '0', 4) !== 0 || bccomp((string) $this->stock_value, '0', 4) !== 0;
    }

    public function isBelowReorderLevel(): bool
    {
        return $this->isStocked() && $this->reorder_level !== null && bccomp((string) $this->stock_quantity, (string) $this->reorder_level, 4) <= 0;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'purchase_tax_id');
    }

    public function salesTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'sales_tax_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
