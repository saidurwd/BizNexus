<?php

namespace Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Finance\Models\Budget;

class StoreBudgetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:' . implode(',', [
                Budget::STATUS_DRAFT,
                Budget::STATUS_SUBMITTED,
                Budget::STATUS_APPROVED,
                Budget::STATUS_REJECTED,
            ]),
        ];
    }
}
