<?php

namespace Modules\Finance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Requests\Concerns\ValidatesDocumentLines;

/**
 * Creating or updating a supplier credit note. It may credit one posted invoice of the same supplier; a blank
 * number is issued from the company's SCN sequence.
 */
class StoreSupplierCreditNoteRequest extends FormRequest
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
            'supplier_invoice_id' => ['nullable', Rule::exists('supplier_invoices', 'id')
                ->where('company_id', $companyId)
                ->where('supplier_id', (int) $this->input('supplier_id'))
                ->whereIn('status', [SupplierInvoice::STATUS_POSTED, SupplierInvoice::STATUS_PARTIALLY_PAID, SupplierInvoice::STATUS_PAID])],
            'credit_note_number' => ['nullable', 'string', 'max:50', Rule::unique('supplier_credit_notes', 'credit_note_number')->where('company_id', $companyId)->ignore($this->route('id'))],
            'credit_note_date' => ['required', 'date'],
            'currency_id' => ['nullable', Rule::exists('currencies', 'id')],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
            ...$this->documentLineRules($companyId),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['supplier_invoice_id' => __('credited invoice'), ...$this->documentLineAttributes()];
    }
}
