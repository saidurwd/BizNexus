<?php

namespace Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Requests\Concerns\ValidatesDocumentLines;

/**
 * Creating or updating a customer credit note. It may credit one posted invoice of the same customer; a blank
 * number is issued from the company's CN sequence.
 */
class StoreCustomerCreditNoteRequest extends FormRequest
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
            'customer_invoice_id' => ['nullable', Rule::exists('customer_invoices', 'id')
                ->where('company_id', $companyId)
                ->where('customer_id', (int) $this->input('customer_id'))
                ->whereIn('status', [CustomerInvoice::STATUS_POSTED, CustomerInvoice::STATUS_PARTIALLY_PAID, CustomerInvoice::STATUS_PAID])],
            'note_number' => ['nullable', 'string', 'max:50', Rule::unique('customer_credit_notes', 'note_number')->where('company_id', $companyId)->ignore($this->route('id'))],
            'note_date' => ['required', 'date'],
            'currency_id' => ['nullable', Rule::exists('currencies', 'id')],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:1000'],
            ...$this->documentLineRules($companyId),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['customer_invoice_id' => __('credited invoice'), 'description' => __('reason'), ...$this->documentLineAttributes()];
    }
}
