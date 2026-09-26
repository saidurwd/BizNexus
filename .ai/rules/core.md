---
paths:
  - 'Modules/Core/**'
---

# Core

## Super admin is exempt from segregation of duties
The super-admin role (Role::SUPER_ADMIN) is granted every permission by PermissionCatalog::install(), so it always contains the configured conflicting pairs. SegregationOfDutiesService::conflictsForRoles() and RoleController skip it. Without that exemption, no user could be given or keep the super-admin role. Apply SoD only to operational roles. Validation errors on ERP pages are listed by layouts/partials/flash-messages, so a form doesn't need its own error summary.
