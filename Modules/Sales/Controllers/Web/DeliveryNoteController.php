<?php

namespace Modules\Sales\Controllers\Web;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Models\DeliveryNote;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\DeliveryService;
use Symfony\Component\HttpFoundation\Response;

class DeliveryNoteController extends Controller
{
    use FiltersDocumentLists;

    public function index(Request $request): View
    {
        $query = DeliveryNote::with(['customer', 'salesOrder', 'warehouse']);
        $filters = $this->applyListFilters($query, $request, 'delivery_date', ['delivery_number', 'tracking_number'], 'customer');

        return view('sales.deliveries.index', [
            'deliveries' => $query->orderByDesc('delivery_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(int $id): View|RedirectResponse
    {
        $order = SalesOrder::with(['customer', 'lines.product.unit'])->findOrFail($id);

        if (! $order->canDeliver()) {
            return redirect()->route('sales.orders.show', $id)->with('error', __('Nothing on this order is waiting to be delivered.'));
        }

        return view('sales.deliveries.create', [
            'order' => $order,
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('code')->get(),
            'available' => StockBalance::whereIn('product_id', $order->lines->pluck('product_id'))->get()->groupBy('warehouse_id')->map(fn ($rows) => $rows->pluck('quantity', 'product_id')),
        ]);
    }

    public function store(Request $request, int $id, DeliveryService $service): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_date' => ['required', 'date'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $this->getActiveCompanyId())->where('status', 'active')],
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array'],
            'lines.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $delivery = $service->deliver(SalesOrder::findOrFail($id), $validated);

        return redirect()->route('sales.deliveries.show', $delivery->id)->with('success', __('Delivery :number posted.', ['number' => $delivery->delivery_number]));
    }

    public function show(int $id): View
    {
        $delivery = DeliveryNote::with(['customer', 'salesOrder', 'warehouse', 'journal', 'lines.product.unit', 'lines.orderLine', 'createdBy'])->findOrFail($id);

        return view('sales.deliveries.show', compact('delivery'));
    }

    /**
     * Printable delivery note (packing slip): what was shipped, without prices.
     */
    public function pdf(int $id): Response
    {
        $delivery = DeliveryNote::with(['company', 'customer', 'salesOrder', 'warehouse', 'lines.product.unit', 'lines.orderLine'])->findOrFail($id);

        $pdf = Pdf::loadView('sales.pdf.delivery-note', ['delivery' => $delivery, 'company' => $delivery->company])->setPaper('a4');

        return $pdf->stream(str_replace(['/', '\\'], '-', $delivery->delivery_number).'.pdf');
    }
}
