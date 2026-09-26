<?php

namespace Modules\Finance\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Money;

/**
 * Journal lines for sales and purchase documents, built from the same per-line tax results the documents show.
 * A credit note is the mirror of an invoice: the same lines with debit and credit swapped.
 */
class DocumentJournalBuilder
{
    public function __construct(protected DocumentTaxService $documentTax) {}

    /**
     * Sales: revenue at net and output tax (none on reverse-charge lines) against the receivable.
     *
     * @return array{lines: list<array{account_id: int, description: string, debit: string|int, credit: string|int}>, total: Money}
     */
    public function sales(Model $document, int $receivableAccountId, string $partyDescription, bool $isCredit = false): array
    {
        $calculations = $this->documentTax->calculations($document);
        $receivable = Money::zero($this->currencyCode($document));
        $lines = [];

        foreach ($document->lines as $line) {
            $calculation = $calculations[$line->id];
            $lines[] = $this->line($line->account_id, $line->description, credit: $calculation->net->amount);

            if (! $line->is_reverse_charge) {
                foreach ($calculation->components as $component) {
                    $account = $this->documentTax->requireAccount($component->tax->output_account_id, $component->tax->tax_code, 'output');
                    $lines[] = $this->line($account, "Output {$component->tax->tax_code} on {$line->description}", credit: $component->amount->amount);
                }
            }

            $receivable = $receivable->plus($line->is_reverse_charge ? $calculation->net : $calculation->gross());
        }

        array_unshift($lines, $this->line($receivableAccountId, $partyDescription, debit: $receivable->amount));

        return ['lines' => $isCredit ? $this->mirrored($lines) : $lines, 'total' => $receivable];
    }

    /**
     * Purchases: cost at net plus non-recoverable tax, recoverable input tax, and self-assessed output tax on
     * reverse-charge lines, against the payable.
     *
     * $costLines, when given, returns the journal lines for a line's cost (e.g. split between goods received
     * not invoiced and price variance for lines matched to a purchase order).
     *
     * @param  (callable(Model, Money): list<array<string, mixed>>)|null  $costLines
     * @return array{lines: list<array<string, mixed>>, total: Money}
     */
    public function purchase(Model $document, int $payableAccountId, string $partyDescription, bool $isCredit = false, ?callable $costLines = null): array
    {
        $calculations = $this->documentTax->calculations($document);
        $payable = Money::zero($this->currencyCode($document));
        $lines = [];

        foreach ($document->lines as $line) {
            $calculation = $calculations[$line->id];
            $cost = $calculation->net->plus($calculation->nonRecoverableTax());
            array_push($lines, ...($costLines ? $costLines($line, $cost) : [$this->line($line->account_id, $line->description, debit: $cost->amount)]));

            foreach ($calculation->components as $component) {
                if ($component->isRecoverable()) {
                    $account = $this->documentTax->requireAccount($component->tax->input_account_id, $component->tax->tax_code, 'input');
                    $lines[] = $this->line($account, "Input {$component->tax->tax_code} on {$line->description}", debit: $component->amount->amount);
                }

                if ($line->is_reverse_charge) {
                    $account = $this->documentTax->requireAccount($component->tax->output_account_id, $component->tax->tax_code, 'output');
                    $lines[] = $this->line($account, "Reverse charge {$component->tax->tax_code} on {$line->description}", credit: $component->amount->amount);
                }
            }

            $payable = $payable->plus($line->is_reverse_charge ? $calculation->net : $calculation->gross());
        }

        $lines[] = $this->line($payableAccountId, $partyDescription, credit: $payable->amount);

        return ['lines' => $isCredit ? $this->mirrored($lines) : $lines, 'total' => $payable];
    }

    /**
     * @return array{account_id: int, description: string, debit: string|int, credit: string|int}
     */
    protected function line(int $accountId, string $description, string|int $debit = 0, string|int $credit = 0): array
    {
        return ['account_id' => $accountId, 'description' => $description, 'debit' => $debit, 'credit' => $credit];
    }

    /**
     * @param  list<array{account_id: int, description: string, debit: string|int, credit: string|int}>  $lines
     * @return list<array{account_id: int, description: string, debit: string|int, credit: string|int}>
     */
    protected function mirrored(array $lines): array
    {
        return array_map(fn (array $line) => isset($line['functional_amount'])
            ? [...$line, 'functional_amount' => bcmul((string) $line['functional_amount'], '-1', 4)]
            : [...$line, 'debit' => $line['credit'], 'credit' => $line['debit']], $lines);
    }

    protected function currencyCode(Model $document): string
    {
        return $document->currency?->code ?? $document->company->baseCurrency?->code ?? 'XXX';
    }
}
