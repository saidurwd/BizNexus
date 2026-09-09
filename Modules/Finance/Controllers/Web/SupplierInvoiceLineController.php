<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierInvoiceLine;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Tax;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class SupplierInvoiceLineController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function store(Request $request, int $invoiceId): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.update');

        $invoice = SupplierInvoice::findOrFail($invoiceId);

        if (!$invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can have lines added.');
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'required|string|max:500',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $line = new SupplierInvoiceLine($validated);
        $line->supplier_invoice_id = $invoiceId;
        $line->calculateTotals();
        $line->save();

        $this->recalculateInvoiceTotals($invoice);

        return back()->with('success', 'Invoice line added successfully.');
    }

    public function update(Request $request, int $invoiceId, int $lineId): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.update');

        $invoice = SupplierInvoice::findOrFail($invoiceId);

        if (!$invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can have lines edited.');
        }

        $line = SupplierInvoiceLine::where('supplier_invoice_id', $invoiceId)->findOrFail($lineId);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'required|string|max:500',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $line->update($validated);
        $line->calculateTotals();
        $line->save();

        $this->recalculateInvoiceTotals($invoice);

        return back()->with('success', 'Invoice line updated successfully.');
    }

    public function destroy(int $invoiceId, int $lineId): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.update');

        $invoice = SupplierInvoice::findOrFail($invoiceId);

        if (!$invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can have lines removed.');
        }

        $line = SupplierInvoiceLine::where('supplier_invoice_id', $invoiceId)->findOrFail($lineId);
        $line->delete();

        $this->recalculateInvoiceTotals($invoice);

        return back()->with('success', 'Invoice line removed successfully.');
    }

    protected function recalculateInvoiceTotals(SupplierInvoice $invoice): void
    {
        $lines = $invoice->lines;

        $subtotal = 0;
        $taxAmount = 0;

        foreach ($lines as $line) {
            $subtotal = bcadd($subtotal, $line->subtotal ?? 0, 4);
            $taxAmount = bcadd($taxAmount, $line->tax_amount ?? 0, 4);
        }

        $invoice->subtotal = $subtotal;
        $invoice->tax_amount = $taxAmount;
        $invoice->total_amount = bcadd($subtotal, $taxAmount, 4);
        $invoice->outstanding_amount = $invoice->total_amount;
        $invoice->save();
    }
}
