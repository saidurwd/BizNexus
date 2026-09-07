<?php

namespace Modules\Core\Exceptions;

use Exception;

class UnauthorizedCompanyAccessException extends Exception
{
    protected int $companyId;
    protected ?int $userId;

    public function __construct(int $companyId, ?int $userId = null)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;

        parent::__construct("User does not have access to company ID: {$companyId}");
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
}
