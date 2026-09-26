<?php

namespace Modules\Finance\Services\Import;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\Currency;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Support\Countries;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\PaymentTerm;

/**
 * Customers or suppliers, matched by code: new codes are created, existing ones updated with the file's values.
 */
abstract class PartyImporter implements Importer
{
    /**
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    abstract protected function codeColumn(): string;

    abstract protected function controlAccountColumn(): string;

    abstract protected function controlAccountType(): string;

    public function columns(): array
    {
        return [
            'code' => [true, __('Unique code')],
            'name' => [true, __('Name')],
            'contact_person' => [false, ''],
            'email' => [false, ''],
            'phone' => [false, ''],
            'tax_number' => [false, __('VAT, GST or TIN')],
            'country_code' => [false, __('ISO 3166 two-letter code, e.g. DE')],
            'address' => [false, ''],
            'currency_code' => [false, __('ISO 4217 code, e.g. EUR')],
            'payment_term_code' => [false, __('Code from Payment Terms, e.g. NET30')],
            'control_account_code' => [false, __('Receivable or payable account code')],
            'status' => [false, __('active or inactive (default active)')],
        ];
    }

    public function validate(array $rows, array $options): array
    {
        $model = $this->model();
        $existing = $model::pluck('id', $this->codeColumn());
        $currencies = Currency::pluck('id', 'code');
        $terms = PaymentTerm::pluck('id', 'code');
        $accounts = Account::postable()->where('account_type', $this->controlAccountType())->pluck('id', 'account_code');
        $countries = array_flip(Countries::codes());
        $seen = [];

        $checked = array_map(function (array $row) use ($existing, $currencies, $terms, $accounts, $countries, &$seen) {
            $data = $row['data'];
            $code = $data['code'] ?? '';
            $country = strtoupper($data['country_code'] ?? '');
            $currency = strtoupper($data['currency_code'] ?? '');
            $term = strtoupper($data['payment_term_code'] ?? '');
            $account = $data['control_account_code'] ?? '';

            $errors = array_keys(array_filter([
                __('Code is missing.') => $code === '',
                __('Name is missing.') => ($data['name'] ?? '') === '',
                __('Code :code appears more than once in the file.', ['code' => $code]) => isset($seen[$code]),
                __('Email address is not valid.') => ($data['email'] ?? '') !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL),
                __('Country :code is not an ISO 3166 code.', ['code' => $country]) => $country !== '' && ! isset($countries[$country]),
                __('Currency :code is not set up.', ['code' => $currency]) => $currency !== '' && ! $currencies->has($currency),
                __('Payment term :code does not exist.', ['code' => $term]) => $term !== '' && ! $terms->has($term),
                __('Account :code is not a postable :type account.', ['code' => $account, 'type' => strtolower($this->controlAccountType())]) => $account !== '' && ! $accounts->has($account),
                __('Status must be active or inactive.') => ! in_array(strtolower($data['status'] ?? ''), ['', 'active', 'inactive'], true),
                ...$this->extraErrors($data),
            ]));
            $seen[$code] = true;

            return [...$row, 'action' => $existing->has($code) ? 'update' : 'create', 'errors' => $errors];
        }, $rows);

        return ['rows' => $checked, 'errors' => []];
    }

    public function import(array $rows, array $options): string
    {
        $model = $this->model();
        $currencies = Currency::pluck('id', 'code');
        $terms = PaymentTerm::pluck('id', 'code');
        $accounts = Account::pluck('id', 'account_code');
        $counts = ['create' => 0, 'update' => 0];

        foreach ($rows as $row) {
            $data = $row['data'];
            $optional = fn (string $key) => ($data[$key] ?? '') !== '' ? $data[$key] : null;

            $model::updateOrCreate(
                [$this->codeColumn() => $data['code']],
                [
                    'company_id' => (int) app(CompanyContextService::class)->getActiveCompanyId(),
                    'name' => $data['name'],
                    'contact_person' => $optional('contact_person'),
                    'email' => $optional('email'),
                    'phone' => $optional('phone'),
                    'tax_number' => $optional('tax_number'),
                    'country_code' => $optional('country_code') ? strtoupper($data['country_code']) : null,
                    'address' => $optional('address'),
                    'currency_id' => $optional('currency_code') ? $currencies->get(strtoupper($data['currency_code'])) : null,
                    'payment_term_id' => $optional('payment_term_code') ? $terms->get(strtoupper($data['payment_term_code'])) : null,
                    $this->controlAccountColumn() => $optional('control_account_code') ? $accounts->get($data['control_account_code']) : null,
                    'status' => strtolower($optional('status') ?? 'active'),
                    ...$this->extraAttributes($data),
                    'updated_by' => Auth::id(),
                ],
            );
            $counts[$row['action']]++;
        }

        return __(':created created, :updated updated.', ['created' => $counts['create'], 'updated' => $counts['update']]);
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, bool>
     */
    protected function extraErrors(array $data): array
    {
        return [];
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    protected function extraAttributes(array $data): array
    {
        return [];
    }
}
