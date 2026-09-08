<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\DepartmentAccessService;

class DepartmentAccess
{
    public function __construct(protected DepartmentAccessService $departmentAccess)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $departmentId = $request->route('department_id') ?? $request->input('department_id');

        if ($departmentId && !$this->departmentAccess->hasAccess($departmentId)) {
            abort(403, 'You do not have access to this department.');
        }

        return $next($request);
    }
}
