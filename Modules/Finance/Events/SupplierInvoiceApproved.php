<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\SupplierInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupplierInvoiceApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public SupplierInvoice $invoice) {}
}
