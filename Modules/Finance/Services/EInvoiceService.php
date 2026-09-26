<?php

namespace Modules\Finance\Services;

use InvalidArgumentException;
use Modules\Finance\Contracts\EInvoiceFormat;
use Modules\Finance\Models\CustomerInvoice;

class EInvoiceService
{
    public function formatFor(CustomerInvoice $invoice, ?string $key = null): EInvoiceFormat
    {
        $countryFormats = config('finance.einvoice.country_formats', []);
        $key ??= $countryFormats[$invoice->company->country_code] ?? $countryFormats['*'] ?? null;
        $class = config("finance.einvoice.formats.{$key}");

        if (! $class) {
            throw new InvalidArgumentException("Unknown e-invoice format [{$key}].");
        }

        return app($class);
    }
}
