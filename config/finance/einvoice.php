<?php

use Modules\Finance\Services\EInvoicing\PeppolBisBillingFormat;

return [

    /*
    |--------------------------------------------------------------------------
    | E-Invoice Formats
    |--------------------------------------------------------------------------
    |
    | Implementations of Modules\Finance\Contracts\EInvoiceFormat, by key. Add
    | adapters for national schemes (ZATCA, India IRN, Bangladesh NBR) here.
    |
    */

    'formats' => [
        'peppol-bis-3' => PeppolBisBillingFormat::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Format per Seller Country
    |--------------------------------------------------------------------------
    |
    | ISO 3166-1 country of the issuing company => format key; "*" is the default.
    |
    */

    'country_formats' => [
        '*' => 'peppol-bis-3',
    ],

];
