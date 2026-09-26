<?php

namespace Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Requests\Concerns\ValidatesDocumentLines;

/**
 * Creating or updating a customer invoice with its lines. The number is optional: a blank one is issued from the
 * company's CI sequence.
 */
class StoreCustomerInvoiceRequest extends FormRequest
{
    use ValidatesDocumentLines;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $companyId = (int) app(CompanyContextService::class)->getActiveCompanyId();

        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'invoice_number' => ['nullable', 'string', 'max:50', Rule::unique('customer_invoices', 'invoice_number')->where('company_id', $companyId)->ignore($this->route('id'))],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency_id' => ['nullable', Rule::exists('currencies', 'id')],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            ...$this->documentLineRules($companyId),
            'lines.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'lines.*.warehouse_id' => ['nullable', 'required_with:lines.*.product_id', Rule::exists('warehouses', 'id')->where('company_id', $companyId)->where('status', 'active')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->documentLineAttributes();
    }
}
