<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\PermissionService;

class Permission
{
    public function __construct(protected PermissionService $permissionService)
    {
    }

    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!$this->permissionService->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
