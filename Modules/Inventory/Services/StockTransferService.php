<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockTransfer;

/**
 * Transfers between warehouses post at once. They change where stock is, not what it is worth, so no
 * journal is needed.
 */
class StockTransferService
{
    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected StockService $stock,
    ) {}

    /**
     * @param  array{company_id: int, from_warehouse_id: int, to_warehouse_id: int, transfer_date: string, notes?: string|null, lines: list<array{product_id: int, quantity: string|int|float}>}  $data
     */
    public function transfer(array $data): StockTransfer
    {
        if ((int) $data['from_warehouse_id'] === (int) $data['to_warehouse_id']) {
            throw new InvalidAccountingTransactionException(__('Choose two different warehouses.'));
        }

        return DB::transaction(function () use ($data) {
            $transfer = StockTransfer::create([
                'company_id' => $data['company_id'],
                'transfer_number' => $this->documentNumber->generateNumber($data['company_id'], 'TRF'),
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'transfer_date' => $data['transfer_date'],
                'notes' => $data['notes'] ?? null,
                'status' => StockTransfer::STATUS_POSTED,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['lines'] as $line) {
                $product = Product::findOrFail($line['product_id']);
                $transferLine = $transfer->lines()->create(['product_id' => $product->id, 'quantity' => $line['quantity']]);

                $this->stock->transfer($product, (int) $data['from_warehouse_id'], (int) $data['to_warehouse_id'], (string) $line['quantity'], $data['transfer_date'], [
                    'type' => StockMove::SOURCE_TRANSFER, 'id' => $transfer->id, 'line_id' => $transferLine->id, 'reference' => $transfer->transfer_number,
                ]);
            }

            $this->audit->logCreate('Inventory', 'StockTransfer', $transfer->id, $transfer->load('lines')->toArray());

            return $transfer->fresh();
        });
    }
}
