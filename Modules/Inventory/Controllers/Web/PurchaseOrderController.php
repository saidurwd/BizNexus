<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Models\Currency;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\PurchaseOrderService;

class PurchaseOrderController extends Controller
{
    use FiltersDocumentLists;

    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService,
        protected PurchaseOrderService $orders,
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request): View
    {
        $query = PurchaseOrder::with(['supplier', 'currency', 'warehouse']);
        $filters = $this->applyListFilters($query, $request, 'order_date', ['order_number', 'supplier_reference'], 'supplier');

        return view('inventory.purchase-orders.index', [
            'orders' => $query->orderByDesc('order_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $product = $request->integer('product') ? Product::find($request->integer('product')) : null;

        return view('inventory.purchase-orders.create', [
            'order' => null,
            'prefill' => $product ? ['supplier_id' => $product->preferred_supplier_id, 'lines' => [['product_id' => $product->id, 'quantity' => $product->reorder_quantity ?? 1, 'unit_price' => $product->purchase_price, 'tax_id' => $product->purchase_tax_id]]] : [],
            ...$this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $order = $this->orders->create([...$this->validated($request), 'company_id' => $this->getActiveCompanyId(), 'branch_id' => $this->getActiveBranchId()]);

        return redirect()->route('inventory.purchase-orders.show', $order->id)->with('success', __('Purchase order :number saved as a draft.', ['number' => $order->order_number]));
    }

    public function show(int $id): View
    {
        $order = PurchaseOrder::with(['supplier', 'currency', 'warehouse', 'lines.product.unit', 'lines.tax', 'receipts', 'invoices', 'createdBy', 'approvedBy'])->findOrFail($id);

        return view('inventory.purchase-orders.show', compact('order'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $order = PurchaseOrder::with('lines')->findOrFail($id);

        if (! $order->isEditable()) {
            return redirect()->route('inventory.purchase-orders.show', $id)->with('error', __('Only draft or rejected orders can be edited.'));
        }

        return view('inventory.purchase-orders.edit', ['order' => $order, 'prefill' => [], ...$this->formData()]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->orders->update(PurchaseOrder::findOrFail($id), $this->validated($request));

        return redirect()->route('inventory.purchase-orders.show', $id)->with('success', __('Purchase order updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->orders->delete(PurchaseOrder::findOrFail($id));

        return redirect()->route('inventory.purchase-orders.index')->with('success', __('Purchase order deleted.'));
    }

    public function submit(int $id): RedirectResponse
    {
        $this->orders->submit(PurchaseOrder::findOrFail($id));

        return back()->with('success', __('Purchase order submitted for approval.'));
    }

    public function approve(int $id): RedirectResponse
    {
        $this->orders->approve(PurchaseOrder::findOrFail($id));

        return back()->with('success', __('Purchase order approved. It can now be sent to the supplier and received.'));
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->orders->reject(PurchaseOrder::findOrFail($id), $validated['reason'] ?? null);

        return back()->with('success', __('Purchase order rejected.'));
    }

    public function cancel(int $id): RedirectResponse
    {
        $this->orders->cancel(PurchaseOrder::findOrFail($id));

        return back()->with('success', __('Purchase order cancelled.'));
    }

    public function close(int $id): RedirectResponse
    {
        $this->orders->close(PurchaseOrder::findOrFail($id));

        return back()->with('success', __('Purchase order closed.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $companyId = $this->getActiveCompanyId();

        return $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'supplier_reference' => ['nullable', 'string', 'max:100'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)->where('status', 'active')],
            'currency_id' => ['nullable', Rule::exists('currencies', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)->where('status', 'active')],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
        ], [], [
            'lines.*.product_id' => __('product'),
            'lines.*.quantity' => __('quantity'),
            'lines.*.unit_price' => __('unit price'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(['id', 'supplier_code', 'name', 'currency_id']),
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('code')->get(),
            'currencies' => Currency::where('status', 'active')->orderBy('code')->get(),
            'products' => Product::active()->with('unit')->orderBy('sku')->get(['id', 'sku', 'name', 'type', 'unit_id', 'purchase_price', 'purchase_tax_id']),
            'taxes' => Tax::where('status', 'active')->orderBy('tax_code')->get(['id', 'tax_code', 'tax_name']),
        ];
    }
}
