<?php

namespace Modules\Finance\Exceptions;

use Exception;

class InvalidApprovalTransitionException extends Exception
{
    public function __construct(string $entityType, string $currentStatus, string $newStatus)
    {
        parent::__construct("Invalid transition for {$entityType}: cannot transition from '{$currentStatus}' to '{$newStatus}'");
    }
}
