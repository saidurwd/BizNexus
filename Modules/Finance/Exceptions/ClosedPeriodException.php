<?php

namespace Modules\Finance\Exceptions;

use Exception;

class ClosedPeriodException extends Exception
{
    public function __construct(string $periodName, string $operation)
    {
        parent::__construct("Cannot {$operation} journal. Fiscal period '{$periodName}' is closed.");
    }
}
