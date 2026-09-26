<?php

namespace Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Requests\Concerns\ValidatesDocumentLines;

/**
 * Recording or updating a supplier invoice with its lines. The number is the supplier's own, unique per supplier.
 */
class StoreSupplierInvoiceRequest extends FormRequest
{
    use ValidatesDocumentLines;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $companyId = (int) app(CompanyContextService::class)->getActiveCompanyId();

        return [
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'invoice_number' => ['required', 'string', 'max:50', Rule::unique('supplier_invoices', 'invoice_number')
                ->where('company_id', $companyId)
                ->where('supplier_id', (int) $this->input('supplier_id'))
                ->ignore($this->route('id'))],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency_id' => ['nullable', Rule::exists('currencies', 'id')],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            ...$this->documentLineRules($companyId),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['invoice_number' => __("supplier's invoice number"), ...$this->documentLineAttributes()];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['invoice_number.unique' => __('This supplier invoice number is already recorded for this supplier.')];
    }
}
