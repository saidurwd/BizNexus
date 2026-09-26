<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockTransfer;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\StockTransferService;

class StockTransferController extends Controller
{
    use FiltersDocumentLists;

    public function index(Request $request): View
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'createdBy'])->withCount('lines');
        $filters = $this->applyListFilters($query, $request, 'transfer_date', ['transfer_number', 'notes']);

        return view('inventory.transfers.index', [
            'transfers' => $query->orderByDesc('transfer_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('inventory.transfers.create', [
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('code')->get(),
            'products' => Product::active()->where('type', Product::TYPE_STOCK)->with('unit')->orderBy('sku')->get(['id', 'sku', 'name', 'unit_id']),
        ]);
    }

    public function store(Request $request, StockTransferService $service): RedirectResponse
    {
        $companyId = $this->getActiveCompanyId();
        $warehouse = Rule::exists('warehouses', 'id')->where('company_id', $companyId)->where('status', 'active');
        $validated = $request->validate([
            'from_warehouse_id' => ['required', $warehouse],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', $warehouse],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)->where('type', Product::TYPE_STOCK)],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ], [], [
            'lines.*.product_id' => __('product'),
            'lines.*.quantity' => __('quantity'),
        ]);

        $transfer = $service->transfer([...$validated, 'company_id' => $companyId]);

        return redirect()->route('inventory.transfers.show', $transfer->id)->with('success', __('Stock transfer :number posted.', ['number' => $transfer->transfer_number]));
    }

    public function show(int $id): View
    {
        $transfer = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'lines.product.unit', 'createdBy'])->findOrFail($id);

        return view('inventory.transfers.show', compact('transfer'));
    }
}
