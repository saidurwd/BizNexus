<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\BranchAccessService;

class BranchAccess
{
    public function __construct(protected BranchAccessService $branchAccess)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $branchId = $request->route('branch_id') ?? $request->input('branch_id');

        if ($branchId && !$this->branchAccess->hasAccess($branchId)) {
            abort(403, 'You do not have access to this branch.');
        }

        return $next($request);
    }
}
