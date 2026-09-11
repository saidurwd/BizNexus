<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\SupplierDebitNote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupplierDebitNoteApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public SupplierDebitNote $debitNote) {}
}
