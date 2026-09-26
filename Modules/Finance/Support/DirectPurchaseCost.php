<?php

namespace Modules\Finance\Support;

use Modules\Core\Support\Money;
use Modules\Finance\Contracts\PurchaseMatching;
use Modules\Finance\Models\SupplierCreditNote;
use Modules\Finance\Models\SupplierCreditNoteLine;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\SupplierInvoiceLine;

/**
 * Without inventory, every invoice line is costed to its own account and nothing is matched.
 */
class DirectPurchaseCost implements PurchaseMatching
{
    public function check(SupplierInvoice $invoice): void {}

    public function costLines(SupplierInvoice $invoice, SupplierInvoiceLine $line, Money $cost): array
    {
        return [['account_id' => $line->account_id, 'description' => $line->description, 'debit' => $cost->amount, 'credit' => 0]];
    }

    public function invoicePosted(SupplierInvoice $invoice): void {}

    public function checkCredit(SupplierCreditNote $creditNote): void {}

    public function creditCostLines(SupplierCreditNote $creditNote, SupplierCreditNoteLine $line, Money $cost): array
    {
        return [['account_id' => $line->account_id, 'description' => $line->description, 'debit' => $cost->amount, 'credit' => 0]];
    }

    public function creditNotePosted(SupplierCreditNote $creditNote): void {}
}
