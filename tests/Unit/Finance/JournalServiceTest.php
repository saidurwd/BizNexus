<?php

namespace Tests\Unit\Finance;

use Tests\TestCase;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Account;
use Modules\Finance\Services\JournalService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\AccountingPeriodService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Services\AuditService;
use Modules\Core\Exceptions\UnbalancedJournalException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JournalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JournalService $journalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalService = new JournalService(
            new CompanyContextService(),
            new AccountingPeriodService(),
            new DocumentNumberService(),
            new AuditService()
        );
    }

    public function test_can_create_journal(): void
    {
        $company = \Modules\Core\Models\Company::factory()->create();
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
        $company = \Modules\Core\Models\Company::factory()->create();
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
        $company = \Modules\Core\Models\Company::factory()->create();
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);

        $journalData = [
            'company_id' => $company->id,
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Single Line Journal',
            'lines' => [
                ['account_id' => $cashAccount->id, 'description' => 'Only one line', 'debit' => 1000, 'credit' => 0],
            ],
        ];

        $this->expectException(\Modules\Core\Exceptions\InvalidAccountingTransactionException::class);

        $this->journalService->create($journalData);
    }

    public function test_can_add_line_to_draft_journal(): void
    {
        $company = \Modules\Core\Models\Company::factory()->create();
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

        $this->expectException(\Modules\Core\Exceptions\InvalidAccountingTransactionException::class);

        $this->journalService->addLine($journal, [
            'account_id' => $expenseAccount->id,
            'description' => 'Should fail',
            'debit' => 100,
            'credit' => 0,
        ]);
    }

    public function test_can_submit_draft_journal(): void
    {
        $company = \Modules\Core\Models\Company::factory()->create();
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

        $this->expectException(\Modules\Core\Exceptions\InvalidAccountingTransactionException::class);

        $this->journalService->approve($journal);
    }

    public function test_cannot_post_journal_twice(): void
    {
        $journal = Journal::factory()->posted()->create();

        $this->expectException(\Modules\Core\Exceptions\InvalidAccountingTransactionException::class);

        $this->journalService->post($journal);
    }

    public function test_can_reverse_posted_journal(): void
    {
        $company = \Modules\Core\Models\Company::factory()->create();
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

        $reversal = $this->journalService->reverse($journal, 'Correction');

        $this->assertEquals(Journal::STATUS_REVERSED, $journal->fresh()->status);
        $this->assertNotNull($reversal);
        $this->assertEquals($journal->id, $reversal->reversal_of_journal_id);
    }
}
