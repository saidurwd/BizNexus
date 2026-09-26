<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\Tax;
use Modules\Finance\Services\SupplierInvoiceService;

class SupplierInvoiceController extends Controller
{
    use FiltersDocumentLists;

    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService,
        protected SupplierInvoiceService $supplierInvoiceService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $query = SupplierInvoice::with(['supplier', 'tax', 'currency']);
        $filters = $this->applyListFilters($query, $request, 'invoice_date', ['invoice_number', 'description'], 'supplier');
        $invoices = $query
            ->when($filters['overdue'] ?? false, fn ($query) => $query->pending()->whereDate('due_date', '<', app(CompanyContextService::class)->today()->toDateString()))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('finance.supplier-invoices.index', compact('invoices', 'filters'));
    }

    public function create()
    {

        $suppliers = Supplier::where('status', 'active')->get();
        $taxes = Tax::where('status', 'active')->get();

        return view('finance.supplier-invoices.create', compact('suppliers', 'taxes'));
    }

    public function store(Request $request): RedirectResponse
    {

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:50|unique:supplier_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'tax_id' => 'nullable|exists:taxes,id',
            'subtotal' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();
        $validated['branch_id'] = $this->getActiveBranchId();
        $validated['status'] = SupplierInvoice::STATUS_DRAFT;
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $validated['outstanding_amount'] = $validated['total_amount'];

        SupplierInvoice::create($validated);

        return redirect()->route('finance.supplier-invoices.index')
            ->with('success', 'Supplier invoice created successfully.');
    }

    public function show(int $id)
    {

        $invoice = SupplierInvoice::with(['supplier', 'tax', 'lines.account', 'lines.tax'])
            ->findOrFail($id);

        return view('finance.supplier-invoices.show', compact('invoice'));
    }

    public function edit(int $id)
    {

        $invoice = SupplierInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return redirect()->route('finance.supplier-invoices.show', $id)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $suppliers = Supplier::where('status', 'active')->get();
        $taxes = Tax::where('status', 'active')->get();

        return view('finance.supplier-invoices.edit', compact('invoice', 'suppliers', 'taxes'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {

        $invoice = SupplierInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return redirect()->route('finance.supplier-invoices.show', $id)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:50|unique:supplier_invoices,invoice_number,'.$id,
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'tax_id' => 'nullable|exists:taxes,id',
            'subtotal' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['outstanding_amount'] = $validated['total_amount'];

        $invoice->update($validated);

        return redirect()->route('finance.supplier-invoices.show', $id)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {

        $invoice = SupplierInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return redirect()->route('finance.supplier-invoices.index')
                ->with('error', 'Only draft invoices can be deleted.');
        }

        $invoice->delete();

        return redirect()->route('finance.supplier-invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    public function post(int $id): RedirectResponse
    {

        $invoice = SupplierInvoice::findOrFail($id);

        if (! $invoice->isApproved()) {
            return back()->with('error', 'Only approved invoices can be posted.');
        }

        try {
            $this->supplierInvoiceService->postInvoice($invoice);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to post invoice: '.$e->getMessage());
        }

        return back()->with('success', 'Invoice posted successfully.');
    }

    public function cancel(int $id): RedirectResponse
    {

        $invoice = SupplierInvoice::findOrFail($id);

        if ($invoice->isPosted() || $invoice->isPaid()) {
            return back()->with('error', 'Posted or paid invoices cannot be cancelled.');
        }

        try {
            $this->supplierInvoiceService->cancelInvoice($invoice);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel invoice: '.$e->getMessage());
        }

        return back()->with('success', 'Invoice cancelled successfully.');
    }

    public function submit(int $id)
    {

        $invoice = SupplierInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can be submitted.');
        }

        $invoice = $this->supplierInvoiceService->submitInvoice($invoice);

        return back()->with('success', 'Invoice submitted successfully.');
    }

    public function approve(int $id)
    {

        $invoice = SupplierInvoice::findOrFail($id);

        if (! $invoice->isSubmitted()) {
            return back()->with('error', 'Only submitted invoices can be approved.');
        }

        $invoice = $this->supplierInvoiceService->approveInvoice($invoice);

        return back()->with('success', 'Invoice approved successfully.');
    }

    public function reject(Request $request, int $id)
    {

        $invoice = SupplierInvoice::findOrFail($id);

        if (! $invoice->isSubmitted()) {
            return back()->with('error', 'Only submitted invoices can be rejected.');
        }

        $invoice = $this->supplierInvoiceService->rejectInvoice($invoice, $request->get('reason'));

        return back()->with('success', 'Invoice rejected.');
    }
}
