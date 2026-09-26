<?php

namespace Modules\Core\Listeners;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Modules\Core\Models\ScheduledTaskRun;

/**
 * Keeps a history of every scheduled task run for the Scheduled Jobs and System Health screens.
 */
class RecordScheduledTaskRuns
{
    /**
     * Open run ids by task object, so the finishing event updates the right row.
     *
     * @var array<int, int>
     */
    protected static array $openRuns = [];

    public static function taskName(Event $task): string
    {
        if ($task->description) {
            return $task->description;
        }

        return $task->command ? trim(Str::afterLast($task->command, 'artisan'), " '\"") : 'Closure';
    }

    public function starting(ScheduledTaskStarting $event): void
    {
        static::$openRuns[spl_object_id($event->task)] = ScheduledTaskRun::create([
            'task' => static::taskName($event->task),
            'expression' => $event->task->expression,
            'status' => ScheduledTaskRun::STATUS_RUNNING,
            'started_at' => now(),
        ])->id;
    }

    /**
     * Laravel fires "failed" and then "finished" for a task that throws, so a failure is never overwritten.
     */
    public function failed(ScheduledTaskFailed $event): void
    {
        $this->close($event->task, ScheduledTaskRun::STATUS_FAILED, Str::limit($event->exception->getMessage(), 2000));
    }

    public function finished(ScheduledTaskFinished $event): void
    {
        $this->close($event->task, $event->task->exitCode ? ScheduledTaskRun::STATUS_FAILED : ScheduledTaskRun::STATUS_SUCCEEDED);
        unset(static::$openRuns[spl_object_id($event->task)]);
    }

    public function skipped(ScheduledTaskSkipped $event): void
    {
        ScheduledTaskRun::create([
            'task' => static::taskName($event->task),
            'expression' => $event->task->expression,
            'status' => ScheduledTaskRun::STATUS_SKIPPED,
            'started_at' => now(),
            'finished_at' => now(),
            'duration_ms' => 0,
        ]);
    }

    protected function close(Event $task, string $status, ?string $output = null): void
    {
        $run = ScheduledTaskRun::find(static::$openRuns[spl_object_id($task)] ?? 0);

        if (! $run || $run->status !== ScheduledTaskRun::STATUS_RUNNING) {
            return;
        }

        $run->update([
            'status' => $status,
            'finished_at' => now(),
            'duration_ms' => (int) $run->started_at->diffInMilliseconds(now()),
            'output' => $output,
        ]);
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            ScheduledTaskStarting::class => 'starting',
            ScheduledTaskFailed::class => 'failed',
            ScheduledTaskFinished::class => 'finished',
            ScheduledTaskSkipped::class => 'skipped',
        ];
    }
}
