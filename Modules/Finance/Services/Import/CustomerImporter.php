<?php

namespace Modules\Finance\Services\Import;

use Modules\Finance\Models\Customer;

class CustomerImporter extends PartyImporter
{
    public function label(): string
    {
        return __('Customers');
    }

    public function columns(): array
    {
        return [...parent::columns(), 'credit_limit' => [false, __('In the company currency; empty for no limit')]];
    }

    public function example(): array
    {
        return [['code' => 'C-1001', 'name' => 'Globex Ltd', 'contact_person' => 'Jane Doe', 'email' => 'ap@globex.example', 'phone' => '+44 20 7946 0000', 'tax_number' => 'GB123456789', 'country_code' => 'GB', 'address' => '1 Market Street, London', 'currency_code' => 'GBP', 'payment_term_code' => 'NET30', 'control_account_code' => '', 'status' => 'active', 'credit_limit' => '25000']];
    }

    protected function model(): string
    {
        return Customer::class;
    }

    protected function codeColumn(): string
    {
        return 'customer_code';
    }

    protected function controlAccountColumn(): string
    {
        return 'receivable_account_id';
    }

    protected function controlAccountType(): string
    {
        return 'ASSET';
    }

    protected function extraErrors(array $data): array
    {
        $limit = $data['credit_limit'] ?? '';

        return [__('Credit limit must be a number of zero or more.') => $limit !== '' && (! is_numeric($limit) || $limit < 0)];
    }

    protected function extraAttributes(array $data): array
    {
        return ['credit_limit' => ($data['credit_limit'] ?? '') !== '' ? $data['credit_limit'] : null];
    }
}
