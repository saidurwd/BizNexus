<?php

namespace Modules\Finance\Exceptions;

use Exception;

class UnbalancedJournalException extends Exception
{
    public function __construct(float $totalDebit, float $totalCredit)
    {
        parent::__construct("Journal is unbalanced. Total Debit: {$totalDebit}, Total Credit: {$totalCredit}");
    }
}
