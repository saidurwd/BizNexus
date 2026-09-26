<?php

namespace Modules\Finance\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Line rules shared by sales and purchase documents: postable accounts and tax codes of the active company.
 */
trait ValidatesDocumentLines
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function documentLineRules(int $companyId): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $companyId)->where('is_postable', 1)->where('is_group', 0)],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
            'lines.*.is_reverse_charge' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function documentLineAttributes(): array
    {
        return [
            'lines.*.account_id' => __('account'),
            'lines.*.description' => __('description'),
            'lines.*.quantity' => __('quantity'),
            'lines.*.unit_price' => __('unit price'),
            'lines.*.tax_id' => __('tax code'),
        ];
    }
}
