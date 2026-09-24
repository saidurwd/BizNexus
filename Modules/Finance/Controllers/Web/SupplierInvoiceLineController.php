<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Services\DocumentTaxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierInvoiceLine;

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

        $invoice = SupplierInvoice::findOrFail($invoiceId);

        if (! $invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can have lines added.');
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'required|string|max:500',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'supply_type' => 'nullable|in:goods,services',
            'is_reverse_charge' => 'nullable|boolean',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $line = new SupplierInvoiceLine($validated);
        $line->supplier_invoice_id = $invoiceId;
        $line->save();

        $this->recalculateInvoiceTotals($invoice);

        return back()->with('success', 'Invoice line added successfully.');
    }

    public function update(Request $request, int $invoiceId, int $lineId): RedirectResponse
    {

        $invoice = SupplierInvoice::findOrFail($invoiceId);

        if (! $invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can have lines edited.');
        }

        $line = SupplierInvoiceLine::where('supplier_invoice_id', $invoiceId)->findOrFail($lineId);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'required|string|max:500',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_price' => 'required|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'supply_type' => 'nullable|in:goods,services',
            'is_reverse_charge' => 'nullable|boolean',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $line->update($validated);

        $this->recalculateInvoiceTotals($invoice);

        return back()->with('success', 'Invoice line updated successfully.');
    }

    public function destroy(int $invoiceId, int $lineId): RedirectResponse
    {

        $invoice = SupplierInvoice::findOrFail($invoiceId);

        if (! $invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can have lines removed.');
        }

        $line = SupplierInvoiceLine::where('supplier_invoice_id', $invoiceId)->findOrFail($lineId);
        $line->delete();

        $this->recalculateInvoiceTotals($invoice);

        return back()->with('success', 'Invoice line removed successfully.');
    }

    protected function recalculateInvoiceTotals(SupplierInvoice $invoice): void
    {
        app(DocumentTaxService::class)->recalculate($invoice);
    }
}
