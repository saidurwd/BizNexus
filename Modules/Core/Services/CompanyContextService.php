<?php

namespace Modules\Core\Services;

use Closure;
use Modules\Core\Models\Company;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Currency;
use Modules\Core\Models\UserCompany;

class CompanyContextService
{
    protected ?Company $activeCompany = null;

    /**
     * Company pinned for this request or job (API token, queued job, seeder), taking precedence over the session.
     */
    protected ?int $pinnedCompanyId = null;

    public function getActiveCompany(): ?Company
    {
        $companyId = $this->getActiveCompanyId();

        if (! $companyId) {
            return null;
        }

        if ($this->activeCompany?->id !== $companyId) {
            $this->activeCompany = Company::find($companyId);
        }

        return $this->activeCompany;
    }

    public function getActiveCompanyId(): ?int
    {
        if ($this->pinnedCompanyId !== null) {
            return $this->pinnedCompanyId;
        }

        $companyId = app()->bound('session') && app('session')->isStarted()
            ? session('active_company_id')
            : null;

        return $companyId ? (int) $companyId : null;
    }

    /**
     * Pin the company for the rest of this request or job without touching the session.
     */
    public function pinCompany(int $companyId): void
    {
        $this->pinnedCompanyId = $companyId;
    }

    /**
     * Run the callback with the given company as the active company, restoring the previous context afterwards.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function runAs(int $companyId, Closure $callback): mixed
    {
        $previousCompanyId = $this->pinnedCompanyId;
        $this->pinnedCompanyId = $companyId;

        try {
            return $callback();
        } finally {
            $this->pinnedCompanyId = $previousCompanyId;
        }
    }

    public function getCompanyId(): ?int
    {
        return $this->getActiveCompanyId();
    }

    public function setActiveCompany(int $companyId, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        $userCompany = UserCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->first();

        if (! $userCompany) {
            return false;
        }

        session(['active_company_id' => $companyId]);
        $this->activeCompany = Company::find($companyId);

        return true;
    }

    public function hasCompanyAccess(int $companyId, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return UserCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->exists();
    }

    public function getUserCompanies(?int $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return Company::whereHas('userCompanies', function ($query) use ($userId) {
            $query->where('user_id', $userId)->where('status', 'active');
        })->get();
    }

    public function getUserCompanyRoles(int $companyId, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return CompanyUserRole::where('user_id', $userId)
            ->where('company_id', $companyId)->active()
            ->with('role')
            ->get()
            ->pluck('role');
    }

    public function getDefaultCompany(?int $userId = null): ?Company
    {
        $userId = $userId ?? auth()->id();

        $userCompany = UserCompany::where('user_id', $userId)
            ->where('is_default', true)
            ->where('status', 'active')
            ->first();

        return $userCompany?->company;
    }

    public function clearActiveBranch(): void
    {
        app(BranchContextService::class)->clearActiveBranch();
    }

    public function getBaseCurrency(): ?Currency
    {
        $company = $this->getActiveCompany();

        return $company?->baseCurrency;
    }
}
