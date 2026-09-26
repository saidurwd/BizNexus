---
paths:
  - 'database/seeders/**'
---

# Seeders

## Never add a permission only in CoreSeeder
Existing databases are never re-seeded, so a permission slug added only to CoreSeeder does not exist there and every route guarded by it returns 403 for everyone, super-admin included. Add new slugs with a migration calling PermissionCatalog::install($permissions, $legacyEquivalents) (and to the seeder for fresh installs). install() keeps the super-admin role holding every permission.
