---
paths:
  - 'routes/**'
---

# Routes

## Every authenticated route declares a permission middleware
Authorization lives in route middleware ->middleware('permission:<slug>'), not in controllers. tests/Feature/AuthorizationTest.php and ApiAccessTest.php fail if a web or api route lacks one (self-service routes are allow-listed there). New permission slugs must be added via a migration + Modules\Core\Support\PermissionCatalog so existing roles keep equivalent access. API routes run behind auth:sanctum + api.company; tokens are bound to one company via a "company:{id}" ability.
