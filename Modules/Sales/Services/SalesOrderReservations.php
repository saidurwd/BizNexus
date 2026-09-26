<?php

namespace Modules\Sales\Services;

use Modules\Inventory\Contracts\StockReservations;
use Modules\Inventory\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;

/**
 * Confirmed sales orders reserve the stock items they have not delivered yet, in the order's warehouse.
 */
class SalesOrderReservations implements StockReservations
{
    public function reserved(array $productIds, ?int $warehouseId = null, ?int $exceptOrderId = null): array
    {
        if ($productIds === []) {
            return [];
        }

        return SalesOrderLine::query()
            ->whereIn('sales_order_lines.product_id', $productIds)
            ->whereIn('sales_order_lines.sales_order_id', SalesOrder::query()
                ->whereIn('status', [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_PARTIALLY_DELIVERED])
                ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
                ->when($exceptOrderId, fn ($query) => $query->whereKeyNot($exceptOrderId))
                ->select('id'))
            ->whereIn('sales_order_lines.product_id', Product::where('type', Product::TYPE_STOCK)->select('id'))
            ->whereColumn('sales_order_lines.quantity', '>', 'sales_order_lines.delivered_quantity')
            ->selectRaw('sales_order_lines.product_id, SUM(sales_order_lines.quantity - sales_order_lines.delivered_quantity) AS reserved_quantity')
            ->groupBy('sales_order_lines.product_id')
            ->pluck('reserved_quantity', 'product_id')
            ->map(fn ($quantity) => bcadd((string) $quantity, '0', 4))
            ->all();
    }
}
