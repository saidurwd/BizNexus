<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\RecurringJournal;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class RecurringJournalController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index()
    {
        $this->checkPermission('finance.journals.view');

        $recurringJournals = RecurringJournal::with(['company', 'createdBy'])
            ->orderByDesc('next_run_date')
            ->get();

        return view('finance.recurring-journals.index', compact('recurringJournals'));
    }

    public function create()
    {
        $this->checkPermission('finance.journals.create');

        return view('finance.recurring-journals.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkPermission('finance.journals.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:DAILY,WEEKLY,MONTHLY,QUARTERLY,YEARLY',
            'next_run_date' => 'required|date',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:active,paused,completed',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        RecurringJournal::create($validated);

        return redirect()->route('finance.recurring-journals.index')
            ->with('success', 'Recurring journal created successfully.');
    }

    public function edit(int $id)
    {
        $this->checkPermission('finance.journals.update');

        $recurringJournal = RecurringJournal::findOrFail($id);

        return view('finance.recurring-journals.edit', compact('recurringJournal'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->checkPermission('finance.journals.update');

        $recurringJournal = RecurringJournal::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:DAILY,WEEKLY,MONTHLY,QUARTERLY,YEARLY',
            'next_run_date' => 'required|date',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:active,paused,completed',
        ]);

        $validated['updated_by'] = auth()->id();

        $recurringJournal->update($validated);

        return redirect()->route('finance.recurring-journals.index')
            ->with('success', 'Recurring journal updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->checkPermission('finance.journals.delete');

        $recurringJournal = RecurringJournal::findOrFail($id);
        $recurringJournal->delete();

        return redirect()->route('finance.recurring-journals.index')
            ->with('success', 'Recurring journal deleted successfully.');
    }
}
