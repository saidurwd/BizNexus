<?php

namespace Modules\Core\Support;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Core\Models\ActivityLog;
use Modules\Core\Models\LoginHistory;
use Modules\Core\Models\ScheduledTaskRun;
use Modules\Core\Models\SecurityEvent;
use Modules\Finance\Jobs\GenerateRecurringJournalsJob;

/**
 * Every scheduled task, in one place so the scheduler (routes/console.php) and the Scheduled Jobs screen see the
 * same list. Web requests never load routes/console.php.
 */
class SystemSchedule
{
    public const RECURRING_JOURNALS = 'Generate recurring journals';

    public const VERIFY_AUDIT_LOG = 'Verify audit log hash chains';

    public const PRUNE_LOGS = 'Prune old security, activity and task logs';

    public const PRUNE_FAILED_JOBS = 'Prune failed jobs older than 30 days';

    public const ASSET_DEPRECIATION = 'Post last month\'s fixed asset depreciation';

    public static function define(Schedule $schedule): void
    {
        $schedule->job(new GenerateRecurringJournalsJob)->hourly()->withoutOverlapping()->name(self::RECURRING_JOURNALS);

        $schedule->command('audit:verify')->dailyAt('02:00')->withoutOverlapping()->name(self::VERIFY_AUDIT_LOG);

        $schedule->command('model:prune', ['--model' => [LoginHistory::class, SecurityEvent::class, ActivityLog::class, ScheduledTaskRun::class]])
            ->dailyAt('03:00')->name(self::PRUNE_LOGS);

        $schedule->command('queue:prune-failed', ['--hours' => 24 * 30])->dailyAt('03:30')->name(self::PRUNE_FAILED_JOBS);

        if (config('assets.auto_depreciation')) {
            $schedule->command('assets:depreciate')->monthlyOn(1, '04:00')->withoutOverlapping()->name(self::ASSET_DEPRECIATION);
        }
    }
}
