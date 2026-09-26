<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Services\PurchaseInvoiceMatcher;

/**
 * Recording the supplier's invoice for goods received on a purchase order (three-way match).
 */
class PurchaseInvoiceMatchController extends Controller
{
    public function create(int $id): View|RedirectResponse
    {
        $order = PurchaseOrder::with(['supplier', 'currency', 'lines.product.unit', 'lines.tax'])->findOrFail($id);

        if (! $order->hasUninvoicedReceipts()) {
            return redirect()->route('inventory.purchase-orders.show', $id)->with('error', __('Nothing received on this order is waiting to be invoiced.'));
        }

        return view('inventory.purchase-orders.invoice', [
            'order' => $order,
            'taxes' => Tax::where('status', 'active')->orderBy('tax_code')->get(['id', 'tax_code', 'tax_name']),
        ]);
    }

    public function store(Request $request, int $id, PurchaseInvoiceMatcher $matcher): RedirectResponse
    {
        $order = PurchaseOrder::findOrFail($id);
        $companyId = $this->getActiveCompanyId();
        $validated = $request->validate([
            'invoice_number' => ['required', 'string', 'max:50', Rule::unique('supplier_invoices', 'invoice_number')->where('company_id', $companyId)->where('supplier_id', $order->supplier_id)],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
        ], ['invoice_number.unique' => __('This supplier invoice number is already recorded for this supplier.')]);

        $invoice = $matcher->createInvoice($order, $validated);

        return redirect()->route('finance.supplier-invoices.show', $invoice->id)->with('success', __('Supplier invoice :number recorded against :order. Submit it for approval to post it.', ['number' => $invoice->invoice_number, 'order' => $order->order_number]));
    }
}
