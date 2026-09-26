<?php

namespace Modules\Finance\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Models\Currency;
use Modules\Core\Support\Countries;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\PaymentTerm;

/**
 * Validation and form data shared by the customer and supplier master data screens.
 */
trait ManagesParties
{
    /**
     * @return array<string, mixed>
     */
    protected function validatedParty(Request $request, string $table, string $codeColumn, string $controlAccountColumn, ?int $ignoreId = null): array
    {
        $companyId = $this->getActiveCompanyId();

        return $request->validate([
            $codeColumn => ['required', 'string', 'max:50', Rule::unique($table, $codeColumn)->where('company_id', $companyId)->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'country_code' => ['nullable', Rule::in(Countries::codes())],
            'address' => ['nullable', 'string', 'max:1000'],
            'currency_id' => ['nullable', Rule::exists('currencies', 'id')],
            'payment_term_id' => ['nullable', Rule::exists('payment_terms', 'id')->where('company_id', $companyId)],
            $controlAccountColumn => ['nullable', Rule::exists('accounts', 'id')->where('company_id', $companyId)->where('is_postable', 1)],
            ...($table === 'customers' ? ['credit_limit' => ['nullable', 'numeric', 'min:0']] : []),
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function partyFormData(string $controlAccountType): array
    {
        return [
            'currencies' => Currency::where('status', 'active')->orderBy('code')->get(),
            'paymentTerms' => PaymentTerm::active()->orderBy('due_days')->get(),
            'controlAccounts' => Account::postable()->active()->where('account_type', $controlAccountType)
                ->orderByDesc('is_control_account')->orderBy('account_code')->get(['id', 'account_code', 'account_name', 'is_control_account']),
        ];
    }
}
