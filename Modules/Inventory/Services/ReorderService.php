<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Inventory\Contracts\StockReservations;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\PurchaseOrderLine;

/**
 * Reorder suggestions: stock items whose projected quantity (on hand, less reserved for customers, plus
 * still to arrive on open purchase orders) is at or below their reorder level. Suggestions become draft
 * purchase orders, one per supplier.
 */
class ReorderService
{
    /**
     * Purchase orders whose outstanding quantities count as on order.
     */
    public const ON_ORDER_STATUSES = [PurchaseOrder::STATUS_SUBMITTED, PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED];

    public function __construct(
        protected StockReservations $reservations,
        protected PurchaseOrderService $orders,
    ) {}

    /**
     * @return Collection<int, array{product: Product, on_hand: string, reserved: string, on_order: string, projected: string, suggested: string}>
     */
    public function suggestions(): Collection
    {
        $products = Product::active()->with(['unit', 'preferredSupplier'])
            ->where('type', Product::TYPE_STOCK)
            ->whereNotNull('reorder_level')
            ->orderBy('sku')
            ->get();

        $ids = $products->pluck('id')->all();
        $reserved = $this->reservations->reserved($ids);
        $onOrder = $this->onOrder($ids);

        return $products
            ->map(function (Product $product) use ($reserved, $onOrder) {
                $projected = bcadd(bcsub((string) $product->stock_quantity, $reserved[$product->id] ?? '0', 4), $onOrder[$product->id] ?? '0', 4);
                $shortfall = bcsub((string) $product->reorder_level, $projected, 4);
                $suggested = $product->reorder_quantity !== null ? (string) $product->reorder_quantity : (bccomp($shortfall, '0', 4) > 0 ? $shortfall : '0');

                return [
                    'product' => $product,
                    'on_hand' => (string) $product->stock_quantity,
                    'reserved' => $reserved[$product->id] ?? '0',
                    'on_order' => $onOrder[$product->id] ?? '0',
                    'projected' => $projected,
                    'suggested' => $suggested,
                ];
            })
            ->filter(fn (array $row) => bccomp($row['projected'], (string) $row['product']->reorder_level, 4) <= 0)
            ->values();
    }

    /**
     * Quantities ordered from suppliers and not yet received, per product.
     *
     * @param  list<int>  $productIds
     * @return array<int, string>
     */
    public function onOrder(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return PurchaseOrderLine::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('purchase_order_id', PurchaseOrder::whereIn('status', self::ON_ORDER_STATUSES)->select('id'))
            ->whereColumn('quantity', '>', 'received_quantity')
            ->selectRaw('product_id, SUM(quantity - received_quantity) AS outstanding_quantity')
            ->groupBy('product_id')
            ->pluck('outstanding_quantity', 'product_id')
            ->map(fn ($quantity) => bcadd((string) $quantity, '0', 4))
            ->all();
    }

    /**
     * Draft purchase orders, one per supplier, at each product's purchase price and tax code.
     *
     * @param  array<int|string, array{quantity: string|int|float, supplier_id: int|string}>  $lines  keyed by product id
     * @return Collection<int, PurchaseOrder>
     */
    public function createOrders(int $companyId, int $warehouseId, string $orderDate, array $lines, ?int $branchId = null): Collection
    {
        $products = Product::whereKey(array_keys($lines))->get()->keyBy('id');

        if ($products->count() !== count($lines)) {
            throw new InvalidAccountingTransactionException(__('Choose at least one product to order.'));
        }

        return DB::transaction(fn () => collect($lines)
            ->groupBy('supplier_id', preserveKeys: true)
            ->map(fn (Collection $supplierLines, int|string $supplierId) => $this->orders->create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'supplier_id' => (int) $supplierId,
                'order_date' => $orderDate,
                'warehouse_id' => $warehouseId,
                'notes' => __('Created from reorder suggestions.'),
                'lines' => $supplierLines->map(fn (array $line, int|string $productId) => [
                    'product_id' => (int) $productId,
                    'quantity' => $line['quantity'],
                    'unit_price' => (string) $products[$productId]->purchase_price,
                    'tax_id' => $products[$productId]->purchase_tax_id,
                ])->values()->all(),
            ]))
            ->values());
    }
}
