<?php

namespace Modules\Core\Exceptions;

use Exception;

class UnbalancedJournalException extends Exception
{
    protected float $totalDebit;
    protected float $totalCredit;

    public function __construct(float $totalDebit, float $totalCredit)
    {
        $this->totalDebit = $totalDebit;
        $this->totalCredit = $totalCredit;

        $difference = bcsub($totalDebit, $totalCredit, 4);
        parent::__construct("Journal is not balanced. Total Debit: {$totalDebit}, Total Credit: {$totalCredit}, Difference: {$difference}");
    }

    public function getTotalDebit(): float
    {
        return $this->totalDebit;
    }

    public function getTotalCredit(): float
    {
        return $this->totalCredit;
    }

    public function getDifference(): float
    {
        return bcsub($this->totalDebit, $this->totalCredit, 4);
    }
}
