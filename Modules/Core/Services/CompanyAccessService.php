<?php

namespace Modules\Core\Services;

use Modules\Core\Models\UserCompany;

class CompanyAccessService
{
    public function hasAccess(int $companyId, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return UserCompany::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->exists();
    }

    public function getAccessibleCompanies(?int $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return \Modules\Core\Models\Company::whereHas('userCompanies', function ($query) use ($userId) {
            $query->where('user_id', $userId)->where('status', 'active');
        })->get();
    }

    public function validateCompany(int $companyId, ?int $userId = null): bool
    {
        return $this->hasAccess($companyId, $userId);
    }
}
