<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Support\Money;
use Modules\Finance\Contracts\PurchaseMatching;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierInvoiceLine;
use Modules\Finance\Services\SupplierInvoiceService;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\PurchaseOrderLine;

/**
 * Three-way match: a supplier invoice line matched to a purchase order line may bill only what has been
 * received and not yet invoiced, at a price within the configured tolerance of the order price.
 *
 * Posting clears goods received not invoiced at the value the receipts credited to it: the order price in the
 * order currency, and the receipts' functional value. A price difference goes to purchase price variance and
 * an exchange rate difference between receipt and invoice to realised exchange gain or loss.
 */
class PurchaseInvoiceMatcher implements PurchaseMatching
{
    public function __construct(
        protected DefaultAccountService $defaultAccounts,
        protected CompanyContextService $companyContext,
    ) {}

    /**
     * Create a draft supplier invoice for what has been received on an order and not yet invoiced.
     *
     * @param  array{invoice_number: string, invoice_date: string, due_date?: string|null, description?: string|null, lines: array<int|string, array{quantity?: string|int|float|null, unit_price?: string|int|float|null, tax_id?: int|string|null}>}  $data  lines keyed by order line id
     */
    public function createInvoice(PurchaseOrder $order, array $data): SupplierInvoice
    {
        $orderLines = $order->lines()->with('product')->get()->keyBy('id');
        $grniAccount = $this->defaultAccounts->forPurpose($order->company_id, AccountPurpose::GoodsReceivedNotInvoiced);

        $lines = collect($data['lines'])
            ->filter(fn (array $line) => bccomp((string) ($line['quantity'] ?? '0') ?: '0', '0', 4) > 0)
            ->map(function (array $line, int|string $orderLineId) use ($orderLines, $grniAccount) {
                $orderLine = $orderLines->get((int) $orderLineId) ?? throw new InvalidAccountingTransactionException(__('A matched line does not belong to this order.'));

                return [
                    'purchase_order_line_id' => $orderLine->id,
                    'product_id' => $orderLine->product_id,
                    'account_id' => $grniAccount,
                    'description' => $orderLine->description,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'] ?? $orderLine->unit_price,
                    'tax_id' => ($line['tax_id'] ?? null) ?: null,
                ];
            })
            ->values()
            ->all();

        if ($lines === []) {
            throw new InvalidAccountingTransactionException(__('Enter the quantity invoiced on at least one line.'));
        }

        return DB::transaction(function () use ($order, $data, $lines) {
            $invoice = app(SupplierInvoiceService::class)->createInvoice([
                'company_id' => $order->company_id,
                'supplier_id' => $order->supplier_id,
                'purchase_order_id' => $order->id,
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency_id' => $order->currency_id,
                'description' => $data['description'] ?? __('Invoice for :order', ['order' => $order->order_number]),
                'lines' => $lines,
            ]);

            $this->check($invoice);

            return $invoice;
        });
    }

    public function check(SupplierInvoice $invoice): void
    {
        $matched = $invoice->lines()->whereNotNull('purchase_order_line_id')->get();

        if ($matched->isEmpty()) {
            return;
        }

        $orderLines = PurchaseOrderLine::with(['purchaseOrder', 'product'])->whereKey($matched->pluck('purchase_order_line_id')->unique())->get()->keyBy('id');
        $pendingElsewhere = SupplierInvoiceLine::query()
            ->whereIn('purchase_order_line_id', $orderLines->keys())
            ->where('supplier_invoice_id', '!=', $invoice->id)
            ->whereIn('supplier_invoice_id', SupplierInvoice::whereIn('status', [SupplierInvoice::STATUS_SUBMITTED, SupplierInvoice::STATUS_APPROVED])->select('id'))
            ->selectRaw('purchase_order_line_id, SUM(quantity) AS pending_quantity')
            ->groupBy('purchase_order_line_id')
            ->pluck('pending_quantity', 'purchase_order_line_id');
        $tolerance = (string) config('inventory.price_tolerance_percent', 0);

        foreach ($matched->groupBy('purchase_order_line_id') as $orderLineId => $invoiceLines) {
            $orderLine = $orderLines->get($orderLineId);
            $order = $orderLine?->purchaseOrder;

            if (! $order || (int) $order->supplier_id !== (int) $invoice->supplier_id || (int) $order->currency_id !== (int) $invoice->currency_id) {
                throw new InvalidAccountingTransactionException(__('A matched line belongs to another supplier\'s order or to an order in another currency.'));
            }

            $billable = bcsub($orderLine->uninvoicedQuantity(), (string) ($pendingElsewhere[$orderLineId] ?? '0'), 4);
            $billed = $invoiceLines->reduce(fn (string $sum, SupplierInvoiceLine $line) => bcadd($sum, (string) $line->quantity, 4), '0');

            if (bccomp($billed, $billable, 4) > 0) {
                throw new InvalidAccountingTransactionException(__(':product: the invoice bills :billed but only :billable has been received and not yet invoiced on :order.', [
                    'product' => $orderLine->product->sku,
                    'billed' => $this->plain($billed),
                    'billable' => $this->plain(bccomp($billable, '0', 4) > 0 ? $billable : '0'),
                    'order' => $order->order_number,
                ]));
            }

            foreach ($invoiceLines as $line) {
                $orderPrice = (string) $orderLine->unit_price;
                $difference = ltrim(bcsub((string) $line->unit_price, $orderPrice, 6), '-');
                $allowed = bcdiv(bcmul($orderPrice, $tolerance, 6), '100', 6);

                if (bccomp($difference, $allowed, 6) > 0) {
                    throw new InvalidAccountingTransactionException(__(':product: the invoice price :price differs from the order price :order_price by more than :tolerance%.', [
                        'product' => $orderLine->product->sku,
                        'price' => $this->plain((string) $line->unit_price),
                        'order_price' => $this->plain($orderPrice),
                        'tolerance' => $this->plain($tolerance),
                    ]));
                }
            }
        }
    }

    public function costLines(SupplierInvoice $invoice, SupplierInvoiceLine $line, Money $cost): array
    {
        if ($line->purchase_order_line_id === null) {
            return [['account_id' => $line->account_id, 'description' => $line->description, 'debit' => $cost->amount, 'credit' => 0]];
        }

        $orderLine = PurchaseOrderLine::whereKey($line->purchase_order_line_id)->lockForUpdate()->firstOrFail();
        $companyId = $invoice->company_id;
        $grniAccount = $this->defaultAccounts->forPurpose($companyId, AccountPurpose::GoodsReceivedNotInvoiced);
        $functional = $this->functionalCurrency($cost->currency);
        $cleared = Money::of($this->clearedFunctionalValue($orderLine, (string) $line->quantity, $functional), $functional);
        $isForeign = $cost->currency !== $functional;

        // In the functional currency the receipts' value is cleared exactly and any difference is price variance;
        // in a foreign currency the order price is cleared and the rate difference is posted separately below.
        $received = $isForeign ? Money::of((string) $line->quantity, $cost->currency)->multipliedBy((string) $orderLine->unit_price) : $cleared;
        $variance = $cost->minus($received);

        $lines = [['account_id' => $grniAccount, 'description' => $line->description, 'debit' => $received->amount, 'credit' => 0]];

        if (! $variance->isZero()) {
            $lines[] = [
                'account_id' => $this->defaultAccounts->forPurpose($companyId, AccountPurpose::PurchasePriceVariance),
                'description' => "Price variance on {$line->description}",
                'debit' => $variance->isPositive() ? $variance->amount : 0,
                'credit' => $variance->isNegative() ? $variance->abs()->amount : 0,
            ];
        }

        $exchangeDifference = $isForeign ? $cleared->minus($received->convertedTo($functional, $invoice->exchange_rate)) : Money::zero($functional);

        if (! $exchangeDifference->isZero()) {
            $lines[] = ['account_id' => $grniAccount, 'description' => "Exchange difference on {$line->description}", 'line_type' => JournalLine::TYPE_FX_REALIZED, 'functional_amount' => $exchangeDifference->amount];
            $lines[] = [
                'account_id' => $this->defaultAccounts->forPurpose($companyId, $exchangeDifference->isPositive() ? AccountPurpose::RealizedFxGain : AccountPurpose::RealizedFxLoss),
                'description' => "Exchange difference on {$line->description}",
                'line_type' => JournalLine::TYPE_FX_REALIZED,
                'functional_amount' => $exchangeDifference->negated()->amount,
            ];
        }

        return $lines;
    }

    public function invoicePosted(SupplierInvoice $invoice): void
    {
        $matched = $invoice->lines()->whereNotNull('purchase_order_line_id')->get();

        $functional = $this->functionalCurrency($invoice->currency?->code);

        foreach ($matched as $line) {
            $orderLine = PurchaseOrderLine::whereKey($line->purchase_order_line_id)->lockForUpdate()->firstOrFail();
            $orderLine->update([
                'invoiced_functional_value' => bcadd((string) $orderLine->invoiced_functional_value, $this->clearedFunctionalValue($orderLine, (string) $line->quantity, $functional), 4),
                'invoiced_quantity' => bcadd((string) $orderLine->invoiced_quantity, (string) $line->quantity, 4),
            ]);
        }

        PurchaseOrder::whereKey($matched->map(fn (SupplierInvoiceLine $line) => $line->purchaseOrderLine?->purchase_order_id)->filter()->unique())
            ->get()
            ->each(fn (PurchaseOrder $order) => $order->refreshReceiptStatus());
    }

    /**
     * The part of the receipts' functional value that billing this quantity clears; the last quantity
     * clears whatever is left, so nothing remains in goods received not invoiced from rounding.
     */
    protected function clearedFunctionalValue(PurchaseOrderLine $orderLine, string $quantity, string $functional): string
    {
        if (bccomp($quantity, $orderLine->uninvoicedQuantity(), 4) >= 0) {
            return Money::of(bcsub((string) $orderLine->received_functional_value, (string) $orderLine->invoiced_functional_value, 4), $functional)->amount;
        }

        return Money::of(bcdiv(bcmul((string) $orderLine->received_functional_value, $quantity, 8), (string) $orderLine->received_quantity, 8), $functional)->amount;
    }

    protected function functionalCurrency(?string $fallback): string
    {
        return $this->companyContext->getBaseCurrency()?->code ?? $fallback ?? 'XXX';
    }

    protected function plain(string $number): string
    {
        return str_contains($number, '.') ? rtrim(rtrim($number, '0'), '.') : $number;
    }
}
