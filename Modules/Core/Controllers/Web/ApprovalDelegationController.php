<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\ApprovalDelegation;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Core\Services\SegregationOfDutiesService;

/**
 * Users delegate their own approval authority in the active company, e.g. while on leave.
 */
class ApprovalDelegationController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected PermissionService $permissionService,
        protected AuditService $audit
    ) {}

    public function store(Request $request, SegregationOfDutiesService $sod): RedirectResponse
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $userId = $request->user()->id;

        $validated = $request->validate([
            'delegate_id' => [
                'required', 'integer', Rule::notIn([$userId]),
                Rule::exists('user_companies', 'user_id')->where('company_id', $companyId)->where('status', 'active'),
            ],
            'starts_on' => 'required|date|after_or_equal:today',
            'ends_on' => 'required|date|after_or_equal:starts_on|before_or_equal:'.now()->addDays(90)->toDateString(),
            'reason' => 'nullable|string|max:255',
        ]);

        $delegated = PermissionService::delegablePermissions($this->permissionService->getRolePermissions($userId, $companyId));

        if ($delegated === []) {
            throw ValidationException::withMessages(['delegate_id' => 'You have no approval permissions to delegate.']);
        }

        $conflicts = $sod->conflictsIn([...$this->permissionService->getRolePermissions((int) $validated['delegate_id'], $companyId), ...$delegated]);

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'delegate_id' => 'This delegation would give the delegate conflicting permissions: '.$sod->describe($conflicts).'.',
            ]);
        }

        $delegation = ApprovalDelegation::create([...$validated, 'company_id' => $companyId, 'delegator_id' => $userId]);

        $this->audit->logCreate('Core', 'ApprovalDelegation', $delegation->id, $delegation->toArray() + ['permissions' => $delegated], $companyId);

        return redirect()->route('profile.edit')->with('status', 'delegation-created');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $delegation = ApprovalDelegation::where('delegator_id', $request->user()->id)->whereNull('revoked_at')->findOrFail($id);

        $delegation->update(['revoked_at' => now()]);

        $this->audit->logCustom('Core', 'ApprovalDelegation', $delegation->id, 'REVOKE', []);

        return redirect()->route('profile.edit')->with('status', 'delegation-revoked');
    }
}
