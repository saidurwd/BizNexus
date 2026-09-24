<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\Tax;
use Modules\Finance\Services\CustomerInvoiceService;

class CustomerInvoiceController extends Controller
{
    public function __construct(
        protected CustomerInvoiceService $invoiceService,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {

        $invoices = CustomerInvoice::with('customer')
            ->orderBy('invoice_date', 'desc')
            ->paginate(20);

        return view('finance.customer-invoices.index', compact('invoices'));
    }

    public function create()
    {

        $customers = Customer::where('status', 'active')->get();
        $taxes = Tax::where('status', 'active')->get();

        return view('finance.customer-invoices.create', compact('customers', 'taxes'));
    }

    public function store(Request $request): RedirectResponse
    {

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50|unique:customer_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'customer_id' => 'nullable|exists:customers,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'subtotal' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();
        $validated['branch_id'] = $this->getActiveBranchId();

        $invoice = $this->invoiceService->createInvoice($validated);

        return redirect()
            ->route('finance.customer-invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully');
    }

    public function show(string $id)
    {

        $invoice = CustomerInvoice::with('customer')->findOrFail($id);

        return view('finance.customer-invoices.show', compact('invoice'));
    }

    public function edit(string $id)
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return redirect()->route('finance.customer-invoices.show', $id)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $customers = Customer::where('status', 'active')->get();
        $taxes = Tax::where('status', 'active')->get();

        return view('finance.customer-invoices.edit', compact('invoice', 'customers', 'taxes'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return redirect()->route('finance.customer-invoices.show', $id)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50|unique:customer_invoices,invoice_number,'.$id,
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'customer_id' => 'nullable|exists:customers,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'subtotal' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $invoice->update($validated);

        return redirect()->route('finance.customer-invoices.show', $id)
            ->with('success', 'Invoice updated successfully');
    }

    public function destroy(string $id): RedirectResponse
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return redirect()->route('finance.customer-invoices.index')
                ->with('error', 'Only draft invoices can be deleted.');
        }

        $invoice->delete();

        return redirect()->route('finance.customer-invoices.index')
            ->with('success', 'Invoice deleted successfully');
    }

    public function submit(string $id)
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can be submitted.');
        }

        $invoice = $this->invoiceService->submitInvoice($invoice);

        return back()->with('success', 'Invoice submitted successfully.');
    }

    public function approve(string $id)
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isSubmitted()) {
            return back()->with('error', 'Only submitted invoices can be approved.');
        }

        $invoice = $this->invoiceService->approveInvoice($invoice);

        return back()->with('success', 'Invoice approved successfully.');
    }

    public function reject(Request $request, string $id)
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isSubmitted()) {
            return back()->with('error', 'Only submitted invoices can be rejected.');
        }

        $invoice = $this->invoiceService->rejectInvoice($invoice, $request->get('reason'));

        return back()->with('success', 'Invoice rejected.');
    }

    public function post(string $id): RedirectResponse
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isDraft() && ! $invoice->isSubmitted() && ! $invoice->isApproved()) {
            return back()->with('error', 'Only draft, submitted, or approved invoices can be posted.');
        }

        try {
            $this->invoiceService->postInvoice($invoice);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to post invoice: '.$e->getMessage());
        }

        return back()->with('success', 'Invoice posted successfully.');
    }

    public function cancel(string $id): RedirectResponse
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if ($invoice->isPosted() || $invoice->isPaid()) {
            return back()->with('error', 'Posted or paid invoices cannot be cancelled.');
        }

        try {
            $this->invoiceService->cancelInvoice($invoice);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel invoice: '.$e->getMessage());
        }

        return back()->with('success', 'Invoice cancelled successfully.');
    }
}
