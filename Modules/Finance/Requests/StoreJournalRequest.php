<?php

namespace Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Finance\Exceptions\UnbalancedJournalException;

class StoreJournalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'journal_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
            'lines.*.department_id' => 'nullable|exists:departments,id',
            'lines.*.branch_id' => 'nullable|exists:branches,id',
            'lines.*.project_id' => 'nullable|exists:projects,id',
            'lines.*.reference' => 'nullable|string|max:100',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);

            if (count($lines) < 2) {
                $validator->errors()->add('lines', 'A journal must have at least 2 lines.');
                return;
            }

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $index => $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("lines.{$index}.debit", 'A line cannot have both debit and credit.');
                }

                if ($debit === 0 && $credit === 0) {
                    $validator->errors()->add("lines.{$index}.debit", 'A line must have either debit or credit.');
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $validator->errors()->add('lines', "Journal is unbalanced. Total Debit: {$totalDebit}, Total Credit: {$totalCredit}");
            }
        });
    }
}
