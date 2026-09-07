<?php

namespace Modules\Finance\Exceptions;

use Exception;

class NonPostableAccountException extends Exception
{
    public function __construct(int $accountId)
    {
        parent::__construct("Account [{$accountId}] is not postable and cannot be used in journal lines.");
    }
}
