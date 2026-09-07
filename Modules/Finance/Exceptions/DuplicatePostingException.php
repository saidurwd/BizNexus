<?php

namespace Modules\Finance\Exceptions;

use Exception;

class DuplicatePostingException extends Exception
{
    public function __construct(int $journalId)
    {
        parent::__construct("Journal [{$journalId}] has already been posted.");
    }
}
