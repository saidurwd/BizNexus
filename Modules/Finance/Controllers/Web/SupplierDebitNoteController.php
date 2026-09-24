<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierDebitNote;
use Modules\Finance\Services\SupplierDebitNoteService;

class SupplierDebitNoteController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService,
        protected SupplierDebitNoteService $debitNoteService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index()
    {

        $debitNotes = SupplierDebitNote::with('supplier')
            ->orderByDesc('note_date')
            ->get();

        return view('finance.supplier-debit-notes.index', compact('debitNotes'));
    }

    public function create()
    {

        $suppliers = Supplier::where('status', 'active')->get();

        return view('finance.supplier-debit-notes.create', compact('suppliers'));
    }

    public function store(Request $request): RedirectResponse
    {

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'note_number' => 'required|string|max:50|unique:supplier_debit_notes,note_number',
            'note_date' => 'required|date',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();
        $validated['status'] = SupplierDebitNote::STATUS_DRAFT;
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        SupplierDebitNote::create($validated);

        return redirect()->route('finance.supplier-debit-notes.index')
            ->with('success', 'Debit note created successfully.');
    }

    public function show(int $id)
    {

        $debitNote = SupplierDebitNote::with('supplier')->findOrFail($id);

        return view('finance.supplier-debit-notes.show', compact('debitNote'));
    }

    public function edit(int $id)
    {

        $debitNote = SupplierDebitNote::findOrFail($id);

        if (! $debitNote->isDraft()) {
            return redirect()->route('finance.supplier-debit-notes.show', $id)
                ->with('error', 'Only draft debit notes can be edited.');
        }

        $suppliers = Supplier::where('status', 'active')->get();

        return view('finance.supplier-debit-notes.edit', compact('debitNote', 'suppliers'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {

        $debitNote = SupplierDebitNote::findOrFail($id);

        if (! $debitNote->isDraft()) {
            return redirect()->route('finance.supplier-debit-notes.show', $id)
                ->with('error', 'Only draft debit notes can be edited.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'note_number' => 'required|string|max:50|unique:supplier_debit_notes,note_number,'.$id,
            'note_date' => 'required|date',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $validated['updated_by'] = auth()->id();

        $debitNote->update($validated);

        return redirect()->route('finance.supplier-debit-notes.show', $id)
            ->with('success', 'Debit note updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {

        $debitNote = SupplierDebitNote::findOrFail($id);

        if (! $debitNote->isDraft()) {
            return redirect()->route('finance.supplier-debit-notes.index')
                ->with('error', 'Only draft debit notes can be deleted.');
        }

        $debitNote->delete();

        return redirect()->route('finance.supplier-debit-notes.index')
            ->with('success', 'Debit note deleted successfully.');
    }

    public function post(int $id): RedirectResponse
    {

        $debitNote = SupplierDebitNote::findOrFail($id);

        if (! $debitNote->isApproved()) {
            return back()->with('error', 'Only approved debit notes can be posted.');
        }

        try {
            $this->debitNoteService->postDebitNote($debitNote);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to post debit note: '.$e->getMessage());
        }

        return back()->with('success', 'Debit note posted successfully.');
    }

    public function submit(int $id): RedirectResponse
    {

        $debitNote = SupplierDebitNote::findOrFail($id);

        if (! $debitNote->isDraft()) {
            return back()->with('error', 'Only draft debit notes can be submitted.');
        }

        try {
            $this->debitNoteService->submitDebitNote($debitNote);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit debit note: '.$e->getMessage());
        }

        return back()->with('success', 'Debit note submitted successfully.');
    }

    public function approve(int $id): RedirectResponse
    {

        $debitNote = SupplierDebitNote::findOrFail($id);

        if (! $debitNote->isSubmitted()) {
            return back()->with('error', 'Only submitted debit notes can be approved.');
        }

        try {
            $this->debitNoteService->approveDebitNote($debitNote);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve debit note: '.$e->getMessage());
        }

        return back()->with('success', 'Debit note approved successfully.');
    }

    public function reject(int $id): RedirectResponse
    {

        $debitNote = SupplierDebitNote::findOrFail($id);

        if (! $debitNote->isSubmitted()) {
            return back()->with('error', 'Only submitted debit notes can be rejected.');
        }

        $debitNote->update(['status' => SupplierDebitNote::STATUS_REJECTED]);

        return back()->with('success', 'Debit note rejected successfully.');
    }

    public function cancel(int $id): RedirectResponse
    {

        $debitNote = SupplierDebitNote::findOrFail($id);

        if ($debitNote->isPosted()) {
            return back()->with('error', 'Posted debit notes cannot be cancelled.');
        }

        $debitNote->update(['status' => SupplierDebitNote::STATUS_CANCELLED]);

        return back()->with('success', 'Debit note cancelled successfully.');
    }
}
