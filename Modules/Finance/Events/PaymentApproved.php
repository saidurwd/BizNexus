<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\SupplierPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public SupplierPayment $payment) {}
}
