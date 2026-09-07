<?php

namespace Modules\Core\Exceptions;

use Exception;
use Modules\Finance\Models\Journal;

class DuplicatePostingException extends Exception
{
    protected Journal $journal;

    public function __construct(Journal $journal)
    {
        $this->journal = $journal;

        parent::__construct("Journal '{$journal->journal_number}' has already been posted");
    }

    public function getJournal(): Journal
    {
        return $this->journal;
    }
}
