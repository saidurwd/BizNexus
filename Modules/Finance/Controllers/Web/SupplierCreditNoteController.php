<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierCreditNote;
use Modules\Finance\Services\SupplierCreditNoteService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class SupplierCreditNoteController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService,
        protected SupplierCreditNoteService $creditNoteService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index()
    {
        $this->checkPermission('finance.suppliers.view');

        $creditNotes = SupplierCreditNote::with(['supplier', 'invoice'])
            ->orderByDesc('credit_note_date')
            ->get();

        return view('finance.supplier-credit-notes.index', compact('creditNotes'));
    }

    public function create()
    {
        $this->checkPermission('finance.suppliers.create');

        $suppliers = Supplier::where('status', 'active')->get();
        $invoices = SupplierInvoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->get();

        return view('finance.supplier-credit-notes.create', compact('suppliers', 'invoices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.create');

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_invoice_id' => 'nullable|exists:supplier_invoices,id',
            'credit_note_number' => 'required|string|max:50|unique:supplier_credit_notes,credit_note_number',
            'credit_note_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();
        $validated['status'] = SupplierCreditNote::STATUS_DRAFT;
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        SupplierCreditNote::create($validated);

        return redirect()->route('finance.supplier-credit-notes.index')
            ->with('success', 'Credit note created successfully.');
    }

    public function show(int $id)
    {
        $this->checkPermission('finance.suppliers.view');

        $creditNote = SupplierCreditNote::with(['supplier', 'invoice'])->findOrFail($id);

        return view('finance.supplier-credit-notes.show', compact('creditNote'));
    }

    public function edit(int $id)
    {
        $this->checkPermission('finance.suppliers.update');

        $creditNote = SupplierCreditNote::findOrFail($id);

        if (!$creditNote->isDraft()) {
            return redirect()->route('finance.supplier-credit-notes.show', $id)
                ->with('error', 'Only draft credit notes can be edited.');
        }

        $suppliers = Supplier::where('status', 'active')->get();
        $invoices = SupplierInvoice::whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->get();

        return view('finance.supplier-credit-notes.edit', compact('creditNote', 'suppliers', 'invoices'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.update');

        $creditNote = SupplierCreditNote::findOrFail($id);

        if (!$creditNote->isDraft()) {
            return redirect()->route('finance.supplier-credit-notes.show', $id)
                ->with('error', 'Only draft credit notes can be edited.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_invoice_id' => 'nullable|exists:supplier_invoices,id',
            'credit_note_number' => 'required|string|max:50|unique:supplier_credit_notes,credit_note_number,' . $id,
            'credit_note_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $creditNote->update($validated);

        return redirect()->route('finance.supplier-credit-notes.show', $id)
            ->with('success', 'Credit note updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.delete');

        $creditNote = SupplierCreditNote::findOrFail($id);

        if (!$creditNote->isDraft()) {
            return redirect()->route('finance.supplier-credit-notes.index')
                ->with('error', 'Only draft credit notes can be deleted.');
        }

        $creditNote->delete();

        return redirect()->route('finance.supplier-credit-notes.index')
            ->with('success', 'Credit note deleted successfully.');
    }

    public function submit(int $id): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.approve');

        $creditNote = SupplierCreditNote::findOrFail($id);

        if (!$creditNote->isDraft()) {
            return back()->with('error', 'Only draft credit notes can be submitted.');
        }

        $creditNote->update(['status' => 'submitted']);

        return back()->with('success', 'Credit note submitted successfully.');
    }

    public function approve(int $id): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.approve');

        $creditNote = SupplierCreditNote::findOrFail($id);

        if ($creditNote->status !== 'submitted') {
            return back()->with('error', 'Only submitted credit notes can be approved.');
        }

        $creditNote->update(['status' => 'approved']);

        return back()->with('success', 'Credit note approved successfully.');
    }

    public function post(int $id): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.post');

        $creditNote = SupplierCreditNote::findOrFail($id);

        if ($creditNote->status !== 'approved') {
            return back()->with('error', 'Only approved credit notes can be posted.');
        }

        try {
            $this->creditNoteService->postCreditNote($creditNote);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to post credit note: ' . $e->getMessage());
        }

        return back()->with('success', 'Credit note posted successfully.');
    }

    public function cancel(int $id): RedirectResponse
    {
        $this->checkPermission('finance.suppliers.cancel');

        $creditNote = SupplierCreditNote::findOrFail($id);

        if (in_array($creditNote->status, ['posted', 'cancelled'])) {
            return back()->with('error', 'Posted or cancelled credit notes cannot be cancelled.');
        }

        $creditNote->update(['status' => 'cancelled']);

        return back()->with('success', 'Credit note cancelled successfully.');
    }
}
