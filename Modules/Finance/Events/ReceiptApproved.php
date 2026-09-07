<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\CustomerReceipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReceiptApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public CustomerReceipt $receipt) {}
}
