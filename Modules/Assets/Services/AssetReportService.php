<?php

namespace Modules\Assets\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Assets\Models\AssetCategory;
use Modules\Assets\Models\AssetTransaction;
use Modules\Assets\Models\FixedAsset;

/**
 * Fixed asset reports built from the asset transactions, so they can be run for any past date: the register,
 * the movement schedule by category (the IAS 16.73 reconciliation) and a depreciation forecast.
 */
class AssetReportService
{
    /**
     * Transaction types that add to accumulated depreciation and impairment.
     */
    public const DEPRECIATION_TYPES = [AssetTransaction::TYPE_OPENING_DEPRECIATION, AssetTransaction::TYPE_DEPRECIATION, AssetTransaction::TYPE_IMPAIRMENT];

    /**
     * Assets held at the end of the day: cost, accumulated depreciation and carrying amount.
     *
     * @return Collection<int, array{asset: FixedAsset, cost: string, accumulated: string, book_value: string}>
     */
    public function register(CarbonImmutable $asOf, ?int $categoryId = null): Collection
    {
        $accumulated = AssetTransaction::whereIn('type', self::DEPRECIATION_TYPES)
            ->whereDate('transaction_date', '<=', $asOf->toDateString())
            ->selectRaw('fixed_asset_id, SUM(amount) AS accumulated_amount')
            ->groupBy('fixed_asset_id')
            ->pluck('accumulated_amount', 'fixed_asset_id');

        return FixedAsset::with(['category', 'branch', 'department'])
            ->whereDate('acquisition_date', '<=', $asOf->toDateString())
            ->where(fn ($query) => $query->whereNull('disposed_on')->orWhereDate('disposed_on', '>', $asOf->toDateString()))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('asset_number')
            ->get()
            ->map(function (FixedAsset $asset) use ($accumulated) {
                $depreciation = bcadd((string) ($accumulated[$asset->id] ?? '0'), '0', 4);

                return ['asset' => $asset, 'cost' => (string) $asset->cost, 'accumulated' => $depreciation, 'book_value' => bcsub((string) $asset->cost, $depreciation, 4)];
            });
    }

    /**
     * Opening balance, movements and closing balance of cost and of accumulated depreciation per category.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function movements(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $transactions = AssetTransaction::with('asset')
            ->whereDate('transaction_date', '<=', $to->toDateString())
            ->where('type', '!=', AssetTransaction::TYPE_TRANSFER)
            ->get();

        return AssetCategory::orderBy('code')->get()
            ->map(function (AssetCategory $category) use ($transactions, $from) {
                $row = array_fill_keys(['cost_opening', 'additions', 'disposals', 'depreciation_opening', 'depreciation', 'impairment', 'depreciation_disposed'], '0');

                foreach ($transactions->filter(fn (AssetTransaction $transaction) => $transaction->asset?->category_id === $category->id) as $transaction) {
                    $before = $transaction->transaction_date->lt($from);
                    $amount = (string) $transaction->amount;

                    match ($transaction->type) {
                        AssetTransaction::TYPE_ACQUISITION => $before ? $row['cost_opening'] = bcadd($row['cost_opening'], $amount, 4) : $row['additions'] = bcadd($row['additions'], $amount, 4),
                        AssetTransaction::TYPE_DISPOSAL => $before
                            ? [$row['cost_opening'] = bcsub($row['cost_opening'], $amount, 4), $row['depreciation_opening'] = bcsub($row['depreciation_opening'], (string) ($transaction->details['accumulated_depreciation'] ?? '0'), 4)]
                            : [$row['disposals'] = bcadd($row['disposals'], $amount, 4), $row['depreciation_disposed'] = bcadd($row['depreciation_disposed'], (string) ($transaction->details['accumulated_depreciation'] ?? '0'), 4)],
                        AssetTransaction::TYPE_IMPAIRMENT => $before ? $row['depreciation_opening'] = bcadd($row['depreciation_opening'], $amount, 4) : $row['impairment'] = bcadd($row['impairment'], $amount, 4),
                        default => $before ? $row['depreciation_opening'] = bcadd($row['depreciation_opening'], $amount, 4) : $row['depreciation'] = bcadd($row['depreciation'], $amount, 4),
                    };
                }

                $row['cost_closing'] = bcsub(bcadd($row['cost_opening'], $row['additions'], 4), $row['disposals'], 4);
                $row['depreciation_closing'] = bcsub(bcadd(bcadd($row['depreciation_opening'], $row['depreciation'], 4), $row['impairment'], 4), $row['depreciation_disposed'], 4);
                $row['book_value_opening'] = bcsub($row['cost_opening'], $row['depreciation_opening'], 4);
                $row['book_value_closing'] = bcsub($row['cost_closing'], $row['depreciation_closing'], 4);

                return ['category' => $category, ...$row];
            })
            ->filter(fn (array $row) => collect($row)->except('category')->contains(fn (string $value) => bccomp($value, '0', 4) !== 0))
            ->values();
    }

    /**
     * Depreciation each asset in use will be charged in each of the coming months.
     *
     * @return array{months: list<string>, rows: Collection<int, array{asset: FixedAsset, charges: array<string, string>, total: string}>}
     */
    public function forecast(CarbonImmutable $from, int $monthCount = 12): array
    {
        $months = collect(range(0, $monthCount - 1))->map(fn (int $offset) => $from->startOfMonth()->addMonths($offset)->endOfMonth()->startOfDay());
        $depreciation = app(DepreciationService::class);

        $rows = FixedAsset::with('category')->where('status', FixedAsset::STATUS_ACTIVE)->orderBy('asset_number')->get()
            ->map(function (FixedAsset $asset) use ($months, $depreciation) {
                $simulated = $asset->replicate()->forceFill(['id' => $asset->id]);
                $charges = [];

                foreach ($months as $month) {
                    $charges[$month->format('Y-m')] = $depreciation->chargeThrough($simulated, $month, persist: false)['amount'];
                }

                return ['asset' => $asset, 'charges' => $charges, 'total' => array_reduce($charges, fn (string $sum, string $charge) => bcadd($sum, $charge, 4), '0')];
            })
            ->filter(fn (array $row) => bccomp($row['total'], '0', 4) > 0)
            ->values();

        return ['months' => $months->map(fn (CarbonImmutable $month) => $month->format('Y-m'))->all(), 'rows' => $rows];
    }
}
