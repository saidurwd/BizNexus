<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\ProductCategory;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\Warehouse;

/**
 * Stock on hand, the stock ledger (movements) and the inventory valuation report.
 */
class StockController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'warehouse' => ['nullable', 'integer'],
            'category' => ['nullable', 'integer'],
            'reorder' => ['nullable', 'boolean'],
        ]);
        $term = isset($filters['q']) ? '%'.addcslashes($filters['q'], '%_\\').'%' : null;
        $warehouses = Warehouse::orderByDesc('is_default')->orderBy('code')->get();

        $products = Product::with(['unit', 'category'])
            ->where('type', Product::TYPE_STOCK)
            ->when($term, fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('sku', 'like', $term)->orWhere('name', 'like', $term)->orWhere('barcode', 'like', $term)))
            ->when($filters['category'] ?? null, fn (Builder $query, int $category) => $query->where('category_id', $category))
            ->when($filters['warehouse'] ?? null, fn (Builder $query, int $warehouse) => $query->whereHas('stockBalances', fn (Builder $balance) => $balance->where('warehouse_id', $warehouse)->where('quantity', '!=', 0)))
            ->when($filters['reorder'] ?? false, fn (Builder $query) => $query->whereNotNull('reorder_level')->whereColumn('stock_quantity', '<=', 'reorder_level'))
            ->orderBy('sku')
            ->paginate(50)
            ->withQueryString();

        $balances = StockBalance::whereIn('product_id', $products->pluck('id'))->get()->groupBy('product_id')
            ->map(fn ($rows) => $rows->pluck('quantity', 'warehouse_id'));

        return view('inventory.stock.index', [
            'products' => $products,
            'balances' => $balances,
            'warehouses' => $warehouses,
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'totalValue' => Product::where('type', Product::TYPE_STOCK)->sum('stock_value'),
        ]);
    }

    public function movements(Request $request): View
    {
        $filters = $request->validate([
            'product' => ['nullable', 'integer'],
            'warehouse' => ['nullable', 'integer'],
            'source' => ['nullable', Rule::in(array_keys(StockMove::sourceLabels()))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $moves = StockMove::with(['product.unit', 'warehouse'])
            ->when($filters['product'] ?? null, fn (Builder $query, int $product) => $query->where('product_id', $product))
            ->when($filters['warehouse'] ?? null, fn (Builder $query, int $warehouse) => $query->where('warehouse_id', $warehouse))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->where('source_type', $source))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('move_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('move_date', '<=', $to))
            ->orderByDesc('move_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('inventory.stock.movements', [
            'moves' => $moves,
            'filters' => $filters,
            'products' => Product::where('type', Product::TYPE_STOCK)->orderBy('sku')->get(['id', 'sku', 'name']),
            'warehouses' => Warehouse::orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Quantity and value of stock at the end of a date, from the movements up to that date.
     */
    public function valuation(Request $request): View
    {
        $validated = $request->validate(['date' => ['nullable', 'date'], 'category' => ['nullable', 'integer']]);
        $date = $validated['date'] ?? app(CompanyContextService::class)->today()->toDateString();

        $totals = StockMove::whereDate('move_date', '<=', $date)
            ->selectRaw('product_id, SUM(quantity) AS closing_quantity, SUM(value) AS closing_value')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $rows = Product::with(['unit', 'category'])
            ->whereKey($totals->keys())
            ->when($validated['category'] ?? null, fn (Builder $query, int $category) => $query->where('category_id', $category))
            ->orderBy('sku')
            ->get()
            ->map(fn (Product $product) => [
                'product' => $product,
                'quantity' => (string) $totals[$product->id]->closing_quantity,
                'value' => (string) $totals[$product->id]->closing_value,
            ])
            ->filter(fn (array $row) => bccomp($row['quantity'], '0', 4) !== 0 || bccomp($row['value'], '0', 4) !== 0)
            ->values();

        return view('inventory.stock.valuation', [
            'rows' => $rows,
            'date' => $date,
            'category' => $validated['category'] ?? null,
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
            'totalValue' => $rows->reduce(fn (string $sum, array $row) => bcadd($sum, $row['value'], 4), '0'),
        ]);
    }
}
