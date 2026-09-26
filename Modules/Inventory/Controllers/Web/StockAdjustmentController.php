<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockAdjustment;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\StockAdjustmentService;

class StockAdjustmentController extends Controller
{
    use FiltersDocumentLists;

    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService,
        protected StockAdjustmentService $adjustments,
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request): View
    {
        $query = StockAdjustment::with(['warehouse', 'createdBy'])->withCount('lines');
        $filters = $this->applyListFilters($query, $request, 'adjustment_date', ['adjustment_number', 'notes']);

        return view('inventory.adjustments.index', [
            'adjustments' => $query->orderByDesc('adjustment_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $reason = in_array($request->query('reason'), StockAdjustment::REASONS, true) ? $request->query('reason') : StockAdjustment::REASON_OTHER;
        $warehouseId = $request->integer('warehouse') ?: Warehouse::defaultId();
        $lines = [];

        if ($reason === StockAdjustment::REASON_COUNT && $warehouseId) {
            // A count sheet: every product with stock in the warehouse, to fill in what was counted.
            $lines = StockBalance::with('product')->where('warehouse_id', $warehouseId)->where('quantity', '!=', 0)->get()
                ->sortBy('product.sku')
                ->map(fn (StockBalance $balance) => ['product_id' => $balance->product_id, 'counted_quantity' => null, 'booked' => (string) $balance->quantity])
                ->values()
                ->all();
        }

        return view('inventory.adjustments.create', [
            'adjustment' => null,
            'reason' => $reason,
            'warehouseId' => $warehouseId,
            'lines' => $lines,
            ...$this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $adjustment = $this->adjustments->create([...$this->validated($request), 'company_id' => $this->getActiveCompanyId()]);

        return redirect()->route('inventory.adjustments.show', $adjustment->id)->with('success', __('Stock adjustment :number saved as a draft. Post it to update stock.', ['number' => $adjustment->adjustment_number]));
    }

    public function show(int $id): View
    {
        $adjustment = StockAdjustment::with(['warehouse', 'journal', 'lines.product.unit', 'createdBy', 'postedBy'])->findOrFail($id);

        return view('inventory.adjustments.show', compact('adjustment'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $adjustment = StockAdjustment::with('lines')->findOrFail($id);

        if (! $adjustment->isDraft()) {
            return redirect()->route('inventory.adjustments.show', $id)->with('error', __('Only draft adjustments can be changed or posted.'));
        }

        $isCount = $adjustment->reason === StockAdjustment::REASON_COUNT;

        return view('inventory.adjustments.edit', [
            'adjustment' => $adjustment,
            'reason' => $adjustment->reason,
            'warehouseId' => $adjustment->warehouse_id,
            'lines' => $adjustment->lines->map(fn ($line) => [
                'product_id' => $line->product_id,
                'quantity' => (string) $line->quantity,
                'counted_quantity' => $isCount ? bcadd((string) $line->system_quantity, (string) $line->quantity, 4) : null,
                'booked' => $isCount ? (string) $line->system_quantity : null,
                'unit_cost' => $line->unit_cost,
            ])->all(),
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->adjustments->update(StockAdjustment::findOrFail($id), $this->validated($request));

        return redirect()->route('inventory.adjustments.show', $id)->with('success', __('Stock adjustment updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->adjustments->delete(StockAdjustment::findOrFail($id));

        return redirect()->route('inventory.adjustments.index')->with('success', __('Stock adjustment deleted.'));
    }

    public function post(int $id): RedirectResponse
    {
        $adjustment = $this->adjustments->post(StockAdjustment::findOrFail($id));

        return back()->with('success', __('Stock adjustment :number posted.', ['number' => $adjustment->adjustment_number]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $companyId = $this->getActiveCompanyId();
        $isCount = $request->input('reason') === StockAdjustment::REASON_COUNT;

        return $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)->where('status', 'active')],
            'adjustment_date' => ['required', 'date'],
            'reason' => ['required', Rule::in(StockAdjustment::REASONS)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)->where('type', Product::TYPE_STOCK)],
            'lines.*.quantity' => [$isCount ? 'nullable' : 'required', 'numeric', 'not_in:0'],
            'lines.*.counted_quantity' => [$isCount ? 'required' : 'nullable', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'lines.*.product_id' => __('product'),
            'lines.*.quantity' => __('quantity'),
            'lines.*.counted_quantity' => __('counted quantity'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('code')->get(),
            'products' => Product::active()->where('type', Product::TYPE_STOCK)->with('unit')->orderBy('sku')->get(['id', 'sku', 'name', 'unit_id', 'stock_quantity', 'stock_value']),
        ];
    }
}
