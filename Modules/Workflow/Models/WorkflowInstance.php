<?php

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    protected $fillable = [
        'workflow_definition_id',
        'entity_type',
        'entity_id',
        'current_state',
        'context',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class);
    }

    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    public function getAvailableTransitions(): array
    {
        return $this->definition->transitions[$this->current_state] ?? [];
    }
}
