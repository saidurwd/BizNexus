<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\Models\ScheduledTaskRun;
use Modules\Core\Support\SystemSchedule;
use Throwable;

/**
 * Health checks for the System Health screen. Each check reports ok, warning or error with a plain explanation.
 */
class SystemHealthService
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const ERROR = 'error';

    /**
     * Extensions the application relies on: money (bcmath), locale formatting (intl), PDFs (dom, gd, mbstring),
     * spreadsheets (zip), uploads (fileinfo).
     */
    public const REQUIRED_EXTENSIONS = ['bcmath', 'intl', 'mbstring', 'pdo', 'openssl', 'zip', 'dom', 'gd', 'fileinfo'];

    /**
     * @return list<array{name: string, status: string, detail: string}>
     */
    public function checks(): array
    {
        $checks = [
            'Database' => fn () => $this->database(),
            'Migrations' => fn () => $this->migrations(),
            'Cache' => fn () => $this->cache(),
            'Storage' => fn () => $this->storage(),
            'Queue' => fn () => $this->queue(),
            'Scheduler' => fn () => $this->scheduler(),
            'Audit log integrity' => fn () => $this->auditChain(),
            'Configuration' => fn () => $this->configuration(),
            'PHP extensions' => fn () => $this->extensions(),
        ];

        $results = [];

        foreach ($checks as $name => $check) {
            try {
                $results[] = ['name' => $name, ...$check()];
            } catch (Throwable $exception) {
                $results[] = ['name' => $name, 'status' => self::ERROR, 'detail' => Str::limit($exception->getMessage(), 200)];
            }
        }

        return $results;
    }

    /**
     * @param  list<array{name: string, status: string, detail: string}>  $checks
     */
    public function overallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        return in_array(self::ERROR, $statuses, true) ? self::ERROR : (in_array(self::WARNING, $statuses, true) ? self::WARNING : self::OK);
    }

    /**
     * @return array{status: string, detail: string}
     */
    protected function database(): array
    {
        $started = microtime(true);
        DB::select('select 1');
        $latency = round((microtime(true) - $started) * 1000, 1);
        $connection = DB::connection();

        return [
            'status' => $latency > 200 ? self::WARNING : self::OK,
            'detail' => "{$connection->getDriverName()} ".$connection->getServerVersion()." · {$latency} ms to answer",
        ];
    }

    /**
     * @return array{status: string, detail: string}
     */
    protected function migrations(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));
        $pending = array_diff(array_keys($files), $migrator->getRepository()->getRan());

        return $pending === []
            ? ['status' => self::OK, 'detail' => count($files).' migrations applied']
            : ['status' => self::ERROR, 'detail' => count($pending).' pending: run php artisan migrate ('.Str::limit(implode(', ', array_slice($pending, 0, 3)), 150).')'];
    }

    /**
     * @return array{status: string, detail: string}
     */
    protected function cache(): array
    {
        $key = 'health-check:'.Str::random(8);
        Cache::put($key, 'ok', 10);
        $works = Cache::pull($key) === 'ok';

        return ['status' => $works ? self::OK : self::ERROR, 'detail' => config('cache.default').($works ? ' store reads and writes' : ' store did not return what was written')];
    }

    /**
     * @return array{status: string, detail: string}
     */
    protected function storage(): array
    {
        $path = 'health-check/'.Str::random(8).'.txt';
        Storage::disk('local')->put($path, 'ok');
        $writable = Storage::disk('local')->get($path) === 'ok';
        Storage::disk('local')->delete($path);

        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        $freePercent = $free && $total ? round($free / $total * 100) : null;
        $space = $freePercent === null ? '' : ' · '.round($free / 1024 ** 3, 1)." GB free ({$freePercent}%)";

        return [
            'status' => ! $writable ? self::ERROR : ($freePercent !== null && $freePercent < 10 ? self::WARNING : self::OK),
            'detail' => ($writable ? 'Private storage is writable' : 'Private storage is not writable').$space,
        ];
    }

    /**
     * @return array{status: string, detail: string}
     */
    protected function queue(): array
    {
        $connection = config('queue.default');
        $failed = DB::table(config('queue.failed.table', 'failed_jobs'))->count();

        if (config("queue.connections.{$connection}.driver") !== 'database') {
            return ['status' => $failed ? self::WARNING : self::OK, 'detail' => "{$connection} driver · {$failed} failed jobs"];
        }

        $jobs = DB::table(config("queue.connections.{$connection}.table", 'jobs'));
        $pending = (clone $jobs)->whereNull('reserved_at')->where('available_at', '<=', now()->getTimestamp())->count();
        $oldest = (clone $jobs)->whereNull('reserved_at')->where('available_at', '<=', now()->getTimestamp())->min('available_at');
        $waitingMinutes = $oldest ? (int) floor((now()->getTimestamp() - $oldest) / 60) : 0;

        return [
            'status' => $waitingMinutes > 15 ? self::ERROR : ($failed ? self::WARNING : self::OK),
            'detail' => "{$pending} waiting".($waitingMinutes > 15 ? " (oldest {$waitingMinutes} min: is a queue worker running?)" : '')." · {$failed} failed",
        ];
    }

    /**
     * @return array{status: string, detail: string}
     */
    protected function scheduler(): array
    {
        $lastRun = ScheduledTaskRun::max('started_at');

        if (! $lastRun) {
            return ['status' => self::WARNING, 'detail' => 'No scheduled task has run yet. Add "* * * * * php artisan schedule:run" to cron.'];
        }

        $minutesAgo = (int) now()->diffInMinutes($lastRun, true);

        return [
            'status' => $minutesAgo > 120 ? self::ERROR : self::OK,
            'detail' => "Last task started {$minutesAgo} min ago".($minutesAgo > 120 ? ': is cron running schedule:run?' : ''),
        ];
    }

    /**
     * @return array{status: string, detail: string}
     */
    protected function auditChain(): array
    {
        $lastCheck = ScheduledTaskRun::where('task', SystemSchedule::VERIFY_AUDIT_LOG)->whereIn('status', [ScheduledTaskRun::STATUS_SUCCEEDED, ScheduledTaskRun::STATUS_FAILED])->latest('started_at')->first();

        return match (true) {
            ! $lastCheck => ['status' => self::WARNING, 'detail' => 'Not verified yet (runs daily at 02:00, or run php artisan audit:verify)'],
            $lastCheck->status === ScheduledTaskRun::STATUS_FAILED => ['status' => self::ERROR, 'detail' => 'The last check on '.$lastCheck->started_at->toDateString().' found altered or missing entries'],
            default => ['status' => self::OK, 'detail' => 'Hash chains intact at '.$lastCheck->started_at->format('Y-m-d H:i')],
        };
    }

    /**
     * @return array{status: string, detail: string}
     */
    protected function configuration(): array
    {
        $problems = array_keys(array_filter([
            'APP_DEBUG is on in production' => app()->isProduction() && config('app.debug'),
            'APP_KEY is not set' => blank(config('app.key')),
            'APP_URL is not HTTPS in production' => app()->isProduction() && ! str_starts_with((string) config('app.url'), 'https://'),
            'mail is only written to the log in production' => app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true),
        ]));

        return $problems === []
            ? ['status' => self::OK, 'detail' => app()->environment().' environment'.(config('app.debug') ? ' · debug on' : '')]
            : ['status' => app()->isProduction() ? self::ERROR : self::WARNING, 'detail' => implode('; ', $problems)];
    }

    /**
     * @return array{status: string, detail: string}
     */
    protected function extensions(): array
    {
        $missing = array_values(array_filter(self::REQUIRED_EXTENSIONS, fn (string $extension) => ! extension_loaded($extension)));

        return $missing === []
            ? ['status' => self::OK, 'detail' => 'PHP '.PHP_VERSION.' with '.implode(', ', self::REQUIRED_EXTENSIONS)]
            : ['status' => self::ERROR, 'detail' => 'Missing: '.implode(', ', $missing)];
    }
}
