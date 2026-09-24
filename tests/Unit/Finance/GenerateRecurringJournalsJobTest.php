<?php

namespace Tests\Unit\Finance;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DocumentNumberService;
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
        $job->handle(app(JournalService::class), app(DocumentNumberService::class));

        $this->assertEquals(0, Journal::count());
    }
}
