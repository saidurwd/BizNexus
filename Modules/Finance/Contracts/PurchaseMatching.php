<?php

namespace Modules\Finance\Contracts;

use Modules\Core\Support\Money;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierInvoiceLine;

/**
 * Three-way matching of supplier invoices to purchase orders and goods receipts. The finance module posts
 * invoices; the inventory module, when installed, decides how matched lines are costed.
 */
interface PurchaseMatching
{
    /**
     * Refuse an invoice whose matched lines bill more than was received or stray too far from the order price.
     */
    public function check(SupplierInvoice $invoice): void;

    /**
     * Journal lines for the cost of one invoice line (its net amount plus non-recoverable tax, in the invoice
     * currency): standard lines with account_id/description/debit/credit, and functional-only adjustments
     * with line_type/functional_amount.
     *
     * @return list<array<string, mixed>>
     */
    public function costLines(SupplierInvoice $invoice, SupplierInvoiceLine $line, Money $cost): array;

    /**
     * Record what the posted invoice billed against its purchase order lines.
     */
    public function invoicePosted(SupplierInvoice $invoice): void;
}
