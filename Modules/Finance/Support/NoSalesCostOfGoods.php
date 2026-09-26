<?php

namespace Modules\Finance\Support;

use Modules\Finance\Contracts\SalesCostOfGoods;
use Modules\Finance\Models\CustomerCreditNote;
use Modules\Finance\Models\CustomerInvoice;

/**
 * Without inventory, customer invoices post revenue only.
 */
class NoSalesCostOfGoods implements SalesCostOfGoods
{
    public function check(CustomerInvoice $invoice): void {}

    public function invoicePosted(CustomerInvoice $invoice): void {}

    public function creditNotePosted(CustomerCreditNote $creditNote): void {}
}
