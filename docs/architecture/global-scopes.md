# Global Scopes (v1)

Zephyr supports multiple flat operational scopes (for example `company`, `school`) through a neutral `scopes` domain entity.

## Core Rules

- Users are global.
- Product catalog is global.
- Operational records are scoped by `scope_id`.
- Each authenticated user works in one `active_scope_id` at a time.

## Runtime Behavior

- `active_scope_id` is stored in session.
- Middleware validates active scope membership and applies fallback to first accessible scope.
- Scoped models enforce isolation with a global Eloquent scope.
- Creation auto-fills `scope_id` from the active scope context.

## Enforcement

- Multi-scope enforcement is always on.
- Explicit scope assignment is required for authenticated users.
- Null active-scope contexts are denied for scoped queries on web/API requests.
- Console workflows (seed/migrate/maintenance commands) bypass the scope filter.

## Authorization

- Record-level policies enforce scope ownership for view/update/delete operations.
- Root users can bypass scope checks explicitly.

## UI

- Filament topbar exposes a persistent scope switcher for assigned active scopes.
- Scope-bound resources set `scope_id` as hidden/default and avoid manual reassignment.
