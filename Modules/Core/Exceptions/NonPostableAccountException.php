<?php

namespace Modules\Core\Exceptions;

use Exception;
use Modules\Finance\Models\Account;

class NonPostableAccountException extends Exception
{
    protected Account $account;

    public function __construct(Account $account)
    {
        $this->account = $account;

        parent::__construct("Account '{$account->account_name}' ({$account->account_code}) is a group account and cannot receive direct postings");
    }

    public function getAccount(): Account
    {
        return $this->account;
    }
}
