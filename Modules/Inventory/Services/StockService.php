<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Support\Money;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMove;

/**
 * The perpetual stock ledger. Every change to a product's stock is a StockMove; the product keeps its
 * company-wide quantity and value, and StockBalance the quantity in each warehouse. Costing is weighted
 * average (IAS 2): receipts add their cost, issues leave at the current average, and the last unit out takes
 * whatever value remains so that nothing is left over from rounding.
 *
 * Callers run inside their own transaction and post the matching journal; this service only moves stock.
 */
class StockService
{
    public function __construct(protected CompanyContextService $companyContext) {}

    /**
     * Put stock into a warehouse at a total value in the functional currency.
     *
     * @param  array{type: string, id: int, line_id?: int|null, reference?: string|null}  $source
     */
    public function receive(Product $product, int $warehouseId, string $quantity, Money $value, string $date, array $source): StockMove
    {
        return $this->move($product, $warehouseId, $quantity, $date, $source, fn () => $value);
    }

    /**
     * Take stock out of a warehouse at the product's average cost; returns the move (negative quantity and value).
     *
     * @param  array{type: string, id: int, line_id?: int|null, reference?: string|null}  $source
     */
    public function issue(Product $product, int $warehouseId, string $quantity, string $date, array $source): StockMove
    {
        return $this->move($product, $warehouseId, bcmul($quantity, '-1', 4), $date, $source, function (Product $locked) use ($quantity) {
            $currency = $this->functionalCurrency();

            return bccomp($quantity, (string) $locked->stock_quantity, 4) >= 0
                ? Money::of((string) $locked->stock_value, $currency)->negated()
                : Money::of(bcmul($locked->averageCost(), $quantity, 8), $currency)->negated();
        });
    }

    /**
     * Move stock between warehouses; the value leaves and arrives at the same average cost.
     *
     * @param  array{type: string, id: int, line_id?: int|null, reference?: string|null}  $source
     * @return array{0: StockMove, 1: StockMove}
     */
    public function transfer(Product $product, int $fromWarehouseId, int $toWarehouseId, string $quantity, string $date, array $source): array
    {
        $out = $this->issue($product, $fromWarehouseId, $quantity, $date, $source);
        $in = $this->receive($product, $toWarehouseId, $quantity, Money::of((string) $out->value, $this->functionalCurrency())->negated(), $date, $source);

        return [$out, $in];
    }

    /**
     * Quantity of a product in one warehouse.
     */
    public function quantityIn(int $productId, int $warehouseId): string
    {
        return (string) (StockBalance::where('product_id', $productId)->where('warehouse_id', $warehouseId)->value('quantity') ?? '0');
    }

    /**
     * @param  array{type: string, id: int, line_id?: int|null, reference?: string|null}  $source
     * @param  callable(Product): Money  $valueFor
     */
    protected function move(Product $product, int $warehouseId, string $quantity, string $date, array $source, callable $valueFor): StockMove
    {
        if (! $product->isStocked()) {
            throw new InvalidAccountingTransactionException(__(':product is not a stock item.', ['product' => $product->sku]));
        }

        if (bccomp($quantity, '0', 4) === 0) {
            throw new InvalidAccountingTransactionException(__('Stock quantities must not be zero.'));
        }

        return DB::transaction(function () use ($product, $warehouseId, $quantity, $date, $source, $valueFor) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $balance = StockBalance::where('product_id', $locked->id)->where('warehouse_id', $warehouseId)->lockForUpdate()->first()
                ?? StockBalance::create(['company_id' => $locked->company_id, 'product_id' => $locked->id, 'warehouse_id' => $warehouseId, 'quantity' => 0]);

            $newWarehouseQuantity = bcadd((string) $balance->quantity, $quantity, 4);

            if (bccomp($quantity, '0', 4) < 0 && bccomp($newWarehouseQuantity, '0', 4) < 0 && ! config('inventory.allow_negative_stock')) {
                throw new InvalidAccountingTransactionException(__('Not enough :product in :warehouse: :available available, :requested needed.', [
                    'product' => $locked->sku,
                    'warehouse' => $balance->warehouse?->code,
                    'available' => rtrim(rtrim((string) $balance->quantity, '0'), '.') ?: '0',
                    'requested' => rtrim(rtrim(bcmul($quantity, '-1', 4), '0'), '.'),
                ]));
            }

            $value = $valueFor($locked);
            $quantityAfter = bcadd((string) $locked->stock_quantity, $quantity, 4);
            $valueAfter = bcadd((string) $locked->stock_value, $value->amount, 4);

            $locked->forceFill(['stock_quantity' => $quantityAfter, 'stock_value' => $valueAfter])->save();
            $balance->update(['quantity' => $newWarehouseQuantity]);
            $product->forceFill(['stock_quantity' => $quantityAfter, 'stock_value' => $valueAfter]);

            return StockMove::create([
                'company_id' => $locked->company_id,
                'product_id' => $locked->id,
                'warehouse_id' => $warehouseId,
                'move_date' => $date,
                'quantity' => $quantity,
                'unit_cost' => bcdiv($value->abs()->amount, ltrim($quantity, '-'), 6),
                'value' => $value->amount,
                'quantity_after' => $quantityAfter,
                'value_after' => $valueAfter,
                'source_type' => $source['type'],
                'source_id' => $source['id'],
                'source_line_id' => $source['line_id'] ?? null,
                'reference' => $source['reference'] ?? null,
                'created_by' => Auth::id(),
            ]);
        });
    }

    protected function functionalCurrency(): string
    {
        return $this->companyContext->getBaseCurrency()?->code ?? 'XXX';
    }
}
