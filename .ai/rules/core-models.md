---
paths:
  - 'Modules/Core/Models/**'
---

# Core Models

## Tenancy: companies and users belong to one tenant
Tenant (customer organisation) > companies > data. Company and User fill tenant_id from the signed-in user on create (seeders run WithoutModelEvents, so set tenant_id explicitly there, e.g. Tenant::default()). UserCompany refuses cross-tenant links; companies cannot change tenant. Company codes are unique per tenant. Sign-in and token checks use User::canSignIn() (user status, tenant status, data region), not isActive().
