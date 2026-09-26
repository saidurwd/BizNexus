<?php

namespace Modules\Finance\Jobs;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Core\Scopes\CompanyScope;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\RecurringJournal;
use Modules\Finance\Services\JournalService;

class GenerateRecurringJournalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Most runs generated for one template in a single pass, so a long outage cannot flood the ledger.
     */
    public const MAX_CATCH_UP_RUNS = 36;

    /**
     * Create a draft journal for every run that has fallen due, in each company's own business date, catching up
     * runs missed while the scheduler was down. Each run is created and advanced in one transaction, so a failure
     * never leaves a journal without moving the next run date.
     */
    public function handle(JournalService $journalService): void
    {
        $recurringJournals = RecurringJournal::withoutGlobalScope(CompanyScope::class)
            ->where('status', 'active')
            ->whereNotNull('next_run_date')
            ->get();

        foreach ($recurringJournals as $recurring) {
            app(CompanyContextService::class)->runAs($recurring->company_id, function () use ($recurring, $journalService) {
                $today = app(CompanyContextService::class)->today();

                for ($run = 0; $run < self::MAX_CATCH_UP_RUNS && $recurring->next_run_date->lte($today); $run++) {
                    $this->generateRun($recurring, $journalService);
                }
            });
        }
    }

    protected function generateRun(RecurringJournal $recurring, JournalService $journalService): void
    {
        if (empty($recurring->lines)) {
            return;
        }

        DB::transaction(function () use ($recurring, $journalService) {
            $journalService->create([
                'company_id' => $recurring->company_id,
                'journal_date' => $recurring->next_run_date->toDateString(),
                'description' => $recurring->description ?? "Recurring: {$recurring->name}",
                'lines' => $recurring->lines,
                'created_by' => $recurring->created_by,
            ]);

            $recurring->update(['next_run_date' => $this->nextRunDate($recurring->next_run_date, $recurring->frequency)]);
        });
    }

    /**
     * The following run, never overflowing into the month after (31 January is followed by 28/29 February).
     */
    protected function nextRunDate(CarbonInterface $current, string $frequency): string
    {
        $current = Carbon::parse($current);

        $next = match (strtoupper($frequency)) {
            'DAILY' => $current->addDay(),
            'WEEKLY' => $current->addWeek(),
            'QUARTERLY' => $current->addMonthsNoOverflow(3),
            'YEARLY' => $current->addYearNoOverflow(),
            default => $current->addMonthNoOverflow(),
        };

        return $next->toDateString();
    }
}
