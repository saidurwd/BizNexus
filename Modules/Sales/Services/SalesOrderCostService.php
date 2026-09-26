<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\CustomerInvoiceLine;
use Modules\Finance\Services\CustomerInvoiceService;
use Modules\Finance\Services\JournalService;
use Modules\Inventory\Services\SalesCostService;
use Modules\Inventory\Services\StockService;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;

/**
 * Invoicing sales orders. An invoice line matched to an order line may bill only what has been delivered
 * (services: ordered) and not yet invoiced. Posting moves the delivered cost of those units from goods
 * delivered not invoiced to cost of goods sold; lines not on an order are costed as before.
 */
class SalesOrderCostService extends SalesCostService
{
    public function __construct(
        StockService $stock,
        JournalService $journalService,
        DefaultAccountService $defaultAccounts,
        CompanyContextService $companyContext,
    ) {
        parent::__construct($stock, $journalService, $defaultAccounts, $companyContext);
    }

    /**
     * Create a draft customer invoice for what can be billed on the order now.
     *
     * @param  array{invoice_date: string, due_date?: string|null, description?: string|null, lines: array<int|string, string|int|float|null>}  $data  lines: order line id => quantity to invoice
     */
    public function createInvoice(SalesOrder $order, array $data): CustomerInvoice
    {
        $orderLines = $order->lines()->with('product.category')->get()->keyBy('id');

        $lines = collect($data['lines'])
            ->map(fn ($quantity) => (string) ($quantity ?: '0'))
            ->filter(fn (string $quantity) => bccomp($quantity, '0', 4) > 0)
            ->map(function (string $quantity, int|string $orderLineId) use ($orderLines, $order) {
                $orderLine = $orderLines->get((int) $orderLineId) ?? throw new InvalidAccountingTransactionException(__('An invoiced line does not belong to this order.'));
                $product = $orderLine->product;

                return [
                    'sales_order_line_id' => $orderLine->id,
                    'product_id' => $product->id,
                    'warehouse_id' => $order->warehouse_id,
                    'account_id' => $product->accountIdFor('revenue') ?? throw new InvalidAccountingTransactionException(__(':product has no revenue account. Set one on the product or its category.', ['product' => $product->sku])),
                    'description' => $orderLine->description,
                    'quantity' => $quantity,
                    'unit_price' => $orderLine->unit_price,
                    'discount_amount' => bccomp((string) $orderLine->discount_amount, '0', 4) > 0
                        ? bcdiv(bcmul((string) $orderLine->discount_amount, $quantity, 8), (string) $orderLine->quantity, 4)
                        : 0,
                    'tax_id' => $orderLine->tax_id,
                ];
            })
            ->values()
            ->all();

        if ($lines === []) {
            throw new InvalidAccountingTransactionException(__('Enter the quantity to invoice on at least one line.'));
        }

        return DB::transaction(function () use ($order, $data, $lines) {
            $invoice = app(CustomerInvoiceService::class)->createInvoice([
                'company_id' => $order->company_id,
                'customer_id' => $order->customer_id,
                'sales_order_id' => $order->id,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency_id' => $order->currency_id,
                'description' => ($data['description'] ?? '') !== '' ? $data['description'] : __('Invoice for :order', ['order' => $order->order_number]),
                'lines' => $lines,
            ]);

            $this->check($invoice);

            return $invoice;
        });
    }

    public function check(CustomerInvoice $invoice): void
    {
        parent::check($invoice);

        $matched = $invoice->lines()->whereNotNull('sales_order_line_id')->get();

        if ($matched->isEmpty()) {
            return;
        }

        $orderLines = SalesOrderLine::with(['salesOrder', 'product'])->whereKey($matched->pluck('sales_order_line_id')->unique())->get()->keyBy('id');
        $pendingElsewhere = CustomerInvoiceLine::query()
            ->whereIn('sales_order_line_id', $orderLines->keys())
            ->where('customer_invoice_id', '!=', $invoice->id)
            ->whereIn('customer_invoice_id', CustomerInvoice::whereIn('status', [CustomerInvoice::STATUS_SUBMITTED, CustomerInvoice::STATUS_APPROVED])->select('id'))
            ->selectRaw('sales_order_line_id, SUM(quantity) AS pending_quantity')
            ->groupBy('sales_order_line_id')
            ->pluck('pending_quantity', 'sales_order_line_id');

        foreach ($matched->groupBy('sales_order_line_id') as $orderLineId => $invoiceLines) {
            $orderLine = $orderLines->get($orderLineId);
            $order = $orderLine?->salesOrder;

            if (! $order || (int) $order->customer_id !== (int) $invoice->customer_id || (int) $order->currency_id !== (int) $invoice->currency_id) {
                throw new InvalidAccountingTransactionException(__('A matched line belongs to another customer\'s order or to an order in another currency.'));
            }

            $billable = bcsub($orderLine->billableQuantity(), (string) ($pendingElsewhere[$orderLineId] ?? '0'), 4);
            $billed = $invoiceLines->reduce(fn (string $sum, CustomerInvoiceLine $line) => bcadd($sum, (string) $line->quantity, 4), '0');

            if (bccomp($billed, $billable, 4) > 0) {
                throw new InvalidAccountingTransactionException(__(':product: the invoice bills :billed but only :billable can be invoiced on :order.', [
                    'product' => $orderLine->product->sku,
                    'billed' => $this->plain($billed),
                    'billable' => $this->plain(bccomp($billable, '0', 4) > 0 ? $billable : '0'),
                    'order' => $order->order_number,
                ]));
            }
        }
    }

    protected function invoiceCostEntries(CustomerInvoice $invoice): array
    {
        [$journalLines, $moves] = parent::invoiceCostEntries($invoice);
        $matched = $invoice->lines()->whereNotNull('sales_order_line_id')->get();

        if ($matched->isEmpty()) {
            return [$journalLines, $moves];
        }

        $this->check($invoice);
        $functional = $this->functionalCurrency();
        $deliveredAccount = null;

        foreach ($matched as $line) {
            $orderLine = SalesOrderLine::with('product.category')->whereKey($line->sales_order_line_id)->lockForUpdate()->firstOrFail();
            $cost = Money::of($this->clearedCost($orderLine, (string) $line->quantity), $functional);

            $orderLine->update([
                'invoiced_cost_value' => bcadd((string) $orderLine->invoiced_cost_value, $cost->amount, 4),
                'invoiced_quantity' => bcadd((string) $orderLine->invoiced_quantity, (string) $line->quantity, 4),
            ]);

            if ($orderLine->needsDelivery()) {
                $line->update(['cost_value' => $cost->amount]);
            }

            if (! $cost->isZero()) {
                $deliveredAccount ??= $this->defaultAccounts->forPurpose($invoice->company_id, AccountPurpose::GoodsDeliveredNotInvoiced);
                array_push($journalLines, ...$this->costOfSaleLines($orderLine->product, $invoice->company_id, $cost, (string) $line->quantity, $deliveredAccount));
            }
        }

        SalesOrder::whereKey($invoice->sales_order_id ?? SalesOrderLine::whereKey($matched->pluck('sales_order_line_id'))->value('sales_order_id'))
            ->get()
            ->each(fn (SalesOrder $order) => $order->refreshFulfilmentStatus());

        return [$journalLines, $moves];
    }

    protected function isFulfilledElsewhere(CustomerInvoiceLine $line): bool
    {
        return $line->sales_order_line_id !== null;
    }

    /**
     * The part of the delivered cost that invoicing this quantity moves to cost of goods sold; the last
     * delivered units take whatever cost is left, so nothing stays in goods delivered not invoiced.
     */
    protected function clearedCost(SalesOrderLine $orderLine, string $quantity): string
    {
        if (! $orderLine->needsDelivery() || bccomp((string) $orderLine->delivered_quantity, '0', 4) <= 0) {
            return '0';
        }

        $uninvoicedDelivered = bcsub((string) $orderLine->delivered_quantity, (string) $orderLine->invoiced_quantity, 4);

        if (bccomp($quantity, $uninvoicedDelivered, 4) >= 0) {
            return bcsub((string) $orderLine->delivered_cost_value, (string) $orderLine->invoiced_cost_value, 4);
        }

        return bcdiv(bcmul((string) $orderLine->delivered_cost_value, $quantity, 8), (string) $orderLine->delivered_quantity, 8);
    }
}
