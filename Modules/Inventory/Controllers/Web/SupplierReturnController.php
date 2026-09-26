<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\SupplierReturn;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\SupplierReturnService;

class SupplierReturnController extends Controller
{
    use FiltersDocumentLists;

    public function index(Request $request): View
    {
        $query = SupplierReturn::with(['supplier', 'purchaseOrder', 'warehouse']);
        $filters = $this->applyListFilters($query, $request, 'return_date', ['return_number', 'reason'], 'supplier');

        return view('inventory.supplier-returns.index', [
            'returns' => $query->orderByDesc('return_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(int $id): View|RedirectResponse
    {
        $order = PurchaseOrder::with(['supplier', 'lines.product.unit'])->findOrFail($id);

        if (! $order->canReturn()) {
            return redirect()->route('inventory.purchase-orders.show', $id)->with('error', __('Nothing received on this order can be returned.'));
        }

        return view('inventory.supplier-returns.create', [
            'order' => $order,
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, int $id, SupplierReturnService $service): RedirectResponse
    {
        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $this->getActiveCompanyId())->where('status', 'active')],
            'reason' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array'],
            'lines.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $return = $service->return(PurchaseOrder::findOrFail($id), $validated);

        return redirect()->route('inventory.supplier-returns.show', $return->id)->with('success', __('Return :number posted.', ['number' => $return->return_number]));
    }

    public function show(int $id): View
    {
        $return = SupplierReturn::with(['supplier', 'purchaseOrder', 'warehouse', 'journal', 'lines.product.unit', 'createdBy'])->findOrFail($id);

        return view('inventory.supplier-returns.show', compact('return'));
    }
}
