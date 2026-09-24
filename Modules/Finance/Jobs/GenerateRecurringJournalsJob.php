<?php

namespace Modules\Finance\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Scopes\CompanyScope;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Finance\Models\RecurringJournal;
use Modules\Finance\Services\JournalService;

class GenerateRecurringJournalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(JournalService $journalService, DocumentNumberService $documentNumber): void
    {
        $today = now()->toDateString();

        $recurringJournals = RecurringJournal::withoutGlobalScope(CompanyScope::class)
            ->where('status', 'active')
            ->where('next_run_date', '<=', $today)
            ->with('company')
            ->get();

        foreach ($recurringJournals as $recurring) {
            app(CompanyContextService::class)->runAs(
                $recurring->company_id,
                fn () => $this->processRecurringJournal($recurring, $journalService, $documentNumber)
            );
        }
    }

    protected function processRecurringJournal(RecurringJournal $recurring, JournalService $journalService, DocumentNumberService $documentNumber): void
    {
        $lines = $recurring->lines ?? [];

        if (empty($lines)) {
            return;
        }

        $journal = $journalService->create([
            'company_id' => $recurring->company_id,
            'journal_date' => $recurring->next_run_date,
            'description' => $recurring->description ?? "Recurring: {$recurring->name}",
            'lines' => $lines,
            'created_by' => Auth::id(),
        ]);

        $nextRunDate = $this->calculateNextRunDate($recurring->next_run_date, $recurring->frequency);

        $recurring->update([
            'next_run_date' => $nextRunDate,
            'updated_by' => Auth::id(),
        ]);
    }

    protected function calculateNextRunDate(string $currentDate, string $frequency): string
    {
        return match ($frequency) {
            'DAILY' => Carbon::parse($currentDate)->addDay()->toDateString(),
            'WEEKLY' => Carbon::parse($currentDate)->addWeek()->toDateString(),
            'MONTHLY' => Carbon::parse($currentDate)->addMonth()->toDateString(),
            'QUARTERLY' => Carbon::parse($currentDate)->addMonths(3)->toDateString(),
            'YEARLY' => Carbon::parse($currentDate)->addYear()->toDateString(),
            default => Carbon::parse($currentDate)->addMonth()->toDateString(),
        };
    }
}
