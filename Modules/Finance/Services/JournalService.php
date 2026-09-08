<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Account;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\AccountingPeriodService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Core\Services\AuditService;
use Modules\Core\Exceptions\UnbalancedJournalException;
use Modules\Core\Exceptions\ClosedPeriodException;
use Modules\Core\Exceptions\InactiveAccountException;
use Modules\Core\Exceptions\NonPostableAccountException;
use Modules\Core\Exceptions\DuplicatePostingException;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;

class JournalService
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected AccountingPeriodService $periodService,
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit
    ) {}

    public function create(array $data): Journal
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'] ?? $this->companyContext->getCompanyId();

            $journal = Journal::create([
                'company_id' => $companyId,
                'journal_number' => $this->documentNumber->generateNumber($companyId, 'JV'),
                'journal_date' => $data['journal_date'],
                'fiscal_period_id' => $data['fiscal_period_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'currency_id' => $data['currency_id'] ?? $this->companyContext->getBaseCurrency()?->id,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'status' => Journal::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['lines'] as $lineData) {
                $this->addLine($journal, $lineData);
            }

            $journal->calculateTotals();
            $journal->save();

            $this->audit->logCreate('Finance', 'Journal', $journal->id, $journal->toArray(), $companyId);

            return $journal;
        });
    }

    public function addLine(Journal $journal, array $lineData): JournalLine
    {
        $account = Account::findOrFail($lineData['account_id']);

        if (!$account->canReceivePosting()) {
            if (!$account->isActive()) {
                throw new InactiveAccountException($account);
            }
            throw new NonPostableAccountException($account);
        }

        $line = $journal->lines()->create([
            'account_id' => $lineData['account_id'],
            'description' => $lineData['description'] ?? null,
            'debit' => $lineData['debit'] ?? 0,
            'credit' => $lineData['credit'] ?? 0,
            'currency_debit' => $lineData['currency_debit'] ?? $lineData['debit'] ?? 0,
            'currency_credit' => $lineData['currency_credit'] ?? $lineData['credit'] ?? 0,
            'cost_center_id' => $lineData['cost_center_id'] ?? null,
            'department_id' => $lineData['department_id'] ?? null,
            'branch_id' => $lineData['branch_id'] ?? null,
            'project_id' => $lineData['project_id'] ?? null,
            'tax_id' => $lineData['tax_id'] ?? null,
            'reference' => $lineData['reference'] ?? null,
        ]);

        $journal->calculateTotals();
        $journal->save();

        return $line;
    }

    public function updateLine(JournalLine $line, array $data): JournalLine
    {
        $journal = $line->journal;

        if (!$journal->isDraft()) {
            throw new InvalidAccountingTransactionException('Can only update lines in draft journals');
        }

        if (isset($data['account_id'])) {
            $account = Account::findOrFail($data['account_id']);
            if (!$account->canReceivePosting()) {
                if (!$account->isActive()) {
                    throw new InactiveAccountException($account);
                }
                throw new NonPostableAccountException($account);
            }
        }

        $line->update($data);

        $journal->calculateTotals();
        $journal->save();

        return $line;
    }

    public function removeLine(JournalLine $line): void
    {
        $journal = $line->journal;

        if (!$journal->isDraft()) {
            throw new InvalidAccountingTransactionException('Can only remove lines from draft journals');
        }

        $line->delete();

        $journal->calculateTotals();
        $journal->save();
    }

    public function validate(Journal $journal): void
    {
        if ($journal->lines()->count() < 2) {
            throw new InvalidAccountingTransactionException('Journal must have at least 2 lines');
        }

        if (!$journal->isBalanced()) {
            throw new UnbalancedJournalException($journal->total_debit, $journal->total_credit);
        }

        foreach ($journal->lines as $line) {
            if ($line->debit > 0 && $line->credit > 0) {
                throw new InvalidAccountingTransactionException('A line cannot have both debit and credit');
            }

            if ($line->debit == 0 && $line->credit == 0) {
                throw new InvalidAccountingTransactionException('A line must have either debit or credit');
            }

            $account = $line->account;
            if (!$account->canReceivePosting()) {
                if (!$account->isActive()) {
                    throw new InactiveAccountException($account);
                }
                throw new NonPostableAccountException($account);
            }
        }
    }

    public function submit(Journal $journal): Journal
    {
        if (!$journal->canSubmit()) {
            throw new InvalidAccountingTransactionException('Journal cannot be submitted');
        }

        $this->validate($journal);

        $journal->update([
            'status' => Journal::STATUS_SUBMITTED,
            'updated_by' => Auth::id(),
        ]);

        $this->audit->logCustom('Finance', 'Journal', $journal->id, 'SUBMIT', $journal->toArray());

        return $journal->fresh();
    }

    public function approve(Journal $journal): Journal
    {
        if (!$journal->canApprove()) {
            throw new InvalidAccountingTransactionException('Journal cannot be approved');
        }

        $journal->update([
            'status' => Journal::STATUS_APPROVED,
            'updated_by' => Auth::id(),
        ]);

        $this->audit->logCustom('Finance', 'Journal', $journal->id, 'APPROVE', $journal->toArray());

        return $journal->fresh();
    }

    public function reject(Journal $journal, ?string $reason = null): Journal
    {
        if (!$journal->canApprove()) {
            throw new InvalidAccountingTransactionException('Journal cannot be rejected');
        }

        $journal->update([
            'status' => Journal::STATUS_REJECTED,
            'description' => $journal->description . "\n\nRejected: " . ($reason ?? 'No reason provided'),
            'updated_by' => Auth::id(),
        ]);

        $this->audit->logCustom('Finance', 'Journal', $journal->id, 'REJECT', ['reason' => $reason]);

        return $journal->fresh();
    }

    public function post(Journal $journal): Journal
    {
        if (!$journal->canPost()) {
            throw new InvalidAccountingTransactionException('Journal cannot be posted');
        }

        if ($journal->isPosted()) {
            throw new DuplicatePostingException($journal);
        }

        return DB::transaction(function () use ($journal) {
            $journalDate = Carbon::parse($journal->journal_date);
            $period = $this->periodService->validateDateForPosting($journal->company_id, $journalDate);

            $this->validate($journal);

            $journal->update([
                'fiscal_period_id' => $period->id,
                'posting_date' => now()->toDateString(),
                'status' => Journal::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->audit->logCustom('Finance', 'Journal', $journal->id, 'POST', $journal->toArray());

            return $journal->fresh();
        });
    }

    public function reverse(Journal $journal, ?string $reason = null): Journal
    {
        if (!$journal->canReverse()) {
            throw new InvalidAccountingTransactionException('Journal cannot be reversed');
        }

        return DB::transaction(function () use ($journal, $reason) {
            $reversal = $journal->replicate();
            $reversal->journal_number = $this->documentNumber->generateNumber($journal->company_id, 'JV');
            $reversal->journal_date = now()->toDateString();
            $reversal->posting_date = null;
            $reversal->fiscal_period_id = null;
            $reversal->status = Journal::STATUS_DRAFT;
            $reversal->posted_at = null;
            $reversal->posted_by = null;
            $reversal->reversal_of_journal_id = $journal->id;
            $reversal->reversal_reason = $reason;
            $reversal->reversed_at = now();
            $reversal->reversed_by = Auth::id();
            $reversal->description = "Reversal of {$journal->journal_number}" . ($reason ? ": {$reason}" : '');
            $reversal->save();

            foreach ($journal->lines as $line) {
                $reversal->lines()->create([
                    'account_id' => $line->account_id,
                    'description' => $line->description,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'currency_debit' => $line->currency_credit,
                    'currency_credit' => $line->currency_debit,
                    'cost_center_id' => $line->cost_center_id,
                    'department_id' => $line->department_id,
                    'branch_id' => $line->branch_id,
                    'project_id' => $line->project_id,
                    'tax_id' => $line->tax_id,
                    'reference' => $line->reference,
                ]);
            }

            $reversal->calculateTotals();
            $reversal->save();

            $journal->update([
                'status' => Journal::STATUS_REVERSED,
                'updated_by' => Auth::id(),
            ]);

            $this->audit->logCustom('Finance', 'Journal', $journal->id, 'REVERSE', [
                'reversal_journal_id' => $reversal->id,
                'reason' => $reason,
            ]);

            return $reversal;
        });
    }

    public function cancel(Journal $journal): Journal
    {
        if (!$journal->canCancel()) {
            throw new InvalidAccountingTransactionException('Journal cannot be cancelled');
        }

        $journal->update([
            'status' => Journal::STATUS_CANCELLED,
            'updated_by' => Auth::id(),
        ]);

        $this->audit->logCustom('Finance', 'Journal', $journal->id, 'CANCEL', []);

        return $journal->fresh();
    }

    public function update(Journal $journal, array $data): Journal
    {
        if (!$journal->isDraft()) {
            throw new InvalidAccountingTransactionException('Can only update draft journals');
        }

        $journal->update(array_merge($data, [
            'updated_by' => Auth::id(),
        ]));

        return $journal->fresh();
    }

    public function delete(Journal $journal): void
    {
        if (!$journal->isDraft() && $journal->status !== Journal::STATUS_CANCELLED) {
            throw new InvalidAccountingTransactionException('Posted journals cannot be deleted');
        }

        $journal->lines()->delete();
        $journal->delete();

        $this->audit->logDelete('Finance', 'Journal', $journal->id, $journal->toArray());
    }
}
