<?php

namespace Modules\Assets\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Assets\Models\AssetCategory;
use Modules\Assets\Models\AssetTransaction;
use Modules\Assets\Models\FixedAsset;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Support\Money;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierInvoiceLine;
use Modules\Finance\Services\JournalService;
use Modules\Inventory\Models\GoodsReceiptLine;

/**
 * The fixed asset register (IAS 16): assets are recorded at cost, depreciated monthly, and leave the books
 * on disposal with the gain or loss against their carrying amount. Assets bought through purchasing are
 * capitalised from the posted invoice or goods receipt line, whose journal already debited the asset account.
 */
class FixedAssetService
{
    public function __construct(
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit,
        protected JournalService $journalService,
        protected CompanyContextService $companyContext,
    ) {}

    /**
     * Register an asset. With an offset account the cost (and any opening accumulated depreciation) is posted
     * against it; without one the amounts are assumed to be in the ledger already (e.g. opening balances).
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): FixedAsset
    {
        return DB::transaction(function () use ($data) {
            $category = AssetCategory::findOrFail($data['category_id']);
            $functional = $this->functionalCurrency();
            $cost = Money::of($data['cost'], $functional);
            $opening = Money::of($data['opening_accumulated_depreciation'] ?? 0, $functional);
            $residual = Money::of($data['residual_value'] ?? 0, $functional);

            if (! $cost->isPositive()) {
                throw new InvalidAccountingTransactionException(__('The cost must be more than zero.'));
            }

            if ($opening->plus($residual)->compareTo($cost) > 0) {
                throw new InvalidAccountingTransactionException(__('Accumulated depreciation and residual value together cannot exceed the cost.'));
            }

            $inService = CarbonImmutable::parse($data['in_service_date'] ?? $data['acquisition_date']);
            $depreciatedUntil = ! empty($data['depreciated_until']) ? CarbonImmutable::parse($data['depreciated_until'])->endOfMonth()->startOfDay() : null;
            $monthsDepreciated = $depreciatedUntil ? max(0, (int) $inService->startOfMonth()->diffInMonths($depreciatedUntil->startOfMonth()) + 1) : 0;

            if ($opening->isPositive() && ! $depreciatedUntil) {
                throw new InvalidAccountingTransactionException(__('Say up to which month the opening accumulated depreciation was charged.'));
            }

            $asset = FixedAsset::create([
                ...$this->descriptiveAttributes($data),
                'company_id' => $data['company_id'],
                'asset_number' => $this->documentNumber->generateNumber($data['company_id'], 'FA'),
                'category_id' => $category->id,
                'acquisition_date' => $data['acquisition_date'],
                'in_service_date' => $inService->toDateString(),
                'cost' => $cost->amount,
                'residual_value' => $residual->amount,
                'depreciation_method' => $data['depreciation_method'] ?? $category->depreciation_method,
                'useful_life_months' => $data['useful_life_months'] ?? $category->useful_life_months,
                'declining_rate' => ($data['depreciation_method'] ?? $category->depreciation_method) === AssetCategory::METHOD_DECLINING_BALANCE ? ($data['declining_rate'] ?? $category->declining_rate) : null,
                'accumulated_depreciation' => $opening->amount,
                'months_depreciated' => $monthsDepreciated,
                'depreciated_until' => $depreciatedUntil?->toDateString(),
                'status' => FixedAsset::STATUS_ACTIVE,
                'source_type' => $data['source_type'] ?? FixedAsset::SOURCE_MANUAL,
                'source_id' => $data['source_id'] ?? null,
                'created_by' => Auth::id(),
            ]);

            if ($asset->useful_life_months < 1) {
                throw new InvalidAccountingTransactionException(__('The useful life must be at least one month.'));
            }

            $journalId = null;

            if (! empty($data['offset_account_id'])) {
                $lines = [
                    ['account_id' => $category->asset_account_id, 'description' => "{$asset->asset_number} {$asset->name}", 'debit' => $cost->amount, 'credit' => 0],
                    ['account_id' => (int) $data['offset_account_id'], 'description' => "{$asset->asset_number} {$asset->name}", 'debit' => 0, 'credit' => $cost->minus($opening)->amount],
                ];

                if ($opening->isPositive()) {
                    $lines[] = ['account_id' => $category->accumulated_depreciation_account_id, 'description' => "Accumulated depreciation {$asset->asset_number}", 'debit' => 0, 'credit' => $opening->amount];
                }

                $journalId = $this->journalService->postFromSource([
                    'company_id' => $asset->company_id,
                    'journal_date' => $asset->acquisition_date->toDateString(),
                    'reference_type' => 'fixed_asset',
                    'reference_id' => $asset->id,
                    'description' => "Acquisition of {$asset->asset_number} {$asset->name}",
                    'lines' => array_values(array_filter($lines, fn (array $line) => bccomp((string) $line['debit'], '0', 4) !== 0 || bccomp((string) $line['credit'], '0', 4) !== 0)),
                ])->id;
            }

            $this->record($asset, AssetTransaction::TYPE_ACQUISITION, $asset->acquisition_date->toDateString(), $cost->amount, $journalId, $data['notes'] ?? null, ['source_type' => $asset->source_type, 'source_id' => $asset->source_id]);

            if ($opening->isPositive()) {
                $this->record($asset, AssetTransaction::TYPE_OPENING_DEPRECIATION, $depreciatedUntil->toDateString(), $opening->amount, $journalId, null, ['months' => $monthsDepreciated]);
            }

            $this->audit->logCreate('Assets', 'FixedAsset', $asset->id, $asset->toArray());

            return $asset->fresh();
        });
    }

    /**
     * Update what describes an asset. The financial fields can change only until depreciation is charged.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(FixedAsset $asset, array $data): FixedAsset
    {
        $changes = $this->descriptiveAttributes($data);

        if ($asset->transactions()->whereIn('type', [AssetTransaction::TYPE_DEPRECIATION, AssetTransaction::TYPE_IMPAIRMENT, AssetTransaction::TYPE_DISPOSAL])->doesntExist()) {
            $changes += array_filter([
                'in_service_date' => $data['in_service_date'] ?? null,
                'residual_value' => isset($data['residual_value']) ? Money::of($data['residual_value'], $this->functionalCurrency())->amount : null,
                'depreciation_method' => $data['depreciation_method'] ?? null,
                'useful_life_months' => $data['useful_life_months'] ?? null,
                'declining_rate' => $data['declining_rate'] ?? null,
            ], fn ($value) => $value !== null);
        }

        $asset->update($changes);

        return $asset->fresh();
    }

    /**
     * Supplier invoice and goods receipt lines posted to an asset category's asset account and not yet in the
     * register, with their cost in the functional currency.
     *
     * @return Collection<int, array{source_type: string, source_id: int, date: string, reference: string, supplier_id: int|null, supplier: string|null, description: string, quantity: string, cost: string}>
     */
    public function capitalisableLines(): Collection
    {
        $assetAccounts = AssetCategory::pluck('asset_account_id')->unique()->all();
        $registered = FixedAsset::whereIn('source_type', [FixedAsset::SOURCE_SUPPLIER_INVOICE_LINE, FixedAsset::SOURCE_GOODS_RECEIPT_LINE])
            ->get(['source_type', 'source_id'])
            ->map(fn (FixedAsset $asset) => "{$asset->source_type}:{$asset->source_id}")
            ->flip();

        $invoiceLines = SupplierInvoiceLine::with('invoice.supplier')
            ->whereIn('account_id', $assetAccounts)
            ->whereIn('supplier_invoice_id', SupplierInvoice::whereIn('status', [SupplierInvoice::STATUS_POSTED, 'PARTIALLY_PAID', 'PAID'])->select('id'))
            ->get()
            ->map(fn (SupplierInvoiceLine $line) => [
                'source_type' => FixedAsset::SOURCE_SUPPLIER_INVOICE_LINE,
                'source_id' => $line->id,
                'date' => $line->invoice->invoice_date->toDateString(),
                'reference' => $line->invoice->invoice_number,
                'supplier_id' => $line->invoice->supplier_id,
                'supplier' => $line->invoice->supplier?->name,
                'description' => $line->description,
                'quantity' => (string) $line->quantity,
                'cost' => Money::of(bcmul((string) $line->subtotal, (string) ($line->invoice->exchange_rate ?: 1), 8), $this->functionalCurrency())->amount,
            ]);

        $receiptLines = GoodsReceiptLine::with(['goodsReceipt.supplier', 'product.category'])
            ->whereHas('goodsReceipt')
            ->get()
            ->filter(fn (GoodsReceiptLine $line) => $line->product && ! $line->product->isStocked() && in_array($line->product->accountIdFor('expense'), $assetAccounts, true))
            ->map(fn (GoodsReceiptLine $line) => [
                'source_type' => FixedAsset::SOURCE_GOODS_RECEIPT_LINE,
                'source_id' => $line->id,
                'date' => $line->goodsReceipt->receipt_date->toDateString(),
                'reference' => $line->goodsReceipt->receipt_number,
                'supplier_id' => $line->goodsReceipt->supplier_id,
                'supplier' => $line->goodsReceipt->supplier?->name,
                'description' => "{$line->product->sku} {$line->product->name}",
                'quantity' => (string) $line->quantity,
                'cost' => (string) $line->functional_value,
            ]);

        return $invoiceLines->concat($receiptLines)
            ->reject(fn (array $line) => $registered->has("{$line['source_type']}:{$line['source_id']}"))
            ->sortBy('date')
            ->values();
    }

    /**
     * Register an asset for a purchased line; the purchase already debited the asset account.
     *
     * @param  array<string, mixed>  $data
     */
    public function capitalise(string $sourceType, int $sourceId, array $data): FixedAsset
    {
        $line = $this->capitalisableLines()->first(fn (array $line) => $line['source_type'] === $sourceType && $line['source_id'] === $sourceId)
            ?? throw new InvalidAccountingTransactionException(__('This purchase line is not waiting to be registered as an asset.'));

        $category = AssetCategory::findOrFail($data['category_id']);

        return $this->register([
            ...$data,
            'category_id' => $category->id,
            'name' => ($data['name'] ?? '') !== '' ? $data['name'] : $line['description'],
            'acquisition_date' => $line['date'],
            'in_service_date' => $data['in_service_date'] ?? $line['date'],
            'cost' => $line['cost'],
            'supplier_id' => $line['supplier_id'],
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'offset_account_id' => null,
            'opening_accumulated_depreciation' => 0,
            'notes' => __('Capitalised from :reference', ['reference' => $line['reference']]),
        ]);
    }

    /**
     * Move an asset to another branch, department, location or custodian. No ledger effect.
     *
     * @param  array<string, mixed>  $data
     */
    public function transfer(FixedAsset $asset, array $data): FixedAsset
    {
        $this->requireInUse($asset);
        $fields = ['branch_id', 'department_id', 'location', 'custodian'];
        $before = $asset->only($fields);
        $after = collect($fields)->mapWithKeys(fn (string $field) => [$field => $data[$field] ?? null])->all();

        return DB::transaction(function () use ($asset, $data, $before, $after) {
            $asset->update($after);
            $this->record($asset, AssetTransaction::TYPE_TRANSFER, $data['transfer_date'], '0', null, $data['notes'] ?? null, ['from' => $before, 'to' => $after]);

            return $asset->fresh();
        });
    }

    /**
     * Write the asset down to its recoverable amount (IAS 36): the loss is expensed and added to accumulated
     * depreciation and impairment; later depreciation spreads the lower amount over the remaining life.
     */
    public function impair(FixedAsset $asset, string $date, string $amount, ?string $reason): FixedAsset
    {
        return DB::transaction(function () use ($asset, $date, $amount, $reason) {
            $asset = FixedAsset::with('category')->whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $this->requireInUse($asset);
            $loss = Money::of($amount, $this->functionalCurrency());

            if (! $loss->isPositive() || bccomp($loss->amount, $asset->depreciableAmount(), 4) > 0) {
                throw new InvalidAccountingTransactionException(__('An impairment must be more than zero and cannot exceed the carrying amount less residual value (:max).', ['max' => $asset->depreciableAmount()]));
            }

            $description = "Impairment of {$asset->asset_number} {$asset->name}";
            $journal = $this->journalService->postFromSource([
                'company_id' => $asset->company_id,
                'journal_date' => $date,
                'reference_type' => 'fixed_asset',
                'reference_id' => $asset->id,
                'description' => $description,
                'lines' => [
                    ['account_id' => $asset->category->depreciation_expense_account_id, 'description' => $description, 'debit' => $loss->amount, 'credit' => 0, 'branch_id' => $asset->branch_id, 'department_id' => $asset->department_id],
                    ['account_id' => $asset->category->accumulated_depreciation_account_id, 'description' => $description, 'debit' => 0, 'credit' => $loss->amount],
                ],
            ]);

            $asset->update([
                'accumulated_depreciation' => bcadd((string) $asset->accumulated_depreciation, $loss->amount, 4),
                'status' => bccomp(bcsub(bcsub((string) $asset->cost, bcadd((string) $asset->accumulated_depreciation, $loss->amount, 4), 4), (string) $asset->residual_value, 4), '0', 4) <= 0 ? FixedAsset::STATUS_FULLY_DEPRECIATED : $asset->status,
            ]);
            $this->record($asset, AssetTransaction::TYPE_IMPAIRMENT, $date, $loss->amount, $journal->id, $reason);

            return $asset->fresh();
        });
    }

    /**
     * Sell or scrap an asset. Depreciation still owed up to the end of the month before disposal is charged
     * first; then cost and accumulated depreciation leave the books and the difference from the proceeds is
     * the gain or loss on disposal.
     */
    public function dispose(FixedAsset $asset, string $date, string $proceeds, ?int $proceedsAccountId, ?string $notes): FixedAsset
    {
        return DB::transaction(function () use ($asset, $date, $proceeds, $proceedsAccountId, $notes) {
            $asset = FixedAsset::with('category')->whereKey($asset->id)->lockForUpdate()->firstOrFail();

            if ($asset->status === FixedAsset::STATUS_DISPOSED) {
                throw new InvalidAccountingTransactionException(__('This asset has already been disposed of.'));
            }

            $functional = $this->functionalCurrency();
            $proceedsAmount = Money::of($proceeds ?: 0, $functional);

            if ($proceedsAmount->isPositive() && ! $proceedsAccountId) {
                throw new InvalidAccountingTransactionException(__('Choose the account the sale proceeds go to.'));
            }

            $disposalDate = CarbonImmutable::parse($date);
            $category = $asset->category;
            $description = "Disposal of {$asset->asset_number} {$asset->name}";

            // Depreciation owed up to the end of the month before disposal.
            $catchUp = app(DepreciationService::class)->chargeThrough($asset, $disposalDate->startOfMonth()->subDay());
            $lines = [];

            if (bccomp($catchUp['amount'], '0', 4) > 0) {
                $lines[] = ['account_id' => $category->depreciation_expense_account_id, 'description' => "Depreciation to disposal of {$asset->asset_number}", 'debit' => $catchUp['amount'], 'credit' => 0, 'branch_id' => $asset->branch_id, 'department_id' => $asset->department_id];
                $lines[] = ['account_id' => $category->accumulated_depreciation_account_id, 'description' => "Depreciation to disposal of {$asset->asset_number}", 'debit' => 0, 'credit' => $catchUp['amount']];
            }

            $cost = Money::of((string) $asset->cost, $functional);
            $accumulated = Money::of((string) $asset->accumulated_depreciation, $functional);
            $gain = $proceedsAmount->minus($cost->minus($accumulated));

            $lines[] = ['account_id' => $category->accumulated_depreciation_account_id, 'description' => $description, 'debit' => $accumulated->amount, 'credit' => 0];

            if ($proceedsAmount->isPositive()) {
                $lines[] = ['account_id' => $proceedsAccountId, 'description' => $description, 'debit' => $proceedsAmount->amount, 'credit' => 0];
            }

            $lines[] = ['account_id' => $category->asset_account_id, 'description' => $description, 'debit' => 0, 'credit' => $cost->amount];

            if (! $gain->isZero()) {
                $lines[] = ['account_id' => $category->disposal_account_id, 'description' => $gain->isPositive() ? "Gain on {$description}" : "Loss on {$description}", 'debit' => $gain->isNegative() ? $gain->abs()->amount : 0, 'credit' => $gain->isPositive() ? $gain->amount : 0];
            }

            $lines = array_values(array_filter($lines, fn (array $line) => bccomp((string) $line['debit'], '0', 4) !== 0 || bccomp((string) $line['credit'], '0', 4) !== 0));
            $journal = $this->journalService->postFromSource([
                'company_id' => $asset->company_id,
                'journal_date' => $disposalDate->toDateString(),
                'reference_type' => 'fixed_asset',
                'reference_id' => $asset->id,
                'description' => $description,
                'lines' => $lines,
            ]);

            if (bccomp($catchUp['amount'], '0', 4) > 0) {
                $this->record($asset, AssetTransaction::TYPE_DEPRECIATION, $catchUp['until'], $catchUp['amount'], $journal->id, null, ['months' => $catchUp['months']]);
            }

            $asset->update(['status' => FixedAsset::STATUS_DISPOSED, 'disposed_on' => $disposalDate->toDateString(), 'disposal_proceeds' => $proceedsAmount->amount]);
            $this->record($asset, AssetTransaction::TYPE_DISPOSAL, $disposalDate->toDateString(), $cost->amount, $journal->id, $notes, [
                'accumulated_depreciation' => $accumulated->amount,
                'proceeds' => $proceedsAmount->amount,
                'gain' => $gain->amount,
            ]);

            return $asset->fresh();
        });
    }

    /**
     * Delete an asset registered by mistake, while nothing but its acquisition has been recorded.
     */
    public function delete(FixedAsset $asset): void
    {
        if ($asset->transactions()->whereNotIn('type', [AssetTransaction::TYPE_ACQUISITION, AssetTransaction::TYPE_OPENING_DEPRECIATION, AssetTransaction::TYPE_TRANSFER])->exists()
            || $asset->transactions()->whereNotNull('journal_id')->exists()) {
            throw new InvalidAccountingTransactionException(__('This asset has postings. Dispose of it instead of deleting it.'));
        }

        DB::transaction(function () use ($asset) {
            $this->audit->logDelete('Assets', 'FixedAsset', $asset->id, $asset->toArray());
            $asset->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function descriptiveAttributes(array $data): array
    {
        return collect(['name', 'description', 'branch_id', 'department_id', 'location', 'custodian', 'serial_number', 'tag', 'supplier_id'])
            ->filter(fn (string $field) => array_key_exists($field, $data))
            ->mapWithKeys(fn (string $field) => [$field => $data[$field] === '' ? null : $data[$field]])
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    protected function record(FixedAsset $asset, string $type, string $date, string $amount, ?int $journalId, ?string $notes, ?array $details = null): AssetTransaction
    {
        return AssetTransaction::create([
            'company_id' => $asset->company_id,
            'fixed_asset_id' => $asset->id,
            'type' => $type,
            'transaction_date' => $date,
            'amount' => $amount,
            'journal_id' => $journalId,
            'notes' => $notes,
            'details' => $details,
            'created_by' => Auth::id(),
        ]);
    }

    protected function requireInUse(FixedAsset $asset): void
    {
        if ($asset->status === FixedAsset::STATUS_DISPOSED) {
            throw new InvalidAccountingTransactionException(__('This asset has already been disposed of.'));
        }
    }

    protected function functionalCurrency(): string
    {
        return $this->companyContext->getBaseCurrency()?->code ?? 'XXX';
    }
}
