<?php

namespace Tests\Unit\Finance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Exceptions\DuplicatePostingException;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Exceptions\UnbalancedJournalException;
use Modules\Core\Models\BusinessUnit;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Services\JournalService;
use Tests\TestCase;

class JournalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JournalService $journalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalService = app(JournalService::class);
    }

    public function test_can_create_journal(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id, 'account_code' => '1110']);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id, 'account_code' => '4110']);

        $journalData = [
            'company_id' => $company->id,
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Test Journal Entry',
            'lines' => [
                ['account_id' => $cashAccount->id, 'description' => 'Cash received', 'debit' => 1000, 'credit' => 0],
                ['account_id' => $revenueAccount->id, 'description' => 'Revenue', 'debit' => 0, 'credit' => 1000],
            ],
        ];

        $journal = $this->journalService->create($journalData);

        $this->assertNotNull($journal);
        $this->assertEquals(Journal::STATUS_DRAFT, $journal->status);
        $this->assertEquals(1000, $journal->total_debit);
        $this->assertEquals(1000, $journal->total_credit);
        $this->assertEquals(2, $journal->lines()->count());
    }

    public function test_journal_must_be_balanced(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id]);

        $journalData = [
            'company_id' => $company->id,
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Unbalanced Journal',
            'lines' => [
                ['account_id' => $cashAccount->id, 'description' => 'Debit', 'debit' => 1000, 'credit' => 0],
                ['account_id' => $revenueAccount->id, 'description' => 'Credit', 'debit' => 0, 'credit' => 500],
            ],
        ];

        $this->expectException(UnbalancedJournalException::class);

        $this->journalService->create($journalData);
    }

    public function test_journal_must_have_at_least_two_lines(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);

        $journalData = [
            'company_id' => $company->id,
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Single Line Journal',
            'lines' => [
                ['account_id' => $cashAccount->id, 'description' => 'Only one line', 'debit' => 1000, 'credit' => 0],
            ],
        ];

        $this->expectException(InvalidAccountingTransactionException::class);

        $this->journalService->create($journalData);
    }

    public function test_can_add_line_to_draft_journal(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id]);
        $expenseAccount = Account::factory()->expense()->create(['company_id' => $company->id]);

        $journal = Journal::factory()->create([
            'company_id' => $company->id,
            'total_debit' => 1000,
            'total_credit' => 1000,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $line = $this->journalService->addLine($journal, [
            'account_id' => $expenseAccount->id,
            'description' => 'New expense line',
            'debit' => 200,
            'credit' => 0,
        ]);

        $this->assertNotNull($line);
        $this->assertEquals(3, $journal->fresh()->lines()->count());
    }

    public function test_cannot_add_line_to_posted_journal(): void
    {
        $journal = Journal::factory()->posted()->create();

        $expenseAccount = Account::factory()->expense()->create(['company_id' => $journal->company_id]);

        $this->expectException(InvalidAccountingTransactionException::class);

        $this->journalService->addLine($journal, [
            'account_id' => $expenseAccount->id,
            'description' => 'Should fail',
            'debit' => 100,
            'credit' => 0,
        ]);
    }

    public function test_can_submit_draft_journal(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id]);

        $journal = Journal::factory()->create([
            'company_id' => $company->id,
            'status' => Journal::STATUS_DRAFT,
            'total_debit' => 1000,
            'total_credit' => 1000,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $journal = $this->journalService->submit($journal);

        $this->assertEquals(Journal::STATUS_SUBMITTED, $journal->status);
    }

    public function test_can_approve_submitted_journal(): void
    {
        $journal = Journal::factory()->submitted()->create();

        $journal = $this->journalService->approve($journal);

        $this->assertEquals(Journal::STATUS_APPROVED, $journal->status);
    }

    public function test_cannot_approve_draft_journal(): void
    {
        $journal = Journal::factory()->draft()->create();

        $this->expectException(InvalidAccountingTransactionException::class);

        $this->journalService->approve($journal);
    }

    public function test_cannot_post_journal_twice(): void
    {
        $journal = Journal::factory()->posted()->create();
        app(CompanyContextService::class)->pinCompany($journal->company_id);

        $this->expectException(DuplicatePostingException::class);

        $this->journalService->post($journal);
    }

    public function test_can_reverse_posted_journal(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id]);

        $journal = Journal::factory()->create([
            'company_id' => $company->id,
            'status' => Journal::STATUS_POSTED,
            'total_debit' => 1000,
            'total_credit' => 1000,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $this->openCurrentPeriod($company);

        $reversal = $this->journalService->reverse($journal, 'Correction');

        $this->assertEquals(Journal::STATUS_REVERSED, $journal->fresh()->status);
        $this->assertEquals(Journal::STATUS_POSTED, $reversal->status);
        $this->assertEquals($journal->id, $reversal->reversal_of_journal_id);
    }

    public function test_cannot_reverse_reversed_journal(): void
    {
        $journal = Journal::factory()->create([
            'status' => Journal::STATUS_REVERSED,
            'total_debit' => 1000,
            'total_credit' => 1000,
        ]);
        app(CompanyContextService::class)->pinCompany($journal->company_id);

        $this->expectException(InvalidAccountingTransactionException::class);

        $this->journalService->reverse($journal);
    }

    public function test_journal_line_includes_business_unit_id(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id]);
        $businessUnit = BusinessUnit::factory()->create(['company_id' => $company->id]);

        $journalData = [
            'company_id' => $company->id,
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Test with business unit',
            'lines' => [
                [
                    'account_id' => $cashAccount->id,
                    'description' => 'Cash received',
                    'debit' => 1000,
                    'credit' => 0,
                    'business_unit_id' => $businessUnit->id,
                ],
                ['account_id' => $revenueAccount->id, 'description' => 'Revenue', 'debit' => 0, 'credit' => 1000],
            ],
        ];

        $journal = $this->journalService->create($journalData);
        $line = $journal->lines()->first();

        $this->assertEquals($businessUnit->id, $line->business_unit_id);
    }

    public function test_can_update_line_in_draft_journal(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id]);

        $journal = Journal::factory()->create([
            'company_id' => $company->id,
            'status' => Journal::STATUS_DRAFT,
        ]);

        $line = JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $this->journalService->updateLine($line, [
            'account_id' => $revenueAccount->id,
            'debit' => 500,
            'credit' => 0,
        ]);

        $this->assertEquals($revenueAccount->id, $line->fresh()->account_id);
        $this->assertEquals(500, $line->fresh()->debit);
    }

    public function test_can_remove_line_from_draft_journal(): void
    {
        $company = Company::factory()->create();
        app(CompanyContextService::class)->pinCompany($company->id);
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id]);

        $journal = Journal::factory()->create([
            'company_id' => $company->id,
            'status' => Journal::STATUS_DRAFT,
        ]);

        $line1 = JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $line2 = JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $this->journalService->removeLine($line1);

        $this->assertEquals(1, $journal->fresh()->lines()->count());
    }

    protected function openCurrentPeriod(Company $company): FiscalPeriod
    {
        $fiscalYear = FiscalYear::create([
            'company_id' => $company->id,
            'name' => 'FY'.now()->year,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 'OPEN',
        ]);

        return FiscalPeriod::create([
            'fiscal_year_id' => $fiscalYear->id,
            'period_name' => now()->format('F'),
            'period_number' => now()->month,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'OPEN',
        ]);
    }
}
