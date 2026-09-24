<?php

namespace Modules\Workflow\Services;

use Illuminate\Support\Collection;
use Modules\Core\Services\PermissionService;
use Modules\Workflow\Models\WorkflowAction;
use Modules\Workflow\Models\WorkflowApproval;
use Modules\Workflow\Models\WorkflowDefinition;
use Modules\Workflow\Models\WorkflowInstance;

class WorkflowService
{
    /**
     * Pending approvals of the active company assigned to the user directly or to one of the user's
     * roles in force in that company.
     */
    public function getPendingApprovals(?int $userId = null): Collection
    {
        $userId = $userId ?? auth()->id();
        $roleSlugs = app(PermissionService::class)->getUserRoles($userId)->where('status', 'active')->pluck('slug');

        $approvals = WorkflowApproval::with(['instance.definition', 'instance'])
            ->whereHas('instance')
            ->where('status', WorkflowApproval::STATUS_PENDING)
            ->where(fn ($query) => $query->where('approver_id', $userId)->orWhereIn('role', $roleSlugs))
            ->orderByDesc('created_at')
            ->get();

        return $approvals->groupBy('instance.entity_type');
    }

    public function getWorkflowHistory(string $entityType, int $entityId): array
    {
        $instance = WorkflowInstance::with(['definition', 'approvals', 'actions'])
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();

        if (! $instance) {
            return [];
        }

        return [
            'instance' => $instance,
            'approvals' => $instance->approvals,
            'actions' => $instance->actions,
        ];
    }

    public function createInstance(string $entityType, int $entityId, string $currentState, ?int $companyId = null): WorkflowInstance
    {
        $definition = WorkflowDefinition::forEntity($entityType)->active()->first();

        if (! $definition) {
            throw new \RuntimeException("No active workflow definition found for entity type: {$entityType}");
        }

        return WorkflowInstance::create([
            'company_id' => $companyId,
            'workflow_definition_id' => $definition->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'current_state' => $currentState,
            'started_at' => now(),
        ]);
    }

    public function transitionInstance(string $entityType, int $entityId, string $newState, ?string $comments = null): void
    {
        $instance = WorkflowInstance::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();

        if (! $instance) {
            return;
        }

        $oldState = $instance->current_state;
        $instance->current_state = $newState;
        $instance->save();

        WorkflowAction::create([
            'workflow_instance_id' => $instance->id,
            'action' => strtolower($newState),
            'from_state' => $oldState,
            'to_state' => $newState,
            'user_id' => auth()->id(),
            'comments' => $comments,
        ]);
    }
}
