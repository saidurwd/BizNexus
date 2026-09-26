<?php

namespace Modules\Finance\Contracts;

use Modules\Finance\Models\CustomerInvoice;

/**
 * Cost of goods sold for customer invoices. The finance module posts the revenue; the inventory module, when
 * installed, issues the stock the invoice sells and posts its cost.
 */
interface SalesCostOfGoods
{
    /**
     * Refuse an invoice whose stock lines cannot be delivered (no warehouse, or not enough stock).
     */
    public function check(CustomerInvoice $invoice): void;

    /**
     * Issue the invoice's stock and post its cost, inside the posting transaction.
     */
    public function invoicePosted(CustomerInvoice $invoice): void;
}
