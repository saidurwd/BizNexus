<?php

namespace Modules\Sales\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Finance\Controllers\Controller;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\SalesOrderCostService;

/**
 * Raising the customer invoice for what has been delivered on a sales order.
 */
class SalesOrderInvoiceController extends Controller
{
    public function create(int $id): View|RedirectResponse
    {
        $order = SalesOrder::with(['customer', 'currency', 'lines.product.unit', 'lines.tax'])->findOrFail($id);

        if (! $order->canInvoice()) {
            return redirect()->route('sales.orders.show', $id)->with('error', __('Nothing on this order can be invoiced yet. Deliver the goods first.'));
        }

        return view('sales.orders.invoice', compact('order'));
    }

    public function store(Request $request, int $id, SalesOrderCostService $invoicing): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array'],
            'lines.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = SalesOrder::findOrFail($id);
        $invoice = $invoicing->createInvoice($order, $validated);

        return redirect()->route('finance.customer-invoices.show', $invoice->id)->with('success', __('Invoice :number raised for :order. Submit it for approval to post it.', ['number' => $invoice->invoice_number, 'order' => $order->order_number]));
    }
}
