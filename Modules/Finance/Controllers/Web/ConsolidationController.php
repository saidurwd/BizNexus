<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Models\Company;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Exceptions\MissingExchangeRateException;
use Modules\Finance\Models\ConsolidationGroup;
use Modules\Finance\Services\ConsolidationService;

/**
 * Consolidation groups are owned by their parent company; a consolidated report needs the consolidation
 * permission in every member, since it exposes all of their figures.
 */
class ConsolidationController extends Controller
{
    public function index(): View
    {
        return view('finance.consolidation.index', [
            'groups' => ConsolidationGroup::with('members')->where('parent_company_id', $this->getActiveCompanyId())->orderBy('name')->get(),
            'companies' => Company::whereIn('id', $this->viewableCompanyIds())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->getActiveCompanyId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'members' => 'required|array|min:1',
            'members.*.company_id' => ['required', 'integer', 'distinct', Rule::in($this->viewableCompanyIds())],
            'members.*.ownership_percent' => 'required|numeric|gt:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $companyId) {
            $group = ConsolidationGroup::create([
                'tenant_id' => $this->companyContext->getActiveCompany()->tenant_id,
                'parent_company_id' => $companyId,
                'name' => $validated['name'],
            ]);

            $group->members()->attach([$companyId => ['ownership_percent' => 100]]);

            foreach ($validated['members'] as $member) {
                if ((int) $member['company_id'] !== $companyId) {
                    $group->members()->attach([$member['company_id'] => ['ownership_percent' => $member['ownership_percent']]]);
                }
            }
        });

        return redirect()->route('finance.consolidation.index')->with('success', 'Consolidation group created.');
    }

    public function show(Request $request, int $id, ConsolidationService $consolidation): View|RedirectResponse
    {
        $group = ConsolidationGroup::where('parent_company_id', $this->getActiveCompanyId())->findOrFail($id);

        abort_unless(
            $group->members()->pluck('companies.id')->diff($this->viewableCompanyIds())->isEmpty(),
            403,
            'You need the consolidation permission in every company of this group.'
        );

        $asOf = Carbon::parse($request->validate(['as_of' => 'nullable|date'])['as_of'] ?? now()->toDateString());

        try {
            $report = $consolidation->trialBalance($group, $asOf);
        } catch (MissingExchangeRateException $exception) {
            return redirect()->route('finance.consolidation.index')->with('error', $exception->getMessage());
        }

        return view('finance.consolidation.show', ['group' => $group, 'report' => $report, 'asOf' => $asOf->toDateString()]);
    }

    /**
     * @return array<int, int>
     */
    protected function viewableCompanyIds(): array
    {
        return $this->permissionService->companyIdsWithPermission('finance.consolidation.view')->all();
    }
}
