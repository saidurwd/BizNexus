<?php

namespace Tests\Unit\Finance;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Support\SystemSchedule;
use Modules\Finance\Jobs\GenerateRecurringJournalsJob;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\RecurringJournal;
use Modules\Finance\Services\JournalService;
use Tests\TestCase;

class GenerateRecurringJournalsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_journal_job_is_dispatched(): void
    {
        Bus::fake();

        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $account = Account::factory()->asset()->create(['company_id' => $company->id]);

        RecurringJournal::create([
            'company_id' => $company->id,
            'name' => 'Monthly Rent',
            'frequency' => 'MONTHLY',
            'next_run_date' => Carbon::yesterday()->toDateString(),
            'lines' => [
                [
                    'account_id' => $account->id,
                    'description' => 'Rent expense',
                    'debit' => 5000,
                    'credit' => 0,
                ],
            ],
            'status' => 'active',
        ]);

        GenerateRecurringJournalsJob::dispatch();

        Bus::assertDispatched(GenerateRecurringJournalsJob::class);
    }

    public function test_recurring_journal_with_future_date_is_not_processed(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $account = Account::factory()->asset()->create(['company_id' => $company->id]);

        RecurringJournal::create([
            'company_id' => $company->id,
            'name' => 'Future Journal',
            'frequency' => 'MONTHLY',
            'next_run_date' => Carbon::tomorrow()->toDateString(),
            'lines' => [
                [
                    'account_id' => $account->id,
                    'description' => 'Future expense',
                    'debit' => 1000,
                    'credit' => 0,
                ],
            ],
            'status' => 'active',
        ]);

        $job = new GenerateRecurringJournalsJob;
        $job->handle(app(JournalService::class));

        $this->assertEquals(0, Journal::count());
    }

    public function test_missed_runs_are_caught_up_as_drafts_without_overflowing_month_ends(): void
    {
        Carbon::setTestNow('2026-03-15 10:00:00');
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $rent = Account::factory()->expense()->create(['company_id' => $company->id]);
        $bank = Account::factory()->asset()->create(['company_id' => $company->id]);

        $recurring = RecurringJournal::create([
            'company_id' => $company->id,
            'name' => 'Monthly Rent',
            'frequency' => 'MONTHLY',
            'next_run_date' => '2026-01-31',
            'lines' => [
                ['account_id' => $rent->id, 'description' => 'Rent', 'debit' => 5000, 'credit' => 0],
                ['account_id' => $bank->id, 'description' => 'Rent', 'debit' => 0, 'credit' => 5000],
            ],
            'status' => 'active',
        ]);

        (new GenerateRecurringJournalsJob)->handle(app(JournalService::class));

        $journals = Journal::orderBy('journal_date')->get();
        $this->assertSame(['2026-01-31', '2026-02-28'], $journals->map(fn (Journal $journal) => $journal->journal_date->toDateString())->all());
        $this->assertTrue($journals->every(fn (Journal $journal) => $journal->status === Journal::STATUS_DRAFT));
        $this->assertSame('2026-03-28', $recurring->fresh()->next_run_date->toDateString());
    }

    public function test_the_scheduler_runs_recurring_journals(): void
    {
        $events = collect(app(Schedule::class)->events());

        $this->assertTrue($events->contains(fn ($event) => $event->description === SystemSchedule::RECURRING_JOURNALS));
    }
}
