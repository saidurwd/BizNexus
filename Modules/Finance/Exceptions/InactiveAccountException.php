<?php

namespace Modules\Finance\Exceptions;

use Exception;

class InactiveAccountException extends Exception
{
    public function __construct(int $accountId)
    {
        parent::__construct("Account [{$accountId}] is inactive and cannot receive postings.");
    }
}
