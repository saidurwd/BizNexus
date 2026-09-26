<?php

namespace Modules\Sales\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Contracts\StockReservations;
use Modules\Inventory\Models\StockBalance;
use Modules\Sales\Controllers\Concerns\HandlesSalesLines;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\SalesOrderService;

class SalesOrderController extends Controller
{
    use FiltersDocumentLists, HandlesSalesLines;

    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService,
        protected SalesOrderService $orders,
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request): View
    {
        $query = SalesOrder::with(['customer', 'currency', 'warehouse']);
        $filters = $this->applyListFilters($query, $request, 'order_date', ['order_number', 'customer_reference'], 'customer');

        return view('sales.orders.index', [
            'orders' => $query->orderByDesc('order_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('sales.orders.create', ['order' => null, ...$this->salesFormData()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $order = $this->orders->create([...$this->validated($request), 'company_id' => $this->getActiveCompanyId(), 'branch_id' => $this->getActiveBranchId()]);

        return redirect()->route('sales.orders.show', $order->id)->with('success', __('Sales order :number saved as a draft.', ['number' => $order->order_number]));
    }

    public function show(int $id): View
    {
        $order = SalesOrder::with(['customer', 'currency', 'warehouse', 'quotation', 'lines.product.unit', 'lines.tax', 'deliveries', 'invoices', 'createdBy', 'confirmedBy'])->findOrFail($id);
        $productIds = $order->lines->pluck('product_id')->unique()->values()->all();
        $onHand = StockBalance::where('warehouse_id', $order->warehouse_id)->whereIn('product_id', $productIds)->pluck('quantity', 'product_id');
        $reserved = app(StockReservations::class)->reserved($productIds, $order->warehouse_id, $order->id);
        $available = collect($productIds)->mapWithKeys(fn (int $productId) => [$productId => bcsub((string) ($onHand[$productId] ?? '0'), $reserved[$productId] ?? '0', 4)]);

        return view('sales.orders.show', compact('order', 'available'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $order = SalesOrder::with('lines')->findOrFail($id);

        if ($order->status !== SalesOrder::STATUS_DRAFT) {
            return redirect()->route('sales.orders.show', $id)->with('error', __('Only draft orders can be edited.'));
        }

        return view('sales.orders.edit', ['order' => $order, ...$this->salesFormData()]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->orders->update(SalesOrder::findOrFail($id), $this->validated($request));

        return redirect()->route('sales.orders.show', $id)->with('success', __('Sales order updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->orders->delete(SalesOrder::findOrFail($id));

        return redirect()->route('sales.orders.index')->with('success', __('Sales order deleted.'));
    }

    public function confirm(int $id): RedirectResponse
    {
        $this->orders->confirm(SalesOrder::findOrFail($id));

        return back()->with('success', __('Sales order confirmed. It can now be delivered.'));
    }

    public function cancel(int $id): RedirectResponse
    {
        $this->orders->cancel(SalesOrder::findOrFail($id));

        return back()->with('success', __('Sales order cancelled.'));
    }

    public function close(int $id): RedirectResponse
    {
        $this->orders->close(SalesOrder::findOrFail($id));

        return back()->with('success', __('Sales order closed.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $companyId = $this->getActiveCompanyId();

        return $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'customer_reference' => ['nullable', 'string', 'max:100'],
            'order_date' => ['required', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)->where('status', 'active')],
            'currency_id' => ['nullable', Rule::exists('currencies', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
            ...$this->salesLineRules($companyId),
        ], [], $this->salesLineAttributes());
    }
}
