<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\CustomerInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerInvoiceApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public CustomerInvoice $invoice) {}
}
