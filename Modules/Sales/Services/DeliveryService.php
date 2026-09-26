<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Services\JournalService;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Services\StockService;
use Modules\Sales\Models\DeliveryNote;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;

/**
 * Delivering goods on a confirmed sales order. A delivery posts at once: stock items leave the warehouse at
 * the weighted average cost, which moves from inventory to goods delivered not invoiced. The invoice later
 * moves it on to cost of goods sold, so the cost is recognised with the revenue.
 */
class DeliveryService
{
    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService,
        protected StockService $stock,
        protected DefaultAccountService $defaultAccounts,
        protected CompanyContextService $companyContext,
    ) {}

    /**
     * @param  array{delivery_date: string, warehouse_id?: int|null, carrier?: string|null, tracking_number?: string|null, notes?: string|null, lines: array<int|string, string|int|float|null>}  $data  lines: order line id => quantity delivered
     */
    public function deliver(SalesOrder $order, array $data): DeliveryNote
    {
        return DB::transaction(function () use ($order, $data) {
            $order = SalesOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($order->status, [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_PARTIALLY_DELIVERED], true)) {
                throw new InvalidAccountingTransactionException(__('Goods can only be delivered on confirmed sales orders that are still open.'));
            }

            $quantities = collect($data['lines'])->map(fn ($quantity) => (string) ($quantity ?: '0'))->filter(fn (string $quantity) => bccomp($quantity, '0', 4) > 0);

            if ($quantities->isEmpty()) {
                throw new InvalidAccountingTransactionException(__('Enter the quantity delivered on at least one line.'));
            }

            $orderLines = SalesOrderLine::with('product.category')->where('sales_order_id', $order->id)->whereKey($quantities->keys())->lockForUpdate()->get()->keyBy('id');

            if ($orderLines->count() !== $quantities->count()) {
                throw new InvalidAccountingTransactionException(__('A delivered line does not belong to this order.'));
            }

            $companyId = $order->company_id;
            $functional = $this->companyContext->getBaseCurrency()?->code ?? 'XXX';
            $warehouseId = (int) ($data['warehouse_id'] ?? $order->warehouse_id);

            $delivery = DeliveryNote::create([
                'company_id' => $companyId,
                'delivery_number' => $this->documentNumber->generateNumber($companyId, 'DN'),
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'warehouse_id' => $warehouseId,
                'delivery_date' => $data['delivery_date'],
                'carrier' => $data['carrier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => DeliveryNote::STATUS_POSTED,
                'created_by' => Auth::id(),
            ]);

            $journalLines = [];
            $moves = [];

            foreach ($quantities as $lineId => $quantity) {
                $orderLine = $orderLines[$lineId];
                $product = $orderLine->product;

                if (! $orderLine->needsDelivery()) {
                    throw new InvalidAccountingTransactionException(__(':product is a service and is invoiced without delivery.', ['product' => $product->sku]));
                }

                if (bccomp($quantity, $orderLine->undeliveredQuantity(), 4) > 0) {
                    throw new InvalidAccountingTransactionException(__('Only :quantity of :product is still to be delivered on this order.', ['quantity' => $this->plain($orderLine->undeliveredQuantity()), 'product' => $product->sku]));
                }

                $cost = Money::zero($functional);
                $deliveryLine = $delivery->lines()->create(['sales_order_line_id' => $orderLine->id, 'product_id' => $product->id, 'quantity' => $quantity]);

                if ($product->isStocked()) {
                    $move = $this->stock->issue($product, $warehouseId, $quantity, $data['delivery_date'], [
                        'type' => StockMove::SOURCE_DELIVERY, 'id' => $delivery->id, 'line_id' => $deliveryLine->id, 'reference' => $delivery->delivery_number,
                    ]);
                    $moves[] = $move;
                    $cost = Money::of((string) $move->value, $functional)->abs();
                }

                $deliveryLine->update(['cost_value' => $cost->amount]);
                $orderLine->update([
                    'delivered_quantity' => bcadd((string) $orderLine->delivered_quantity, $quantity, 4),
                    'delivered_cost_value' => bcadd((string) $orderLine->delivered_cost_value, $cost->amount, 4),
                ]);

                if (! $cost->isZero()) {
                    $description = "{$product->sku} × ".$this->plain($quantity)." — {$order->order_number}";
                    $journalLines[] = ['account_id' => $this->defaultAccounts->forPurpose($companyId, AccountPurpose::GoodsDeliveredNotInvoiced), 'description' => $description, 'debit' => $cost->amount, 'credit' => 0];
                    $journalLines[] = ['account_id' => $product->accountIdFor('inventory') ?? $this->defaultAccounts->forPurpose($companyId, AccountPurpose::Inventory), 'description' => $description, 'debit' => 0, 'credit' => $cost->amount];
                }
            }

            if ($journalLines !== []) {
                $journal = $this->journalService->postFromSource([
                    'company_id' => $companyId,
                    'journal_date' => $data['delivery_date'],
                    'reference_type' => 'delivery_note',
                    'reference_id' => $delivery->id,
                    'description' => "Delivery {$delivery->delivery_number} for {$order->order_number}",
                    'lines' => $journalLines,
                ]);

                $delivery->update(['journal_id' => $journal->id]);
                StockMove::whereKey(collect($moves)->pluck('id'))->update(['journal_id' => $journal->id]);
            }

            $order->refreshFulfilmentStatus();
            $this->audit->logCreate('Sales', 'DeliveryNote', $delivery->id, $delivery->load('lines')->toArray());

            return $delivery->fresh();
        });
    }

    protected function plain(string $number): string
    {
        return str_contains($number, '.') ? (rtrim(rtrim($number, '0'), '.') ?: '0') : $number;
    }
}
