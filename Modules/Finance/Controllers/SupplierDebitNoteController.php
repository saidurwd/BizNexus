<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\SupplierDebitNote;
use Modules\Finance\Services\SupplierDebitNoteService;

class SupplierDebitNoteController extends Controller
{
    public function __construct(
        protected SupplierDebitNoteService $debitNoteService,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $debitNotes = SupplierDebitNote::with(['supplier'])
            ->where('company_id', $companyId)
            ->when($request->get('supplier_id'), fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->get('start_date'), fn ($q, $date) => $q->where('note_date', '>=', $date))
            ->when($request->get('end_date'), fn ($q, $date) => $q->where('note_date', '<=', $date))
            ->orderBy('note_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($debitNotes, 'Debit notes retrieved successfully');
    }

    public function show(int $id)
    {
        $debitNote = SupplierDebitNote::with(['supplier', 'journal'])
            ->findOrFail($id);

        return $this->successResponse($debitNote);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'note_number' => 'nullable|string|max:50',
            'note_date' => 'required|date',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $debitNote = $this->debitNoteService->createDebitNote($validated);

            return $this->successResponse($debitNote, 'Debit note created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function post(int $id)
    {
        $debitNote = SupplierDebitNote::findOrFail($id);

        try {
            $debitNote = $this->debitNoteService->postDebitNote($debitNote);

            return $this->successResponse($debitNote, 'Debit note posted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function submit(int $id)
    {
        $debitNote = SupplierDebitNote::findOrFail($id);

        try {
            $debitNote = $this->debitNoteService->submitDebitNote($debitNote);

            return $this->successResponse($debitNote, 'Debit note submitted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function approve(int $id)
    {
        $debitNote = SupplierDebitNote::findOrFail($id);

        try {
            $debitNote = $this->debitNoteService->approveDebitNote($debitNote);

            return $this->successResponse($debitNote, 'Debit note approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function reject(Request $request, int $id)
    {
        $debitNote = SupplierDebitNote::findOrFail($id);

        try {
            $debitNote = $this->debitNoteService->rejectDebitNote($debitNote, $request->get('reason'));

            return $this->successResponse($debitNote, 'Debit note rejected');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function update(Request $request, int $id)
    {
        $debitNote = SupplierDebitNote::findOrFail($id);

        if (! $debitNote->isDraft()) {
            return $this->errorResponse('Only draft debit notes can be updated', 400);
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

        try {
            $debitNote = $this->debitNoteService->updateDebitNote($debitNote, $validated);

            return $this->successResponse($debitNote, 'Debit note updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function cancel(int $id)
    {
        $debitNote = SupplierDebitNote::findOrFail($id);

        try {
            $debitNote = $this->debitNoteService->cancelDebitNote($debitNote);

            return $this->successResponse($debitNote, 'Debit note cancelled');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
