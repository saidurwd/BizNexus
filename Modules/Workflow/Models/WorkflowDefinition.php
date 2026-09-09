<?php

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    protected $fillable = [
        'name',
        'entity_type',
        'states',
        'transitions',
        'approval_roles',
        'is_active',
    ];

    protected $casts = [
        'states' => 'array',
        'transitions' => 'array',
        'approval_roles' => 'array',
        'is_active' => 'boolean',
    ];

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }
}
