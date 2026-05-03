# Global Scopes Design (v1)

Date: 2026-05-03
Status: Draft for review
Scope: Introduce "global scopes" to support multiple operational contexts (e.g. companies, schools) while keeping users and product catalog shared.

## 1. Objectives

- Support multiple operational scopes in a single Laravel/Filament instance.
- Keep a shared global base for users and product catalog.
- Isolate operational data by active scope.
- Allow users/groups to access one or more scopes.
- Keep v1 simple: flat scopes (no hierarchy).

## 2. Non-Goals (v1)

- No scope hierarchy (`parent_scope_id` is out of scope).
- No cross-scope aggregate views in regular operator flows.
- No multi-database tenancy.

## 3. Domain Model

### 3.1 New Core Entity: `scopes`

`scopes` is the neutral domain container for business contexts such as companies or schools.

Proposed columns:
- `id`
- `name`
- `slug` (unique)
- `type` (string/enum-like; examples: `company`, `school`)
- `is_active` (boolean)
- `created_at`, `updated_at`

Constraints:
- `slug` unique globally.
- `type` required.

### 3.2 Access Mapping

#### `scope_user` (many-to-many)

Maps users to accessible scopes.

Columns:
- `scope_id`
- `user_id`
- optional metadata (future): `is_default`, `joined_at`

Constraints:
- composite unique on (`scope_id`, `user_id`).

### 3.3 Role/Group by Scope

Authorization must support role assignment per scope (Spatie-compatible extension pattern).

Requirement:
- role/group membership must be evaluable in the context of `active_scope_id`.

Implementation detail is flexible (exact pivot naming can follow existing auth conventions), but behavior is mandatory.

## 4. Data Partitioning Strategy

### 4.1 Global Data (shared)

These remain global and unscoped:
- `users`
- `products`
- `product_brands`
- `product_models`
- `product_types`
- `product_groups`

### 4.2 Scoped Data (isolated)

These must include `scope_id` and be isolated by active scope:
- inventories/stock domain
- movements and movement items
- tasks and task-related entities
- reorder domain, orders, and order items
- other operational records created in daily workflows

Rule:
- every operational record belongs to exactly one scope.

## 5. Runtime Context Model

### 5.1 Active Scope Resolution

Store `active_scope_id` in session.

Resolution order:
1. session value if valid and accessible by user
2. user's default scope (if introduced)
3. first accessible active scope

If no accessible scope exists:
- deny access to operational areas with explicit guidance.

### 5.2 Scope Switching (Filament)

- Add scope selector in panel topbar.
- Switching scope updates `active_scope_id`.
- UI should clearly show current active scope in critical pages.

### 5.3 Record Creation

- New scoped records automatically inherit `scope_id = active_scope_id`.
- Manual `scope_id` edits are disallowed for non-super-admin users.

## 6. Authorization and Safety

### 6.1 Query Isolation

Apply a reusable Eloquent global scope to scoped models:
- filter by `scope_id = active_scope_id`.

### 6.2 Policy Enforcement

Policies must perform explicit record-level checks to prevent bypasses in:
- direct route/model binding access
- custom actions/jobs
- edge-case queries not covered by default resource listing

### 6.3 Elevated Access

Super-admin behavior:
- can bypass scope filtering only through explicit and auditable paths.
- default behavior should still respect active scope unless intentionally overridden.

## 7. Migration and Rollout Plan

1. Create `scopes` and `scope_user` tables.
2. Seed one initial scope (`Default`) with `type=company`.
3. Add nullable `scope_id` to all scoped operational tables.
4. Backfill existing records with `Default` scope id.
5. Make `scope_id` non-nullable.
6. Add foreign keys + performance indexes (`scope_id`, and composite indexes where needed).
7. Enable runtime resolver + UI selector behind feature flag `multi_scope_enabled`.
8. Enable global scopes/policies progressively.

Rollback strategy:
- keep feature flag off until backfill and validation pass.

## 8. Testing Strategy (Minimum v1)

Feature tests:
- user sees only records from currently active accessible scope.
- user cannot access records from non-assigned scopes.
- switching scope changes resource datasets.

Authorization tests:
- policy denies cross-scope read/update/delete.
- creation assigns correct `scope_id`.

Regression tests:
- global entities remain shared and visible as expected.

## 9. Risks and Mitigations

Risk: missing `scope_id` filter in custom queries.
Mitigation:
- central helper/trait for scoped queries.
- targeted tests for widgets/services.

Risk: accidental privilege escalation in role checks.
Mitigation:
- require scope-aware role resolution.
- explicit super-admin bypass only.

Risk: migration on large tables.
Mitigation:
- phased nullable -> backfill -> not null rollout.
- index review before enabling flag in production.

## 10. Open Implementation Decisions (to settle in planning)

- exact role-per-scope storage approach with current Spatie setup.
- whether `type` should be free string or constrained enum/check.
- whether to store a persistent user default scope at DB level in v1 or defer.

## 11. Acceptance Criteria

- Multiple scopes can be created and assigned to users.
- A user with access to N scopes can switch active scope in UI.
- Operational data is isolated by active scope.
- Users and product catalog remain shared globally.
- Cross-scope access is denied unless explicit elevated permission applies.
