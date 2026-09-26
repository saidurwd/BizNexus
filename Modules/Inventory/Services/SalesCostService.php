<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Support\Money;
use Modules\Finance\Contracts\SalesCostOfGoods;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\CustomerInvoiceLine;
use Modules\Finance\Services\JournalService;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockMove;

/**
 * Cost of goods sold (IAS 2.34): when a customer invoice with stock items posts, the goods leave their
 * warehouse at the weighted average cost and a journal in the functional currency moves that cost from
 * inventory to cost of goods sold.
 */
class SalesCostService implements SalesCostOfGoods
{
    public function __construct(
        protected StockService $stock,
        protected JournalService $journalService,
        protected DefaultAccountService $defaultAccounts,
        protected CompanyContextService $companyContext,
    ) {}

    public function check(CustomerInvoice $invoice): void
    {
        $required = [];

        foreach ($this->stockLines($invoice) as $line) {
            if ($line->warehouse_id === null) {
                throw new InvalidAccountingTransactionException(__('Choose the warehouse :product is delivered from.', ['product' => $line->product->sku]));
            }

            $key = "{$line->product_id}:{$line->warehouse_id}";
            $required[$key] = bcadd($required[$key] ?? '0', (string) $line->quantity, 4);
        }

        if (config('inventory.allow_negative_stock')) {
            return;
        }

        foreach ($required as $key => $quantity) {
            [$productId, $warehouseId] = array_map('intval', explode(':', $key));
            $available = $this->stock->quantityIn($productId, $warehouseId);

            if (bccomp($quantity, $available, 4) > 0) {
                throw new InvalidAccountingTransactionException(__('Not enough :product in stock: :available available, :requested on the invoice.', [
                    'product' => Product::find($productId)?->sku,
                    'available' => rtrim(rtrim($available, '0'), '.') ?: '0',
                    'requested' => rtrim(rtrim($quantity, '0'), '.'),
                ]));
            }
        }
    }

    public function invoicePosted(CustomerInvoice $invoice): void
    {
        $lines = $this->stockLines($invoice);

        if ($lines->isEmpty()) {
            return;
        }

        $this->check($invoice);

        $companyId = $invoice->company_id;
        $functional = $this->companyContext->getBaseCurrency()?->code ?? 'XXX';
        $date = $invoice->invoice_date->toDateString();
        $journalLines = [];
        $moves = [];

        foreach ($lines as $line) {
            $product = $line->product;
            $move = $this->stock->issue($product, $line->warehouse_id, (string) $line->quantity, $date, [
                'type' => StockMove::SOURCE_CUSTOMER_INVOICE, 'id' => $invoice->id, 'line_id' => $line->id, 'reference' => $invoice->invoice_number,
            ]);
            $cost = Money::of((string) $move->value, $functional)->abs();
            $line->update(['cost_value' => $cost->amount]);
            $moves[] = $move;

            if ($cost->isZero()) {
                continue;
            }

            $description = "Cost of {$product->sku} × ".rtrim(rtrim((string) $line->quantity, '0'), '.');
            $journalLines[] = ['account_id' => $product->accountIdFor('cogs') ?? $this->defaultAccounts->forPurpose($companyId, AccountPurpose::CostOfGoodsSold), 'description' => $description, 'debit' => $cost->amount, 'credit' => 0];
            $journalLines[] = ['account_id' => $product->accountIdFor('inventory') ?? $this->defaultAccounts->forPurpose($companyId, AccountPurpose::Inventory), 'description' => $description, 'debit' => 0, 'credit' => $cost->amount];
        }

        if ($journalLines === []) {
            return;
        }

        $journal = $this->journalService->postFromSource([
            'company_id' => $companyId,
            'journal_date' => $date,
            'reference_type' => 'customer_invoice_cost',
            'reference_id' => $invoice->id,
            'description' => "Cost of goods sold — invoice {$invoice->invoice_number}",
            'lines' => $journalLines,
        ]);

        StockMove::whereKey(collect($moves)->pluck('id'))->update(['journal_id' => $journal->id]);
        $invoice->update(['cost_journal_id' => $journal->id]);
    }

    /**
     * @return Collection<int, CustomerInvoiceLine>
     */
    protected function stockLines(CustomerInvoice $invoice)
    {
        return $invoice->lines()->with('product.category')->whereNotNull('product_id')->get()
            ->filter(fn (CustomerInvoiceLine $line) => $line->product?->isStocked());
    }
}
