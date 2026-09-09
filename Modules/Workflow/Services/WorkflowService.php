<?php

namespace Modules\Workflow\Services;

use Modules\Workflow\Models\WorkflowDefinition;
use Modules\Workflow\Models\WorkflowInstance;
use Modules\Workflow\Models\WorkflowApproval;
use Modules\Workflow\Models\WorkflowAction;

class WorkflowService
{
    public function getPendingApprovals(?int $userId = null): \Illuminate\Support\Collection
    {
        $userId = $userId ?? auth()->id();

        $approvals = WorkflowApproval::with(['instance.definition', 'instance'])
            ->where('status', WorkflowApproval::STATUS_PENDING)
            ->where(function ($query) use ($userId) {
                $query->where('approver_id', $userId)
                      ->orWhereIn('role', function ($q) use ($userId) {
                          $q->select('slug')
                              ->from('roles')
                              ->join('role_user', 'roles.id', '=', 'role_user.role_id')
                              ->where('role_user.user_id', $userId);
                      });
            })
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

        if (!$instance) {
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

        if (!$definition) {
            throw new \RuntimeException("No active workflow definition found for entity type: {$entityType}");
        }

        return WorkflowInstance::create([
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

        if (!$instance) {
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
