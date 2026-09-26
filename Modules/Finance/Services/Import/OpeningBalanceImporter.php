<?php

namespace Modules\Finance\Services\Import;

use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Services\JournalService;

/**
 * Opening trial balance when moving from another system: one draft journal, dated the day before the first
 * period kept here, which is then approved and posted like any other journal. Debits must equal credits.
 */
class OpeningBalanceImporter implements Importer
{
    public function __construct(protected JournalService $journals) {}

    public function label(): string
    {
        return __('Opening balances');
    }

    public function columns(): array
    {
        return [
            'account_code' => [true, __('A postable account')],
            'debit' => [false, __('Debit balance in the company currency')],
            'credit' => [false, __('Credit balance in the company currency')],
            'description' => [false, __('Line description')],
        ];
    }

    public function example(): array
    {
        return [
            ['account_code' => '1100', 'debit' => '25000.00', 'credit' => '', 'description' => 'Bank balance at cut-over'],
            ['account_code' => '3000', 'debit' => '', 'credit' => '25000.00', 'description' => 'Opening equity'],
        ];
    }

    public function validate(array $rows, array $options): array
    {
        $accounts = Account::pluck('is_postable', 'account_code');
        $debits = '0';
        $credits = '0';

        $checked = array_map(function (array $row) use ($accounts, &$debits, &$credits) {
            $data = $row['data'];
            $code = $data['account_code'] ?? '';
            $debit = $this->amount($data['debit'] ?? '');
            $credit = $this->amount($data['credit'] ?? '');

            $errors = array_keys(array_filter([
                __('Account :code does not exist.', ['code' => $code]) => ! $accounts->has($code),
                __('Account :code is a heading account and cannot hold a balance.', ['code' => $code]) => $accounts->has($code) && ! $accounts->get($code),
                __('Debit and credit must be numbers of zero or more.') => $debit === null || $credit === null,
                __('Give either a debit or a credit, not both.') => $debit !== null && $credit !== null && bccomp($debit, '0', 4) > 0 && bccomp($credit, '0', 4) > 0,
                __('The line has no amount.') => $debit !== null && $credit !== null && bccomp($debit, '0', 4) === 0 && bccomp($credit, '0', 4) === 0,
            ]));

            if ($errors === []) {
                $debits = bcadd($debits, $debit, 4);
                $credits = bcadd($credits, $credit, 4);
            }

            return [...$row, 'action' => 'create', 'errors' => $errors];
        }, $rows);

        $fileErrors = array_keys(array_filter([
            __('Choose the opening balance date.') => empty($options['date']),
            __('Debits (:debits) do not equal credits (:credits).', ['debits' => $debits, 'credits' => $credits]) => bccomp($debits, $credits, 4) !== 0,
            __('At least two lines are needed.') => count($rows) < 2,
        ]));

        return ['rows' => $checked, 'errors' => $fileErrors];
    }

    public function import(array $rows, array $options): string
    {
        $accounts = Account::pluck('id', 'account_code');

        $journal = $this->journals->create([
            'company_id' => (int) app(CompanyContextService::class)->getActiveCompanyId(),
            'journal_date' => $options['date'],
            'reference_type' => 'opening_balance',
            'description' => __('Opening balances at :date', ['date' => $options['date']]),
            'lines' => array_map(fn (array $row) => [
                'account_id' => $accounts->get($row['data']['account_code']),
                'description' => ($row['data']['description'] ?? '') ?: __('Opening balance'),
                'debit' => $this->amount($row['data']['debit'] ?? '') ?? '0',
                'credit' => $this->amount($row['data']['credit'] ?? '') ?? '0',
            ], $rows),
        ]);

        return __('Draft journal :number created with :count lines. Submit it for approval and post it.', ['number' => $journal->journal_number, 'count' => count($rows)]);
    }

    /**
     * A non-negative amount ("1,234.50" accepted), '0' when empty, or null when not a number.
     */
    protected function amount(string $value): ?string
    {
        $value = str_replace([',', ' '], '', $value);

        if ($value === '') {
            return '0';
        }

        return is_numeric($value) && (float) $value >= 0 ? bcadd($value, '0', 4) : null;
    }
}
