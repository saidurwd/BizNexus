<?php

namespace Modules\Finance\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class Controller extends BaseController
{
    protected CompanyContextService $companyContext;
    protected PermissionService $permissionService;

    public function __construct(CompanyContextService $companyContext, PermissionService $permissionService)
    {
        $this->companyContext = $companyContext;
        $this->permissionService = $permissionService;
    }

    protected function getActiveCompanyId(): ?int
    {
        return $this->companyContext->getActiveCompanyId();
    }

    protected function checkPermission(string $permission): void
    {
        if (!$this->permissionService->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
