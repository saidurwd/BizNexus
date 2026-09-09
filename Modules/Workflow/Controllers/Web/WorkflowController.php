<?php

namespace Modules\Workflow\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Workflow\Models\WorkflowDefinition;
use Modules\Workflow\Models\WorkflowInstance;
use Modules\Workflow\Models\WorkflowApproval;
use Modules\Workflow\Services\WorkflowService;

class WorkflowController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    public function index()
    {
        $pendingApprovals = $this->workflowService->getPendingApprovals();

        $recentInstances = WorkflowInstance::with('definition')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('workflow.index', compact('pendingApprovals', 'recentInstances'));
    }

    public function show(int $id)
    {
        $instance = WorkflowInstance::with(['definition', 'approvals.user', 'actions.user'])
            ->findOrFail($id);

        return view('workflow.show', compact('instance'));
    }

    public function definitions()
    {
        $definitions = WorkflowDefinition::orderBy('entity_type')->get();

        return view('workflow.definitions', compact('definitions'));
    }

    public function storeDefinition(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|max:100',
            'states' => 'required|array',
            'transitions' => 'required|array',
            'approval_roles' => 'nullable|array',
        ]);

        WorkflowDefinition::create($validated);

        return redirect()->route('workflow.definitions')
            ->with('success', 'Workflow definition created successfully.');
    }

    public function updateDefinition(Request $request, int $id)
    {
        $definition = WorkflowDefinition::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|max:100',
            'states' => 'required|array',
            'transitions' => 'required|array',
            'approval_roles' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $definition->update($validated);

        return redirect()->route('workflow.definitions')
            ->with('success', 'Workflow definition updated successfully.');
    }
}
