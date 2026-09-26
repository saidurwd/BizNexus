<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Support\Formatter;
use Modules\Finance\Services\ExchangeRateService;
use Modules\Finance\Services\ReceiptService;
use Modules\Inventory\Contracts\StockReservations;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockBalance;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\Concerns\PricesSalesLines;

/**
 * Sales orders: drafted, confirmed within the customer's credit limit, then delivered and invoiced.
 */
class SalesOrderService
{
    use PricesSalesLines;

    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data) {
            $order = SalesOrder::create([
                ...$this->headerAttributes($data),
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'sales_quotation_id' => $data['sales_quotation_id'] ?? null,
                'order_number' => $this->documentNumber->generateNumber($data['company_id'], 'SO'),
                'status' => SalesOrder::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            $this->replacePricedLines($order, $data['lines'], $order->currencyCode(), $order->order_date);
            $this->audit->logCreate('Sales', 'SalesOrder', $order->id, $order->toArray());

            return $order->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SalesOrder $order, array $data): SalesOrder
    {
        $this->requireStatus($order, [SalesOrder::STATUS_DRAFT]);

        return DB::transaction(function () use ($order, $data) {
            $order->update([...$this->headerAttributes($data), 'updated_by' => Auth::id()]);
            $order->refresh();
            $this->replacePricedLines($order, $data['lines'], $order->currencyCode(), $order->order_date);

            return $order->fresh();
        });
    }

    /**
     * Confirm the order to the customer. With credit limits on, the customer's open receivables plus this
     * order may not exceed their limit ("block") or only raise a warning ("warn").
     */
    public function confirm(SalesOrder $order): SalesOrder
    {
        $this->requireStatus($order, [SalesOrder::STATUS_DRAFT]);

        if ($order->lines()->doesntExist()) {
            throw new InvalidAccountingTransactionException(__('An order needs at least one line.'));
        }

        $this->ensureWithinCreditLimit($order);
        $this->ensureStockAvailable($order);

        $order->forceFill(['confirmed_by' => Auth::id(), 'confirmed_at' => now()]);

        return $this->transition($order, SalesOrder::STATUS_CONFIRMED, 'CONFIRM');
    }

    /**
     * Cancel an order nothing has been delivered or invoiced on.
     */
    public function cancel(SalesOrder $order): SalesOrder
    {
        $this->requireStatus($order, [SalesOrder::STATUS_DRAFT, SalesOrder::STATUS_CONFIRMED]);

        if ($order->lines()->where(fn ($query) => $query->where('delivered_quantity', '>', 0)->orWhere('invoiced_quantity', '>', 0))->exists()) {
            throw new InvalidAccountingTransactionException(__('Part of this order has been delivered or invoiced. Close it instead.'));
        }

        return $this->transition($order, SalesOrder::STATUS_CANCELLED, 'CANCEL');
    }

    /**
     * Close an order that will not be delivered in full. Everything delivered must be invoiced first.
     */
    public function close(SalesOrder $order): SalesOrder
    {
        $this->requireStatus($order, SalesOrder::OPEN_STATUSES);

        $waiting = $order->lines()->with('product')->get()
            ->contains(fn ($line) => $line->needsDelivery() && bccomp((string) $line->delivered_quantity, (string) $line->invoiced_quantity, 4) > 0);

        if ($waiting) {
            throw new InvalidAccountingTransactionException(__('Goods delivered on this order have not all been invoiced yet.'));
        }

        return $this->transition($order, SalesOrder::STATUS_CLOSED, 'CLOSE');
    }

    public function delete(SalesOrder $order): void
    {
        $this->requireStatus($order, [SalesOrder::STATUS_DRAFT]);
        $this->audit->logDelete('Sales', 'SalesOrder', $order->id, $order->toArray());
        $order->delete();
    }

    /**
     * Compare what the order needs with what is available in its warehouse: on hand less what other
     * confirmed orders have reserved. Shortfalls become backorders ("warn") or refuse the order ("block").
     */
    protected function ensureStockAvailable(SalesOrder $order): void
    {
        $mode = config('inventory.reservation_check', 'warn');

        if ($mode === 'off') {
            return;
        }

        $needed = $order->lines()->with('product')->get()
            ->filter(fn ($line) => $line->product?->isStocked())
            ->groupBy('product_id')
            ->map(fn ($lines) => $lines->reduce(fn (string $sum, $line) => bcadd($sum, (string) $line->quantity, 4), '0'));

        if ($needed->isEmpty()) {
            return;
        }

        $onHand = StockBalance::where('warehouse_id', $order->warehouse_id)->whereIn('product_id', $needed->keys())->pluck('quantity', 'product_id');
        $reserved = app(StockReservations::class)->reserved($needed->keys()->all(), $order->warehouse_id, $order->id);
        $shortages = [];

        foreach ($needed as $productId => $quantity) {
            $available = bcsub((string) ($onHand[$productId] ?? '0'), $reserved[$productId] ?? '0', 4);

            if (bccomp($quantity, $available, 4) > 0) {
                $shortages[] = __(':product: :available available, :needed ordered', [
                    'product' => Product::find($productId)?->sku,
                    'available' => $this->plain(bccomp($available, '0', 4) > 0 ? $available : '0'),
                    'needed' => $this->plain($quantity),
                ]);
            }
        }

        if ($shortages === []) {
            return;
        }

        $message = __('Not enough stock is free in :warehouse, so part of the order will be on backorder (:shortages).', [
            'warehouse' => $order->warehouse?->code,
            'shortages' => implode('; ', $shortages),
        ]);

        if ($mode === 'block') {
            throw new InvalidAccountingTransactionException($message);
        }

        session()->flash('warning', $message);
    }

    protected function ensureWithinCreditLimit(SalesOrder $order): void
    {
        $mode = config('finance.controls.credit_limit', 'block');
        $customer = $order->customer;

        if ($mode === 'off' || $customer?->credit_limit === null) {
            return;
        }

        $rate = app(ExchangeRateService::class)->rateForDocument($order->company_id, $order->currency_id, $order->order_date);
        $open = app(ReceiptService::class)->getARAging((int) $order->company_id, $customer->id)['total'];
        $exposure = bcadd((string) $open, bcmul((string) $order->total_amount, (string) $rate, 4), 4);

        if (bccomp($exposure, (string) $customer->credit_limit, 4) <= 0) {
            return;
        }

        $message = __(':customer would owe :exposure, above the credit limit of :limit.', [
            'customer' => $customer->name,
            'exposure' => Formatter::amount($exposure),
            'limit' => Formatter::amount($customer->credit_limit),
        ]);

        if ($mode === 'block') {
            throw new InvalidAccountingTransactionException($message);
        }

        session()->flash('warning', $message);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function headerAttributes(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'],
            'customer_reference' => $data['customer_reference'] ?? null,
            'order_date' => $data['order_date'],
            'delivery_date' => $data['delivery_date'] ?? null,
            'warehouse_id' => $data['warehouse_id'],
            'currency_id' => $data['currency_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * @param  list<string>  $allowed
     */
    protected function requireStatus(SalesOrder $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw new InvalidAccountingTransactionException(__('This is not possible while the sales order is :status.', ['status' => strtolower(str_replace('_', ' ', $order->status))]));
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function transition(SalesOrder $order, string $status, string $action, array $context = []): SalesOrder
    {
        $previous = $order->status;
        $order->status = $status;
        $order->updated_by = Auth::id();
        $order->save();

        $this->audit->logCustom('Sales', 'SalesOrder', $order->id, $action, ['previous_status' => $previous, ...$context]);

        return $order->fresh();
    }

    protected function plain(string $number): string
    {
        return str_contains($number, '.') ? (rtrim(rtrim($number, '0'), '.') ?: '0') : $number;
    }
}
