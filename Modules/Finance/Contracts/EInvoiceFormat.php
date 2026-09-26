<?php

namespace Modules\Finance\Contracts;

use Modules\Finance\Models\CustomerInvoice;

/**
 * A structured e-invoice format (e.g. Peppol BIS 3, ZATCA, India IRN, Bangladesh NBR), registered in
 * config('finance.einvoice.formats') and chosen per country in config('finance.einvoice.country_formats').
 */
interface EInvoiceFormat
{
    public function key(): string;

    public function render(CustomerInvoice $invoice): string;

    public function mimeType(): string;

    public function fileExtension(): string;
}
