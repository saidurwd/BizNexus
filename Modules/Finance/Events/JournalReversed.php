<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\Journal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JournalReversed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Journal $original, public Journal $reversal) {}
}
