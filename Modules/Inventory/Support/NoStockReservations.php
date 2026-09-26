<?php

namespace Modules\Inventory\Support;

use Modules\Inventory\Contracts\StockReservations;

/**
 * Without sales orders nothing is reserved.
 */
class NoStockReservations implements StockReservations
{
    public function reserved(array $productIds, ?int $warehouseId = null, ?int $exceptOrderId = null): array
    {
        return [];
    }
}
