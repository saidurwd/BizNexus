<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Modules\Finance\Jobs\GenerateRecurringJournalsJob;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new GenerateRecurringJournalsJob())
            ->daily()
            ->at('00:00')
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        if (file_exists(base_path('routes/console.php'))) {
            require base_path('routes/console.php');
        }
    }
}
