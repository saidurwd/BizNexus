<?php

namespace Modules\Core\Exceptions;

use Exception;
use Modules\Core\Models\FiscalPeriod;

class ClosedPeriodException extends Exception
{
    protected FiscalPeriod $period;
    protected string $action;

    public function __construct(FiscalPeriod $period, string $action = 'post')
    {
        $this->period = $period;
        $this->action = $action;

        parent::__construct("Cannot {$action} transactions in closed period: {$period->period_name}");
    }

    public function getPeriod(): FiscalPeriod
    {
        return $this->period;
    }
}
