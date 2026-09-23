# Global Scopes (v1)

Zephyr supports multiple flat operational scopes (for example `company`, `school`) through a neutral `scopes` domain entity.

## Core Rules

- Users are global.
- Product catalog is global.
- Operational records are scoped by `scope_id`.
- Each panel request works in one scope, the Filament tenant.

## Runtime Behavior

- The scope is the Filament tenant (`Scope`, slug attribute `slug`, ownership relationship `scope`): it is the first URL segment (`/<scope-slug>/...`).
- Filament checks that the user belongs to the scope of the URL.
- Tenant-scoped resources filter their queries (select options included) with Filament's tenancy global scope.
- Creation auto-fills `scope_id` from the current tenant.
- Global resources (catalog, users, scopes) set `$isScopedToTenant = false`.

## Enforcement

- Multi-scope enforcement is always on.
- Explicit scope assignment is required for authenticated users.
- Console commands and API requests have no tenant: their queries are not filtered by scope, so code running there must filter explicitly.
- Changes to shared records that update copies in scoped tables (e.g. product data on stocks) bypass the tenancy scope, so every scope is updated.

## Authorization

- Record-level policies enforce scope ownership for view/update/delete operations.
- Root users can bypass scope checks explicitly.

## UI

- The Filament tenant menu switches between the scopes assigned to the user.
- Scope-bound resources set `scope_id` as hidden/default and avoid manual reassignment.
