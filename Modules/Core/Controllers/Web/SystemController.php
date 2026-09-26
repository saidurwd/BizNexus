<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Composer\InstalledVersions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Core\Listeners\RecordScheduledTaskRuns;
use Modules\Core\Models\AuditLog;
use Modules\Core\Models\ScheduledTaskRun;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\SystemHealthService;
use Modules\Core\Support\SystemSchedule;

/**
 * System Health, Queue Monitor, Scheduled Jobs and About.
 */
class SystemController extends Controller
{
    public function health(SystemHealthService $health): View
    {
        $checks = $health->checks();

        return view('core.system.health', ['checks' => $checks, 'overall' => $health->overallStatus($checks)]);
    }

    public function queue(): View
    {
        $connection = config('queue.default');
        $usesDatabase = config("queue.connections.{$connection}.driver") === 'database';
        $jobsTable = config("queue.connections.{$connection}.table", 'jobs');
        $now = now()->getTimestamp();

        $queues = $usesDatabase
            ? DB::table($jobsTable)
                ->selectRaw('queue, SUM(CASE WHEN reserved_at IS NULL AND available_at <= ? THEN 1 ELSE 0 END) AS waiting_count, SUM(CASE WHEN reserved_at IS NULL AND available_at > ? THEN 1 ELSE 0 END) AS delayed_count, SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) AS running_count, MIN(CASE WHEN reserved_at IS NULL THEN available_at END) AS oldest_available_at', [$now, $now])
                ->groupBy('queue')
                ->orderBy('queue')
                ->get()
            : collect();

        $pendingJobs = $usesDatabase
            ? DB::table($jobsTable)->orderBy('available_at')->limit(50)->get()->map(fn ($job) => [
                'id' => $job->id,
                'queue' => $job->queue,
                'name' => $this->jobName($job->payload),
                'attempts' => $job->attempts,
                'state' => $job->reserved_at ? 'running' : ($job->available_at > $now ? 'delayed' : 'waiting'),
                'available_at' => now()->setTimestamp($job->available_at),
                'created_at' => now()->setTimestamp($job->created_at),
            ])
            : collect();

        $failedJobs = DB::table(config('queue.failed.table', 'failed_jobs'))->latest('failed_at')->limit(100)->get()->map(fn ($job) => [
            'uuid' => $job->uuid,
            'queue' => $job->queue,
            'name' => $this->jobName($job->payload),
            'error' => Str::limit(strtok((string) $job->exception, "\n"), 300),
            'failed_at' => $job->failed_at,
        ]);

        return view('core.system.queue', compact('connection', 'usesDatabase', 'queues', 'pendingJobs', 'failedJobs'));
    }

    public function retryFailedJob(string $uuid, AuditService $audit): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
        $audit->logCustom('Core', 'FailedJob', 0, 'RETRY', ['uuid' => $uuid]);

        return back()->with('success', __('The job was put back on the queue.'));
    }

    public function retryAllFailedJobs(AuditService $audit): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
        $audit->logCustom('Core', 'FailedJob', 0, 'RETRY_ALL', []);

        return back()->with('success', __('All failed jobs were put back on the queue.'));
    }

    public function forgetFailedJob(string $uuid, AuditService $audit): RedirectResponse
    {
        Artisan::call('queue:forget', ['id' => $uuid]);
        $audit->logCustom('Core', 'FailedJob', 0, 'DISCARD', ['uuid' => $uuid]);

        return back()->with('success', __('The failed job was discarded.'));
    }

    public function schedule(): View
    {
        $schedule = new Schedule(config('app.schedule_timezone', config('app.timezone')));
        SystemSchedule::define($schedule);

        $lastRuns = ScheduledTaskRun::whereIn('id', ScheduledTaskRun::selectRaw('MAX(id)')->groupBy('task'))->get()->keyBy('task');

        $tasks = collect($schedule->events())->map(function ($event) use ($lastRuns) {
            $name = RecordScheduledTaskRuns::taskName($event);

            return [
                'name' => $name,
                'expression' => $event->expression,
                'timezone' => (string) ($event->timezone ?? config('app.timezone')),
                'next_due' => $event->nextRunDate(),
                'without_overlapping' => $event->withoutOverlapping,
                'last_run' => $lastRuns->get($name),
            ];
        });

        $recentRuns = ScheduledTaskRun::latest('started_at')->limit(50)->get();

        return view('core.system.schedule', compact('tasks', 'recentRuns'));
    }

    public function about(): View
    {
        $packages = collect(['laravel/framework', 'laravel/fortify', 'laravel/sanctum', 'jeroennoten/laravel-adminlte', 'barryvdh/laravel-dompdf', 'openspout/openspout', 'pestphp/pest'])
            ->filter(fn (string $package) => InstalledVersions::isInstalled($package))
            ->mapWithKeys(fn (string $package) => [$package => InstalledVersions::getPrettyVersion($package)]);

        $connection = DB::connection();

        return view('core.system.about', [
            'application' => [
                __('Name') => config('app.name'),
                __('Version') => config('app.version', '1.0.0'),
                __('Environment') => app()->environment(),
                __('Debug mode') => config('app.debug') ? __('On') : __('Off'),
                __('URL') => config('app.url'),
                __('Time zone') => config('app.timezone'),
                __('Default language') => config('app.locale'),
                __('Data region') => config('tenancy.data_region') ?: __('Single region'),
            ],
            'platform' => [
                'PHP' => PHP_VERSION,
                __('Operating system') => php_uname('s').' '.php_uname('r'),
                __('Database') => $connection->getDriverName().' '.$connection->getServerVersion(),
                __('Cache store') => config('cache.default'),
                __('Queue connection') => config('queue.default'),
                __('Session driver') => config('session.driver'),
                __('Mail transport') => config('mail.default'),
            ],
            'modules' => $this->modules(),
            'packages' => $packages,
            'auditEntries' => AuditLog::count(),
        ]);
    }

    /**
     * @return Collection<int, array{name: string, routes: int, models: int}>
     */
    protected function modules(): Collection
    {
        $routes = collect(Route::getRoutes()->getRoutes());

        return collect(glob(base_path('Modules/*'), GLOB_ONLYDIR))->map(fn (string $path) => [
            'name' => basename($path),
            'routes' => $routes->filter(fn ($route) => str_starts_with((string) $route->getActionName(), 'Modules\\'.basename($path).'\\'))->count(),
            'models' => count(glob($path.'/Models/*.php')),
        ])->values();
    }

    protected function jobName(string $payload): string
    {
        return json_decode($payload, true)['displayName'] ?? __('Unknown job');
    }
}
