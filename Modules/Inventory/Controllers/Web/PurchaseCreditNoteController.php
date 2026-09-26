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
 * Recording the supplier's credit note for goods returned after they were invoiced.
 */
class PurchaseCreditNoteController extends Controller
{
    public function create(int $id): View|RedirectResponse
    {
        $order = PurchaseOrder::with(['supplier', 'currency', 'lines.product.unit', 'lines.tax'])->findOrFail($id);

        if (! $order->hasCreditDue()) {
            return redirect()->route('inventory.purchase-orders.show', $id)->with('error', __('No credit note is due on this order.'));
        }

        return view('inventory.purchase-orders.credit-note', [
            'order' => $order,
            'taxes' => Tax::where('status', 'active')->orderBy('tax_code')->get(['id', 'tax_code', 'tax_name']),
        ]);
    }

    public function store(Request $request, int $id, PurchaseInvoiceMatcher $matcher): RedirectResponse
    {
        $order = PurchaseOrder::findOrFail($id);
        $companyId = $this->getActiveCompanyId();
        $validated = $request->validate([
            'credit_note_number' => ['required', 'string', 'max:50'],
            'credit_note_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
        ]);

        $creditNote = $matcher->createCreditNote($order, $validated);

        return redirect()->route('finance.supplier-credit-notes.show', $creditNote->id)->with('success', __('Supplier credit note :number recorded against :order. Submit it for approval to post it.', ['number' => $creditNote->credit_note_number, 'order' => $order->order_number]));
    }
}
