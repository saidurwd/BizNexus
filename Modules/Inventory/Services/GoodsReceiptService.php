<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Services\ExchangeRateService;
use Modules\Finance\Services\JournalService;
use Modules\Inventory\Models\GoodsReceipt;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\PurchaseOrderLine;
use Modules\Inventory\Models\StockMove;

/**
 * Receiving goods against an approved purchase order. A receipt posts at once: stock items go into the
 * warehouse at the order price converted at the receipt date's rate (IAS 2 / IAS 21), non-stock items are
 * expensed, and the total is credited to goods received not invoiced until the supplier's invoice arrives.
 */
class GoodsReceiptService
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
     * @param  array{receipt_date: string, warehouse_id?: int|null, delivery_note?: string|null, notes?: string|null, lines: array<int|string, string|int|float|null>}  $data  lines: order line id => quantity received
     */
    public function receive(PurchaseOrder $order, array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($order, $data) {
            $order = PurchaseOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->canReceive()) {
                throw new InvalidAccountingTransactionException(__('Goods can only be received on approved purchase orders that are still open.'));
            }

            $quantities = collect($data['lines'])->map(fn ($quantity) => (string) ($quantity ?: '0'))->filter(fn (string $quantity) => bccomp($quantity, '0', 4) > 0);

            if ($quantities->isEmpty()) {
                throw new InvalidAccountingTransactionException(__('Enter the quantity received on at least one line.'));
            }

            $orderLines = PurchaseOrderLine::with('product.category')->where('purchase_order_id', $order->id)->whereKey($quantities->keys())->lockForUpdate()->get()->keyBy('id');

            if ($orderLines->count() !== $quantities->count()) {
                throw new InvalidAccountingTransactionException(__('A received line does not belong to this order.'));
            }

            $companyId = $order->company_id;
            $currency = $order->currencyCode();
            $functional = $this->companyContext->getBaseCurrency()?->code ?? $currency;
            $rate = app(ExchangeRateService::class)->rateForDocument($companyId, $order->currency_id, $data['receipt_date']);
            $warehouseId = (int) ($data['warehouse_id'] ?? $order->warehouse_id);

            $receipt = GoodsReceipt::create([
                'company_id' => $companyId,
                'receipt_number' => $this->documentNumber->generateNumber($companyId, 'GRN'),
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $warehouseId,
                'receipt_date' => $data['receipt_date'],
                'delivery_note' => $data['delivery_note'] ?? null,
                'currency_id' => $order->currency_id,
                'exchange_rate' => $rate,
                'status' => GoodsReceipt::STATUS_POSTED,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $grniAccount = $this->defaultAccounts->forPurpose($companyId, AccountPurpose::GoodsReceivedNotInvoiced);
            $journalLines = [];
            $moves = [];
            $total = Money::zero($currency);

            foreach ($quantities as $lineId => $quantity) {
                $orderLine = $orderLines[$lineId];
                $product = $orderLine->product;

                if (bccomp($quantity, $orderLine->outstandingQuantity(), 4) > 0) {
                    throw new InvalidAccountingTransactionException(__('Only :quantity of :product is still to be received on this order.', ['quantity' => rtrim(rtrim($orderLine->outstandingQuantity(), '0'), '.'), 'product' => $product->sku]));
                }

                $value = Money::of($quantity, $currency)->multipliedBy((string) $orderLine->unit_price);
                $functionalValue = $value->convertedTo($functional, $rate);

                $receiptLine = $receipt->lines()->create([
                    'purchase_order_line_id' => $orderLine->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $orderLine->unit_price,
                    'value' => $value->amount,
                    'functional_value' => $functionalValue->amount,
                ]);

                $debitAccount = $product->isStocked()
                    ? $product->accountIdFor('inventory') ?? $this->defaultAccounts->forPurpose($companyId, AccountPurpose::Inventory)
                    : $product->accountIdFor('expense') ?? throw new InvalidAccountingTransactionException(__(':product has no expense account. Set one on the product or its category.', ['product' => $product->sku]));

                $journalLines[] = ['account_id' => $debitAccount, 'description' => "{$product->sku} {$product->name} × ".$this->plain($quantity), 'debit' => $value->amount, 'credit' => 0];
                $total = $total->plus($value);

                if ($product->isStocked()) {
                    $moves[] = $this->stock->receive($product, $warehouseId, $quantity, $functionalValue, $data['receipt_date'], [
                        'type' => StockMove::SOURCE_GOODS_RECEIPT, 'id' => $receipt->id, 'line_id' => $receiptLine->id, 'reference' => $receipt->receipt_number,
                    ]);
                }

                $orderLine->update([
                    'received_quantity' => bcadd((string) $orderLine->received_quantity, $quantity, 4),
                    'received_functional_value' => bcadd((string) $orderLine->received_functional_value, $functionalValue->amount, 4),
                ]);
            }

            $journalLines[] = ['account_id' => $grniAccount, 'description' => "Goods received not invoiced — {$order->order_number}", 'debit' => 0, 'credit' => $total->amount];

            $journal = $this->journalService->postFromSource([
                'company_id' => $companyId,
                'journal_date' => $data['receipt_date'],
                'reference_type' => 'goods_receipt',
                'reference_id' => $receipt->id,
                'description' => "Goods receipt {$receipt->receipt_number} for {$order->order_number}",
                'currency_id' => $order->currency_id,
                'exchange_rate' => $rate,
                'lines' => $journalLines,
            ]);

            $receipt->update(['journal_id' => $journal->id]);
            StockMove::whereKey(collect($moves)->pluck('id'))->update(['journal_id' => $journal->id]);
            $order->refreshReceiptStatus();

            $this->audit->logCreate('Inventory', 'GoodsReceipt', $receipt->id, $receipt->load('lines')->toArray());

            return $receipt->fresh();
        });
    }

    protected function plain(string $quantity): string
    {
        return str_contains($quantity, '.') ? rtrim(rtrim($quantity, '0'), '.') : $quantity;
    }
}
