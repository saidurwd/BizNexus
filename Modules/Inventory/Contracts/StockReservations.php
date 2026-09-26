<?php

namespace Modules\Inventory\Contracts;

/**
 * Stock promised to customers but not yet shipped. The inventory module shows on hand, reserved and
 * available quantities; the sales module, when installed, says what confirmed orders have reserved.
 */
interface StockReservations
{
    /**
     * Quantity reserved per product id, in one warehouse or across all of them, optionally ignoring one order.
     *
     * @param  list<int>  $productIds
     * @return array<int, string>
     */
    public function reserved(array $productIds, ?int $warehouseId = null, ?int $exceptOrderId = null): array;
}
