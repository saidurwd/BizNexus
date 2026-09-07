<?php

namespace Modules\Core\Exceptions;

use Exception;

class InvalidAccountingTransactionException extends Exception
{
    protected array $errors;

    public function __construct(string $message, array $errors = [])
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
