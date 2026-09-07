<?php

namespace Modules\Finance\Exceptions;

use Exception;

class InvalidAccountingTransactionException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct("Invalid accounting transaction: {$message}");
    }
}
