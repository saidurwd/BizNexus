<?php

namespace Tests\Unit\Finance;

use Tests\TestCase;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledgerService = new LedgerService();
    }

    public function test_trial_balance_must_balance(): void
    {
        $company = \Modules\Core\Models\Company::factory()->create();

        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id, 'account_code' => '1110']);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id, 'account_code' => '4110']);
        $expenseAccount = Account::factory()->expense()->create(['company_id' => $company->id, 'account_code' => '5110']);

        $journal = Journal::factory()->posted()->create([
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

        $trialBalance = $this->ledgerService->getTrialBalance($company->id);

        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertEquals(0, bccomp($trialBalance['difference'], '0', 4));
    }

    public function test_account_statement_shows_correct_balance(): void
    {
        $company = \Modules\Core\Models\Company::factory()->create();
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id]);

        $journal = Journal::factory()->posted()->create([
            'company_id' => $company->id,
            'total_debit' => 5000,
            'total_credit' => 5000,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'debit' => 5000,
            'credit' => 0,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 5000,
        ]);

        $statement = $this->ledgerService->getAccountStatement($cashAccount->id);

        $this->assertEquals(5000, $statement['total_debit']);
        $this->assertEquals(0, $statement['total_credit']);
        $this->assertEquals(5000, $statement['closing_balance']);
    }

    public function test_general_ledger_groups_by_account(): void
    {
        $company = \Modules\Core\Models\Company::factory()->create();
        $cashAccount = Account::factory()->asset()->create(['company_id' => $company->id]);
        $revenueAccount = Account::factory()->revenue()->create(['company_id' => $company->id]);

        $journal = Journal::factory()->posted()->create([
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

        $ledger = $this->ledgerService->getGeneralLedger($company->id);

        $this->assertCount(2, $ledger['accounts']);
    }
}
