<?php

namespace Modules\Core\Services;

use Illuminate\Http\Request;
use Modules\Core\Models\Company;
use Modules\Core\Models\UserCompany;
use Modules\Core\Models\CompanyUserRole;

class CompanyContextService
{
    protected ?Company $activeCompany = null;

    public function getActiveCompany(): ?Company
    {
        if ($this->activeCompany) {
            return $this->activeCompany;
        }

        $companyId = session('active_company_id');

        if (!$companyId) {
            return null;
        }

        return $this->activeCompany = Company::find($companyId);
    }

    public function getActiveCompanyId(): ?int
    {
        return session('active_company_id');
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

        if (!$userCompany) {
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
            ->where('company_id', $companyId)
            ->where('status', 'active')
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
}
