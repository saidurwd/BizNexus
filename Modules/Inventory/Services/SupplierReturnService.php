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
use Modules\Finance\Services\JournalService;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\PurchaseOrderLine;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\SupplierReturn;

/**
 * Returning received goods to the supplier. The return reverses the receipt in the functional currency:
 * goods received not invoiced is debited with the value the receipt credited for these units, stock items
 * leave inventory at the weighted average cost (non-stock items are taken off their expense account), and
 * any difference between the two is purchase price variance. The order's received quantity goes down, so
 * replacements can be received; goods that were already invoiced leave a credit note due from the supplier.
 */
class SupplierReturnService
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
     * @param  array{return_date: string, warehouse_id?: int|null, reason?: string|null, lines: array<int|string, string|int|float|null>}  $data  lines: order line id => quantity returned
     */
    public function return(PurchaseOrder $order, array $data): SupplierReturn
    {
        return DB::transaction(function () use ($order, $data) {
            $order = PurchaseOrder::with('lines')->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->canReturn()) {
                throw new InvalidAccountingTransactionException(__('Nothing received on this order can be returned.'));
            }

            $quantities = collect($data['lines'])->map(fn ($quantity) => (string) ($quantity ?: '0'))->filter(fn (string $quantity) => bccomp($quantity, '0', 4) > 0);

            if ($quantities->isEmpty()) {
                throw new InvalidAccountingTransactionException(__('Enter the quantity returned on at least one line.'));
            }

            $orderLines = PurchaseOrderLine::with('product.category')->where('purchase_order_id', $order->id)->whereKey($quantities->keys())->lockForUpdate()->get()->keyBy('id');

            if ($orderLines->count() !== $quantities->count()) {
                throw new InvalidAccountingTransactionException(__('A returned line does not belong to this order.'));
            }

            $companyId = $order->company_id;
            $functional = $this->companyContext->getBaseCurrency()?->code ?? 'XXX';
            $warehouseId = (int) ($data['warehouse_id'] ?? $order->warehouse_id);
            $grni = $this->defaultAccounts->forPurpose($companyId, AccountPurpose::GoodsReceivedNotInvoiced);

            $return = SupplierReturn::create([
                'company_id' => $companyId,
                'return_number' => $this->documentNumber->generateNumber($companyId, 'RTN'),
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $warehouseId,
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'status' => SupplierReturn::STATUS_POSTED,
                'created_by' => Auth::id(),
            ]);

            $journalLines = [];
            $moves = [];

            foreach ($quantities as $lineId => $quantity) {
                $orderLine = $orderLines[$lineId];
                $product = $orderLine->product;

                if (bccomp($quantity, (string) $orderLine->received_quantity, 4) > 0) {
                    throw new InvalidAccountingTransactionException(__('Only :quantity of :product has been received on this order.', ['quantity' => $this->plain((string) $orderLine->received_quantity), 'product' => $product->sku]));
                }

                $receiptValue = Money::of(bccomp($quantity, (string) $orderLine->received_quantity, 4) === 0
                    ? (string) $orderLine->received_functional_value
                    : bcdiv(bcmul((string) $orderLine->received_functional_value, $quantity, 8), (string) $orderLine->received_quantity, 8), $functional);
                $returnLine = $return->lines()->create(['purchase_order_line_id' => $orderLine->id, 'product_id' => $product->id, 'quantity' => $quantity, 'receipt_value' => $receiptValue->amount]);

                if ($product->isStocked()) {
                    $move = $this->stock->issue($product, $warehouseId, $quantity, $data['return_date'], [
                        'type' => StockMove::SOURCE_SUPPLIER_RETURN, 'id' => $return->id, 'line_id' => $returnLine->id, 'reference' => $return->return_number,
                    ]);
                    $moves[] = $move;
                    $stockValue = Money::of((string) $move->value, $functional)->abs();
                    $creditAccount = $product->accountIdFor('inventory') ?? $this->defaultAccounts->forPurpose($companyId, AccountPurpose::Inventory);
                } else {
                    $stockValue = $receiptValue;
                    $creditAccount = $product->accountIdFor('expense') ?? throw new InvalidAccountingTransactionException(__(':product has no expense account. Set one on the product or its category.', ['product' => $product->sku]));
                }

                $returnLine->update(['stock_value' => $stockValue->amount]);
                $orderLine->update([
                    'received_quantity' => bcsub((string) $orderLine->received_quantity, $quantity, 4),
                    'received_functional_value' => bcsub((string) $orderLine->received_functional_value, $receiptValue->amount, 4),
                ]);

                $description = "Return of {$product->sku} × ".$this->plain($quantity)." — {$order->order_number}";
                $difference = $receiptValue->minus($stockValue);
                $journalLines[] = ['account_id' => $grni, 'description' => $description, 'debit' => $receiptValue->amount, 'credit' => 0];
                $journalLines[] = ['account_id' => $creditAccount, 'description' => $description, 'debit' => 0, 'credit' => $stockValue->amount];

                if (! $difference->isZero()) {
                    $journalLines[] = [
                        'account_id' => $this->defaultAccounts->forPurpose($companyId, AccountPurpose::PurchasePriceVariance),
                        'description' => "Cost difference on {$description}",
                        'debit' => $difference->isNegative() ? $difference->abs()->amount : 0,
                        'credit' => $difference->isPositive() ? $difference->amount : 0,
                    ];
                }
            }

            $journalLines = array_values(array_filter($journalLines, fn (array $line) => bccomp((string) $line['debit'], '0', 4) !== 0 || bccomp((string) $line['credit'], '0', 4) !== 0));

            if (count($journalLines) >= 2) {
                $journal = $this->journalService->postFromSource([
                    'company_id' => $companyId,
                    'journal_date' => $data['return_date'],
                    'reference_type' => 'supplier_return',
                    'reference_id' => $return->id,
                    'description' => "Return {$return->return_number} to {$order->supplier?->name} for {$order->order_number}",
                    'lines' => $journalLines,
                ]);

                $return->update(['journal_id' => $journal->id]);
                StockMove::whereKey(collect($moves)->pluck('id'))->update(['journal_id' => $journal->id]);
            }

            $order->refreshReceiptStatus();
            $this->audit->logCreate('Inventory', 'SupplierReturn', $return->id, $return->load('lines')->toArray());

            return $return->fresh();
        });
    }

    protected function plain(string $number): string
    {
        return str_contains($number, '.') ? (rtrim(rtrim($number, '0'), '.') ?: '0') : $number;
    }
}
