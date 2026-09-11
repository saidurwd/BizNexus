<?php

namespace Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Models\RecurringJournal;
use Modules\Finance\Models\Journal;
use Modules\Finance\Services\JournalService;
use Modules\Core\Services\DocumentNumberService;
use Illuminate\Support\Facades\Auth;

class GenerateRecurringJournalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(JournalService $journalService, DocumentNumberService $documentNumber): void
    {
        $today = now()->toDateString();

        $recurringJournals = RecurringJournal::where('status', 'active')
            ->where('next_run_date', '<=', $today)
            ->with('company')
            ->get();

        foreach ($recurringJournals as $recurring) {
            $this->processRecurringJournal($recurring, $journalService, $documentNumber);
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
            'DAILY' => \Carbon\Carbon::parse($currentDate)->addDay()->toDateString(),
            'WEEKLY' => \Carbon\Carbon::parse($currentDate)->addWeek()->toDateString(),
            'MONTHLY' => \Carbon\Carbon::parse($currentDate)->addMonth()->toDateString(),
            'QUARTERLY' => \Carbon\Carbon::parse($currentDate)->addMonths(3)->toDateString(),
            'YEARLY' => \Carbon\Carbon::parse($currentDate)->addYear()->toDateString(),
            default => \Carbon\Carbon::parse($currentDate)->addMonth()->toDateString(),
        };
    }
}
