<?php

namespace Modules\Finance\Controllers;

use Illuminate\Http\Request;
use Modules\Finance\Models\Journal;
use Modules\Finance\Services\JournalService;
use Modules\Core\Services\CompanyContextService;

class JournalController extends Controller
{
    public function __construct(
        protected JournalService $journalService,
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $request->get('company_id') ?? $this->companyContext->getCompanyId();

        $journals = Journal::with(['lines.account', 'fiscalPeriod'])
            ->where('company_id', $companyId)
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->get('start_date'), fn($q, $date) => $q->where('journal_date', '>=', $date))
            ->when($request->get('end_date'), fn($q, $date) => $q->where('journal_date', '<=', $date))
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($journals, 'Journals retrieved successfully');
    }

    public function show(int $id)
    {
        $journal = Journal::with(['lines.account', 'fiscalPeriod', 'postedBy', 'createdBy'])
            ->findOrFail($id);

        return $this->successResponse($journal);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'journal_date' => 'required|date',
            'fiscal_period_id' => 'nullable|exists:fiscal_periods,id',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
            'lines.*.department_id' => 'nullable|exists:departments,id',
            'lines.*.branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $journal = $this->journalService->create($validated);
            return $this->successResponse($journal, 'Journal created successfully', 201);
        } catch (\Modules\Core\Exceptions\UnbalancedJournalException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function update(Request $request, int $id)
    {
        $journal = Journal::findOrFail($id);

        $validated = $request->validate([
            'journal_date' => 'sometimes|date',
            'description' => 'nullable|string',
        ]);

        try {
            $journal = $this->journalService->update($journal, $validated);
            return $this->successResponse($journal, 'Journal updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function destroy(int $id)
    {
        $journal = Journal::findOrFail($id);

        try {
            $this->journalService->delete($journal);
            return $this->successResponse(null, 'Journal deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function addLine(Request $request, int $id)
    {
        $journal = Journal::findOrFail($id);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'nullable|string',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $line = $this->journalService->addLine($journal, $validated);
            $journal->refresh();
            return $this->successResponse($journal, 'Line added successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function removeLine(int $journalId, int $lineId)
    {
        $line = \Modules\Finance\Models\JournalLine::findOrFail($lineId);

        if ($line->journal_id !== $journalId) {
            return $this->errorResponse('Line does not belong to this journal', 400);
        }

        try {
            $this->journalService->removeLine($line);
            return $this->successResponse(null, 'Line removed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function submit(int $id)
    {
        $journal = Journal::findOrFail($id);

        try {
            $journal = $this->journalService->submit($journal);
            return $this->successResponse($journal, 'Journal submitted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function approve(int $id)
    {
        $journal = Journal::findOrFail($id);

        try {
            $journal = $this->journalService->approve($journal);
            return $this->successResponse($journal, 'Journal approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function reject(Request $request, int $id)
    {
        $journal = Journal::findOrFail($id);

        try {
            $journal = $this->journalService->reject($journal, $request->get('reason'));
            return $this->successResponse($journal, 'Journal rejected');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function post(int $id)
    {
        $journal = Journal::findOrFail($id);

        try {
            $journal = $this->journalService->post($journal);
            return $this->successResponse($journal, 'Journal posted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function reverse(Request $request, int $id)
    {
        $journal = Journal::findOrFail($id);

        try {
            $reversal = $this->journalService->reverse($journal, $request->get('reason'));
            return $this->successResponse($reversal, 'Journal reversed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function cancel(int $id)
    {
        $journal = Journal::findOrFail($id);

        try {
            $journal = $this->journalService->cancel($journal);
            return $this->successResponse($journal, 'Journal cancelled');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
