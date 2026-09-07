<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\Company;
use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;

class CompanyContextService
{
    protected ?int $companyId = null;
    protected ?Company $company = null;

    public function setCompany(int $companyId): void
    {
        if (!$this->hasAccessToCompany($companyId)) {
            throw new UnauthorizedCompanyAccessException($companyId, Auth::id());
        }

        $this->companyId = $companyId;
        $this->company = Company::find($companyId);
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function getCompany(): ?Company
    {
        if ($this->companyId && !$this->company) {
            $this->company = Company::find($this->companyId);
        }

        return $this->company;
    }

    public function hasAccessToCompany(int $companyId): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->companies()->where('companies.id', $companyId)->exists();
    }

    public function validateCompanyAccess(int $companyId): void
    {
        if (!$this->hasAccessToCompany($companyId)) {
            throw new UnauthorizedCompanyAccessException($companyId, Auth::id());
        }
    }

    public function clear(): void
    {
        $this->companyId = null;
        $this->company = null;
    }

    public function getBaseCurrency()
    {
        $company = $this->getCompany();

        return $company?->baseCurrency;
    }

    public function getTimezone(): string
    {
        return $this->getCompany()?->timezone ?? 'UTC';
    }

    public function getCurrentFiscalYear()
    {
        return $this->getCompany()?->currentFiscalYear();
    }

    public function getCurrentFiscalPeriod()
    {
        $fiscalYear = $this->getCurrentFiscalYear();

        return $fiscalYear?->getCurrentPeriod();
    }
}
