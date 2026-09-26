<?php

namespace Modules\Inventory\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockMove;

/**
 * Figures for the inventory dashboard, in the functional currency: stock value, ageing by last receipt,
 * slow movers and turnover.
 */
class InventoryDashboardService
{
    /**
     * Stock movements that take goods to customers (they count as sales for turnover and slow movers).
     */
    public const OUTBOUND_SOURCES = [StockMove::SOURCE_CUSTOMER_INVOICE, StockMove::SOURCE_DELIVERY];

    /**
     * Age buckets in days: label => [from, to].
     *
     * @var array<string, array{0: int, 1: int|null}>
     */
    public const AGE_BUCKETS = ['0–30' => [0, 30], '31–90' => [31, 90], '91–180' => [91, 180], '180+' => [181, null]];

    public function __construct(protected ReorderService $reorder) {}

    /**
     * @return array{stock_value: string, items_in_stock: int, below_reorder: int, slow_value: string, cost_of_sales: string, turnover: string|null, days_of_cover: string|null}
     */
    public function summary(CarbonImmutable $today): array
    {
        $stockValue = (string) Product::where('type', Product::TYPE_STOCK)->sum('stock_value');
        $costOfSales = $this->outboundValue($today->subYear(), $today);
        $slow = $this->slowMovers($today, 90, PHP_INT_MAX);

        return [
            'stock_value' => bcadd($stockValue, '0', 4),
            'items_in_stock' => Product::where('type', Product::TYPE_STOCK)->where('stock_quantity', '>', 0)->count(),
            'below_reorder' => $this->reorder->suggestions()->count(),
            'slow_value' => $slow->reduce(fn (string $sum, array $row) => bcadd($sum, (string) $row['product']->stock_value, 4), '0'),
            'cost_of_sales' => $costOfSales,
            'turnover' => bccomp($stockValue, '0', 4) > 0 ? bcdiv($costOfSales, $stockValue, 2) : null,
            'days_of_cover' => bccomp($costOfSales, '0', 4) > 0 ? bcdiv(bcmul($stockValue, '365', 8), $costOfSales, 0) : null,
        ];
    }

    /**
     * Stock value by how long ago each product was last received (a proxy for how old the stock is).
     *
     * @return array<string, string>
     */
    public function ageing(CarbonImmutable $today): array
    {
        $lastReceipts = StockMove::where('quantity', '>', 0)
            ->where('source_type', '!=', StockMove::SOURCE_TRANSFER)
            ->selectRaw('product_id, MAX(move_date) AS last_received_on')
            ->groupBy('product_id')
            ->pluck('last_received_on', 'product_id');

        $buckets = array_fill_keys(array_keys(self::AGE_BUCKETS), '0');

        foreach (Product::where('type', Product::TYPE_STOCK)->where('stock_quantity', '>', 0)->get(['id', 'stock_value']) as $product) {
            $age = isset($lastReceipts[$product->id]) ? (int) CarbonImmutable::parse($lastReceipts[$product->id])->diffInDays($today) : PHP_INT_MAX;

            foreach (self::AGE_BUCKETS as $label => [$from, $to]) {
                if ($age >= $from && ($to === null || $age <= $to)) {
                    $buckets[$label] = bcadd($buckets[$label], (string) $product->stock_value, 4);
                    break;
                }
            }
        }

        return $buckets;
    }

    /**
     * Products in stock that no customer has taken for the given number of days, most valuable first.
     *
     * @return Collection<int, array{product: Product, last_sold_on: CarbonImmutable|null}>
     */
    public function slowMovers(CarbonImmutable $today, int $days = 90, int $limit = 10): Collection
    {
        $lastSales = StockMove::whereIn('source_type', self::OUTBOUND_SOURCES)
            ->selectRaw('product_id, MAX(move_date) AS last_sold_on')
            ->groupBy('product_id')
            ->pluck('last_sold_on', 'product_id');
        $cutoff = $today->subDays($days);

        return Product::with('unit')->where('type', Product::TYPE_STOCK)->where('stock_quantity', '>', 0)
            ->orderByDesc('stock_value')
            ->get()
            ->map(fn (Product $product) => ['product' => $product, 'last_sold_on' => isset($lastSales[$product->id]) ? CarbonImmutable::parse($lastSales[$product->id]) : null])
            ->filter(fn (array $row) => $row['last_sold_on'] === null || $row['last_sold_on']->lt($cutoff))
            ->take($limit)
            ->values();
    }

    /**
     * Products holding the most stock value.
     *
     * @return Collection<int, Product>
     */
    public function topByValue(int $limit = 8): Collection
    {
        return Product::with('unit')->where('type', Product::TYPE_STOCK)->where('stock_value', '>', 0)->orderByDesc('stock_value')->limit($limit)->get();
    }

    /**
     * Cost of goods that left stock to customers in the period.
     */
    protected function outboundValue(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $value = StockMove::whereIn('source_type', self::OUTBOUND_SOURCES)
            ->whereDate('move_date', '>=', $from->toDateString())
            ->whereDate('move_date', '<=', $to->toDateString())
            ->sum('value');

        return bcmul(bcadd((string) $value, '0', 4), '-1', 4);
    }
}
