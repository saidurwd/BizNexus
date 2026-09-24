---
paths:
  - 'Modules/*/Models/**'
---

# Models

## Company-owned models use BelongsToCompany (fail-closed)
Any model with company_id holding company data uses Modules\Core\Concerns\BelongsToCompany. Its CompanyScope returns NO rows when no company is active (queues, console, seeders): wrap such code in app(CompanyContextService::class)->runAs($companyId, fn () => ...). Never call withoutGlobalScope(CompanyScope::class) in controllers; only in cross-company jobs that then runAs per record. Access tables (user_companies, company_user_roles, user_branches) stay unscoped.
