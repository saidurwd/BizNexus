<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Support\Money;
use Modules\Finance\Contracts\SalesCostOfGoods;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\CustomerCreditNote;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\CustomerInvoiceLine;
use Modules\Finance\Services\JournalService;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockMove;

/**
 * Cost of goods sold (IAS 2.34): when a customer invoice with stock items posts, the goods leave their
 * warehouse at the weighted average cost and a journal in the functional currency moves that cost from
 * inventory to cost of goods sold. A credit note that returns goods to a warehouse reverses it.
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
                    'available' => $this->plain($available),
                    'requested' => $this->plain($quantity),
                ]));
            }
        }
    }

    public function invoicePosted(CustomerInvoice $invoice): void
    {
        [$journalLines, $moves] = $this->invoiceCostEntries($invoice);

        if ($journalLines === []) {
            return;
        }

        $journal = $this->journalService->postFromSource([
            'company_id' => $invoice->company_id,
            'journal_date' => $invoice->invoice_date->toDateString(),
            'reference_type' => 'customer_invoice_cost',
            'reference_id' => $invoice->id,
            'description' => "Cost of goods sold — invoice {$invoice->invoice_number}",
            'lines' => $journalLines,
        ]);

        StockMove::whereKey(collect($moves)->pluck('id'))->update(['journal_id' => $journal->id]);
        $invoice->update(['cost_journal_id' => $journal->id]);
    }

    public function creditNotePosted(CustomerCreditNote $creditNote): void
    {
        $lines = $creditNote->lines()->with('product.category')->whereNotNull('product_id')->whereNotNull('warehouse_id')->get()
            ->filter(fn ($line) => $line->product?->isStocked());

        if ($lines->isEmpty()) {
            return;
        }

        $companyId = $creditNote->company_id;
        $functional = $this->functionalCurrency();
        $date = $creditNote->note_date->toDateString();
        $originalCosts = $this->originalUnitCosts($creditNote);
        $journalLines = [];
        $moves = [];

        foreach ($lines as $line) {
            $product = $line->product;
            $unitCost = $originalCosts[$product->id] ?? $product->averageCost();
            $value = Money::of(bcmul($unitCost, (string) $line->quantity, 8), $functional);
            $moves[] = $this->stock->receive($product, $line->warehouse_id, (string) $line->quantity, $value, $date, [
                'type' => StockMove::SOURCE_CUSTOMER_RETURN, 'id' => $creditNote->id, 'line_id' => $line->id, 'reference' => $creditNote->note_number,
            ]);
            $line->update(['cost_value' => $value->amount]);

            if ($value->isZero()) {
                continue;
            }

            $description = "Return of {$product->sku} × ".$this->plain((string) $line->quantity);
            $journalLines[] = ['account_id' => $this->inventoryAccount($product, $companyId), 'description' => $description, 'debit' => $value->amount, 'credit' => 0];
            $journalLines[] = ['account_id' => $this->cogsAccount($product, $companyId), 'description' => $description, 'debit' => 0, 'credit' => $value->amount];
        }

        if ($journalLines === []) {
            return;
        }

        $journal = $this->journalService->postFromSource([
            'company_id' => $companyId,
            'journal_date' => $date,
            'reference_type' => 'customer_credit_note_cost',
            'reference_id' => $creditNote->id,
            'description' => "Goods returned — credit note {$creditNote->note_number}",
            'lines' => $journalLines,
        ]);

        StockMove::whereKey(collect($moves)->pluck('id'))->update(['journal_id' => $journal->id]);
        $creditNote->forceFill(['cost_journal_id' => $journal->id])->save();
    }

    /**
     * Issue the stock the invoice sells directly (not delivered earlier) and build its cost journal lines.
     *
     * @return array{0: list<array<string, mixed>>, 1: list<StockMove>}
     */
    protected function invoiceCostEntries(CustomerInvoice $invoice): array
    {
        $lines = $this->stockLines($invoice);

        if ($lines->isEmpty()) {
            return [[], []];
        }

        $this->check($invoice);

        $functional = $this->functionalCurrency();
        $journalLines = [];
        $moves = [];

        foreach ($lines as $line) {
            $product = $line->product;
            $move = $this->stock->issue($product, $line->warehouse_id, (string) $line->quantity, $invoice->invoice_date->toDateString(), [
                'type' => StockMove::SOURCE_CUSTOMER_INVOICE, 'id' => $invoice->id, 'line_id' => $line->id, 'reference' => $invoice->invoice_number,
            ]);
            $cost = Money::of((string) $move->value, $functional)->abs();
            $line->update(['cost_value' => $cost->amount]);
            $moves[] = $move;

            array_push($journalLines, ...$this->costOfSaleLines($product, $invoice->company_id, $cost, (string) $line->quantity, $this->inventoryAccount($product, $invoice->company_id)));
        }

        return [$journalLines, $moves];
    }

    /**
     * Debit cost of goods sold and credit where the cost sat (inventory, or goods delivered not invoiced).
     *
     * @return list<array<string, mixed>>
     */
    protected function costOfSaleLines(Product $product, int $companyId, Money $cost, string $quantity, int $creditAccountId): array
    {
        if ($cost->isZero()) {
            return [];
        }

        $description = "Cost of {$product->sku} × ".$this->plain($quantity);

        return [
            ['account_id' => $this->cogsAccount($product, $companyId), 'description' => $description, 'debit' => $cost->amount, 'credit' => 0],
            ['account_id' => $creditAccountId, 'description' => $description, 'debit' => 0, 'credit' => $cost->amount],
        ];
    }

    /**
     * Invoice lines for stock items that leave the warehouse when the invoice posts.
     *
     * @return Collection<int, CustomerInvoiceLine>
     */
    protected function stockLines(CustomerInvoice $invoice): Collection
    {
        return $invoice->lines()->with('product.category')->whereNotNull('product_id')->get()
            ->filter(fn (CustomerInvoiceLine $line) => $line->product?->isStocked() && ! $this->isFulfilledElsewhere($line))
            ->values();
    }

    /**
     * Whether the line's goods left stock on another document (e.g. a delivery note) before the invoice.
     */
    protected function isFulfilledElsewhere(CustomerInvoiceLine $line): bool
    {
        return false;
    }

    /**
     * Unit cost at which each product left stock on the credited invoice, so a return goes back at that cost.
     *
     * @return array<int, string>
     */
    protected function originalUnitCosts(CustomerCreditNote $creditNote): array
    {
        if ($creditNote->customer_invoice_id === null) {
            return [];
        }

        return CustomerInvoiceLine::where('customer_invoice_id', $creditNote->customer_invoice_id)
            ->whereNotNull('product_id')->whereNotNull('cost_value')->where('quantity', '>', 0)
            ->get()
            ->groupBy('product_id')
            ->map(function (Collection $lines) {
                $quantity = $lines->reduce(fn (string $sum, $line) => bcadd($sum, (string) $line->quantity, 4), '0');
                $cost = $lines->reduce(fn (string $sum, $line) => bcadd($sum, (string) $line->cost_value, 4), '0');

                return bccomp($quantity, '0', 4) > 0 ? bcdiv($cost, $quantity, 6) : '0';
            })
            ->filter(fn (string $cost) => bccomp($cost, '0', 6) > 0)
            ->all();
    }

    protected function inventoryAccount(Product $product, int $companyId): int
    {
        return $product->accountIdFor('inventory') ?? $this->defaultAccounts->forPurpose($companyId, AccountPurpose::Inventory);
    }

    protected function cogsAccount(Product $product, int $companyId): int
    {
        return $product->accountIdFor('cogs') ?? $this->defaultAccounts->forPurpose($companyId, AccountPurpose::CostOfGoodsSold);
    }

    protected function functionalCurrency(): string
    {
        return $this->companyContext->getBaseCurrency()?->code ?? 'XXX';
    }

    protected function plain(string $number): string
    {
        return str_contains($number, '.') ? (rtrim(rtrim($number, '0'), '.') ?: '0') : $number;
    }
}
