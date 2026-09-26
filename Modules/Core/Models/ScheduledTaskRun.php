<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * One run of a scheduled task, recorded from the scheduler's events.
 */
class ScheduledTaskRun extends Model
{
    use MassPrunable;

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public $timestamps = false;

    protected $fillable = ['task', 'expression', 'status', 'started_at', 'finished_at', 'duration_ms', 'output'];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function prunable(): Builder
    {
        return static::where('started_at', '<', now()->subDays(config('security.retention_days.scheduled_task_runs')));
    }
}
