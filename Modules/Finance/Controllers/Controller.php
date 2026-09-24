<?php

namespace Modules\Finance\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Core\Services\BranchContextService;
use Modules\Core\Services\CompanyContextService;
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

    protected function successResponse(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function errorResponse(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], fn ($value) => $value !== null), $status);
    }

    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    protected function checkPermission(string $permission): void
    {
        if (! $this->permissionService->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
