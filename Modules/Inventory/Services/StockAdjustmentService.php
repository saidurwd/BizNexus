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
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockAdjustment;
use Modules\Inventory\Models\StockMove;

/**
 * Stock adjustments: drafted, then posted to the stock ledger and the general ledger together. Increases
 * use the cost entered on the line (or the current average cost); decreases leave at the average cost. The
 * other side of the entry is the inventory adjustment account.
 */
class StockAdjustmentService
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
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $adjustment = StockAdjustment::create([
                ...$this->headerAttributes($data),
                'company_id' => $data['company_id'],
                'adjustment_number' => $this->documentNumber->generateNumber($data['company_id'], 'ADJ'),
                'status' => StockAdjustment::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            $this->replaceLines($adjustment, $data['lines']);
            $this->audit->logCreate('Inventory', 'StockAdjustment', $adjustment->id, $adjustment->load('lines')->toArray());

            return $adjustment->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(StockAdjustment $adjustment, array $data): StockAdjustment
    {
        $this->requireDraft($adjustment);

        return DB::transaction(function () use ($adjustment, $data) {
            $adjustment->update($this->headerAttributes($data));
            $this->replaceLines($adjustment, $data['lines']);

            return $adjustment->fresh();
        });
    }

    public function delete(StockAdjustment $adjustment): void
    {
        $this->requireDraft($adjustment);
        $this->audit->logDelete('Inventory', 'StockAdjustment', $adjustment->id, $adjustment->toArray());
        $adjustment->delete();
    }

    public function post(StockAdjustment $adjustment): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment) {
            $adjustment = StockAdjustment::whereKey($adjustment->id)->lockForUpdate()->firstOrFail();
            $this->requireDraft($adjustment);

            $companyId = $adjustment->company_id;
            $functional = $this->companyContext->getBaseCurrency()?->code ?? 'XXX';
            $adjustmentAccount = $this->defaultAccounts->forPurpose($companyId, AccountPurpose::InventoryAdjustment);
            $journalLines = [];
            $moves = [];
            $net = Money::zero($functional);
            $source = fn (int $lineId) => ['type' => StockMove::SOURCE_ADJUSTMENT, 'id' => $adjustment->id, 'line_id' => $lineId, 'reference' => $adjustment->adjustment_number];

            foreach ($adjustment->lines()->with('product.category')->get() as $line) {
                $product = $line->product;
                $quantity = (string) $line->quantity;

                if ($adjustment->reason === StockAdjustment::REASON_COUNT) {
                    // Count against what the books show now, not when the count sheet was drafted.
                    $booked = $this->stock->quantityIn($product->id, $adjustment->warehouse_id);
                    $quantity = bcsub(bcadd((string) $line->system_quantity, $quantity, 4), $booked, 4);
                    $line->update(['system_quantity' => $booked, 'quantity' => $quantity]);

                    if (bccomp($quantity, '0', 4) === 0) {
                        continue;
                    }
                }

                $move = bccomp($quantity, '0', 4) > 0
                    ? $this->stock->receive($product, $adjustment->warehouse_id, $quantity, Money::of(bcmul($line->unit_cost !== null ? (string) $line->unit_cost : $product->averageCost(), $quantity, 8), $functional), $adjustment->adjustment_date->toDateString(), $source($line->id))
                    : $this->stock->issue($product, $adjustment->warehouse_id, bcmul($quantity, '-1', 4), $adjustment->adjustment_date->toDateString(), $source($line->id));

                $value = Money::of((string) $move->value, $functional);
                $line->update(['value' => $value->amount]);
                $moves[] = $move;

                if ($value->isZero()) {
                    continue;
                }

                $inventoryAccount = $product->accountIdFor('inventory') ?? $this->defaultAccounts->forPurpose($companyId, AccountPurpose::Inventory);
                $journalLines[] = [
                    'account_id' => $inventoryAccount,
                    'description' => "{$product->sku} {$product->name}",
                    'debit' => $value->isPositive() ? $value->amount : 0,
                    'credit' => $value->isNegative() ? $value->abs()->amount : 0,
                ];
                $net = $net->plus($value);
            }

            $journalId = null;

            if ($journalLines !== []) {
                $journalLines[] = [
                    'account_id' => $adjustmentAccount,
                    'description' => "Stock adjustment {$adjustment->adjustment_number} ({$adjustment->reasonLabel()})",
                    'debit' => $net->isNegative() ? $net->abs()->amount : 0,
                    'credit' => $net->isPositive() ? $net->amount : 0,
                ];

                $journalId = $this->journalService->postFromSource([
                    'company_id' => $companyId,
                    'journal_date' => $adjustment->adjustment_date->toDateString(),
                    'reference_type' => 'stock_adjustment',
                    'reference_id' => $adjustment->id,
                    'description' => "Stock adjustment {$adjustment->adjustment_number}",
                    'lines' => $journalLines,
                ])->id;

                StockMove::whereKey(collect($moves)->pluck('id'))->update(['journal_id' => $journalId]);
            }

            $adjustment->update(['status' => StockAdjustment::STATUS_POSTED, 'journal_id' => $journalId, 'posted_by' => Auth::id(), 'posted_at' => now()]);
            $this->audit->logCustom('Inventory', 'StockAdjustment', $adjustment->id, 'POST', ['journal_id' => $journalId]);

            return $adjustment->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function headerAttributes(array $data): array
    {
        return [
            'warehouse_id' => $data['warehouse_id'],
            'adjustment_date' => $data['adjustment_date'],
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * Lines hold either a signed quantity change or, for a count, the counted quantity (turned into the
     * difference from what the books show in the warehouse).
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function replaceLines(StockAdjustment $adjustment, array $lines): void
    {
        $adjustment->lines()->delete();

        foreach ($lines as $line) {
            $product = Product::findOrFail($line['product_id']);

            if (! $product->isStocked()) {
                throw new InvalidAccountingTransactionException(__(':product is not a stock item.', ['product' => $product->sku]));
            }

            $systemQuantity = null;
            $quantity = (string) ($line['quantity'] ?? '0');

            if ($adjustment->reason === StockAdjustment::REASON_COUNT) {
                $systemQuantity = $this->stock->quantityIn($product->id, $adjustment->warehouse_id);
                $quantity = bcsub((string) $line['counted_quantity'], $systemQuantity, 4);
            }

            if (bccomp($quantity, '0', 4) === 0) {
                continue;
            }

            $adjustment->lines()->create([
                'product_id' => $product->id,
                'system_quantity' => $systemQuantity,
                'quantity' => $quantity,
                'unit_cost' => isset($line['unit_cost']) && $line['unit_cost'] !== '' ? $line['unit_cost'] : null,
            ]);
        }

        if ($adjustment->lines()->doesntExist()) {
            throw new InvalidAccountingTransactionException(__('The adjustment changes no quantities.'));
        }
    }

    protected function requireDraft(StockAdjustment $adjustment): void
    {
        if (! $adjustment->isDraft()) {
            throw new InvalidAccountingTransactionException(__('Only draft adjustments can be changed or posted.'));
        }
    }
}
