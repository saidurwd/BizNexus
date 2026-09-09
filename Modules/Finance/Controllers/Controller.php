<?php

namespace Modules\Finance\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\BranchContextService;
use Modules\Core\Services\PermissionService;

class Controller extends BaseController
{
    protected CompanyContextService $companyContext;
    protected ?BranchContextService $branchContext = null;
    protected PermissionService $permissionService;

    public function __construct(CompanyContextService $companyContext, PermissionService $permissionService, ?BranchContextService $branchContext = null)
    {
        $this->companyContext = $companyContext;
        $this->branchContext = $branchContext ?? app(BranchContextService::class);
        $this->permissionService = $permissionService;
    }

    protected function getActiveCompanyId(): ?int
    {
        return $this->companyContext->getActiveCompanyId();
    }

    protected function getActiveBranchId(): ?int
    {
        return $this->branchContext?->getActiveBranchId();
    }

    protected function checkPermission(string $permission): void
    {
        if (!$this->permissionService->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
