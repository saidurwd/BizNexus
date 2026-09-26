<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Core\Models\SecurityEvent;
use Modules\Core\Services\PermissionService;
use Modules\Core\Services\SecurityLogService;

/**
 * Requires the permission through the user's roles in the active company. Requests authenticated with an
 * API token additionally require the token itself to carry the permission (or the "*" ability).
 */
class Permission
{
    public function __construct(protected PermissionService $permissionService) {}

    public function handle(Request $request, Closure $next, string $permission)
    {
        if (! $this->permissionService->hasPermission($permission)) {
            $this->recordDenial($request, $permission);
            abort(403, 'You do not have permission to perform this action.');
        }

        if ($request->user()?->currentAccessToken() instanceof PersonalAccessToken && ! $request->user()->tokenCan($permission)) {
            abort(403, 'This API token does not allow this action.');
        }

        return $next($request);
    }

    protected function recordDenial(Request $request, string $permission): void
    {
        if ($request->user()) {
            app(SecurityLogService::class)->event(SecurityEvent::ACCESS_DENIED, SecurityEvent::SEVERITY_WARNING, $request->user(), context: [
                'permission' => $permission,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
            ]);
        }
    }
}
