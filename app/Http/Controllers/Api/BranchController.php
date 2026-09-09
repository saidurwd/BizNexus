<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Services\BranchAccessService;
use Modules\Core\Services\CompanyContextService;

class BranchController extends Controller
{
    public function __construct(
        protected BranchAccessService $branchAccess,
        protected CompanyContextService $companyContext
    ) {
    }

    public function index(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $companyId = (int) $request->query('company_id');

        if (!$this->companyContext->hasCompanyAccess($companyId)) {
            return response()->json(['message' => 'You do not have access to this company.'], 403);
        }

        $branches = $this->branchAccess->getAccessibleBranches($companyId);

        return response()->json(
            $branches->map(fn($b) => ['id' => $b->id, 'code' => $b->code, 'name' => $b->name])
        );
    }
}
