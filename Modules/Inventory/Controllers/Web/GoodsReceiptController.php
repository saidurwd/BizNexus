<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Models\GoodsReceipt;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\GoodsReceiptService;

class GoodsReceiptController extends Controller
{
    use FiltersDocumentLists;

    public function index(Request $request): View
    {
        $query = GoodsReceipt::with(['supplier', 'purchaseOrder', 'warehouse']);
        $filters = $this->applyListFilters($query, $request, 'receipt_date', ['receipt_number', 'delivery_note'], 'supplier');

        return view('inventory.goods-receipts.index', [
            'receipts' => $query->orderByDesc('receipt_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(int $id): View|RedirectResponse
    {
        $order = PurchaseOrder::with(['supplier', 'lines.product.unit'])->findOrFail($id);

        if (! $order->canReceive()) {
            return redirect()->route('inventory.purchase-orders.show', $id)->with('error', __('Goods can only be received on approved purchase orders that are still open.'));
        }

        return view('inventory.goods-receipts.create', [
            'order' => $order,
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, int $id, GoodsReceiptService $service): RedirectResponse
    {
        $order = PurchaseOrder::findOrFail($id);
        $validated = $request->validate([
            'receipt_date' => ['required', 'date'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $this->getActiveCompanyId())->where('status', 'active')],
            'delivery_note' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array'],
            'lines.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $receipt = $service->receive($order, $validated);

        return redirect()->route('inventory.goods-receipts.show', $receipt->id)->with('success', __('Goods receipt :number posted.', ['number' => $receipt->receipt_number]));
    }

    public function show(int $id): View
    {
        $receipt = GoodsReceipt::with(['supplier', 'purchaseOrder', 'warehouse', 'currency', 'journal', 'lines.product.unit', 'createdBy'])->findOrFail($id);

        return view('inventory.goods-receipts.show', compact('receipt'));
    }
}
