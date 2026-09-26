<?php

namespace Modules\Finance\Services\Import;

use Modules\Finance\Models\Supplier;

class SupplierImporter extends PartyImporter
{
    public function label(): string
    {
        return __('Suppliers');
    }

    public function example(): array
    {
        return [['code' => 'S-2001', 'name' => 'Initech GmbH', 'contact_person' => 'Max Mustermann', 'email' => 'billing@initech.example', 'phone' => '+49 30 123456', 'tax_number' => 'DE123456789', 'country_code' => 'DE', 'address' => 'Hauptstrasse 1, Berlin', 'currency_code' => 'EUR', 'payment_term_code' => 'NET30', 'control_account_code' => '', 'status' => 'active']];
    }

    protected function model(): string
    {
        return Supplier::class;
    }

    protected function codeColumn(): string
    {
        return 'supplier_code';
    }

    protected function controlAccountColumn(): string
    {
        return 'payable_account_id';
    }

    protected function controlAccountType(): string
    {
        return 'LIABILITY';
    }
}
