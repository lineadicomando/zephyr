# Roadmap v0.2.0

## v0.2.0 Features

### 1. WPA - Initial Support (without notifications)
- [ ] Implement initial WPA support
- [ ] Disable notifications
- Status: Pending

### 2. Ansible API: Products/Inventory Synchronization
- [ ] Integrate lineadicomando.zephyr collection
- [ ] Synchronize products table
- [ ] Synchronize inventory table
- Status: Pending

### 3. Ansible API: Maintenance Tasks Synchronization
- [ ] Integrate lineadicomando.zephyr collection
- [ ] Synchronize tasks table
- [ ] Transmit completed maintenance task reports
- Status: Pending

---

## Code review follow-ups (open items)

Items left open after the September 2026 code reviews, ordered by priority. Fixed items are in the git history.

### P1 - High

### P2 - Medium
- [ ] `RolePolicy` checks `ViewAny:Role`-style permissions, but Shield generates snake_case names (`view_any_role`): role permissions granted to non super admins have no effect
- [ ] Dashboard widgets (`StatsOverview`, `MovementChart`, `TaskChart`, `CurrentScopeWidget`) ignore the Shield widget permissions (no `HasWidgetShield`/`canView()`)
- [ ] Product edits: `Product::onSaved` goes through the Filament tenant scope, so stocks/inventories of other scopes keep stale product columns; renaming a product leaves the old name on stocks and movement items (stocks re-saved before `syncSummary()`, which saves quietly). Fix together with the cascading saves item below
- [ ] Docker entrypoint: if the first-boot seed fails after the migrations (e.g. placeholder `BOOTSTRAP_ADMIN_PASSWORD`), the restart sees migrations as `Ran` and never seeds again: no admin
- [ ] Docker: `SEED_DEMO_DATA=true` always fails (Faker is dev-only, image built with `--no-dev`) but the README suggests it
- [ ] Demo seed creates admin users with password `password` and attaches every user to every scope; `zephyr:setup` offers it in production too
- [ ] `zephyr:setup` rebuilds `.env` from `.env.example` keeping only `APP_KEY`: re-running it resets `TRUSTED_PROXIES`, `MAIL_*`, drivers and other custom values
- [ ] `zephyr:setup` leaves the bootstrap admin password in `.env` when migration/seed fails (and `.env` gets backed up)

### P3 - Low
- [ ] Demo data is inconsistent: seeded stocks do not match their movement items (140/180) and many demo inventories cannot be edited ("Insufficient availability")
- [ ] Soft-deleted ProductGroup/ProductType/MovementType/User keep their unique name/email forever (plain unique indexes, no restore UI): added trashed filter and restore/force delete actions, as for users
- [ ] `ReorderOrderService` status transitions are neither transactional nor locked (concurrent cancel/receive, partial `markReceived`)
- [ ] `InventoryResource\RelationManagers\MovementsRelationManager` creates/updates `Movement` records while authorized with MovementItem permissions
- [ ] `InventoryLocationPolicy`, `InventoryPositionPolicy`, `MovementTypePolicy` do not check scope membership (`ChecksScopeAccess`); isolation relies only on the Filament tenant scope
- [ ] Scope slug can be changed to `api` (rejected by the tenant route pattern): the scope becomes unreachable
- [ ] `GET /api/user` has no token ability check
- [ ] `composer run setup` produces an insecure install (`APP_DEBUG=true`, `admin@example.com`/`password`) without warnings in the README
- [ ] README: remove the session `active_scope_id` description (scope comes from the tenant URL) and the `console_bypass_commands` mention
- [ ] Docker: `public-vol` mount point does not exist in the image; on Docker (not Podman) the volume may be root-owned and `rsync` fails as `www-data`
- [ ] Roles are global (`permission.teams => false`): an admin is admin in every scope they belong to; per-scope roles require spatie/permission teams

### Data integrity (structural)
- [ ] Add foreign keys on `inventories.product_id` and on the product columns of `stocks` (clean up orphan rows first)
- [ ] Stock uniqueness is not enforced when `inventory_position_id` is NULL
- [ ] `Movement::onSaving` fills the location only when empty: derive it from the position every time
- [ ] Inventory locations can never be deleted (`defaultPosition()` always creates a related position)
- [ ] Reorder proposals: every click creates a new draft even if an open order already contains the same stocks; outside the panel the order scope is taken from the first rule

### Operations and setup
- [ ] API tokens can only be created through tinker: add a command or a panel page to issue/revoke tokens with abilities

### Performance
- [ ] Cascading saves: `Inventory`, `InventoryLocation`, `Product`, `ProductType` re-save every related stock/movement item on save; replace with bulk updates of the changed columns
- [ ] `MovementItem::isLast()` runs one query per table row (twice in `MovementItemsRelationManager`): compute it with a subquery
- [ ] Missing indexes: `tasks` (`task_type_id`, `task_status_id`, `user_id`, `starts_at`), movement foreign keys, `inventories.product_id`, product foreign keys

### Code quality
- [ ] Add Larastan (new dependency, needs approval): plain PHPStan reports ~290 mostly false positive errors on Eloquent magic
- [ ] Remove dead code: `UserResource\RelationManagers\GroupsRelationManager` (no `groups` relation), `config/scopes.php` `console_bypass_commands` (unused), unused static helpers in `InventoryResource` (lines ~62-94)
- [ ] Deduplicate the inventory table/filters (`InventoryResource`, `TaskResource\InventoriesRelationManager`, `StockResource`) and the `requestDeletion` action (`ScopeResource`, `EditScope`)

### Tests
- [ ] `php artisan test` on the whole suite exceeds the 128M memory limit (the `arch()` test together with the others): run `php -d memory_limit=1G vendor/bin/pest` meanwhile
- [ ] `UserFactory` attaches every user to the `default` scope, and `StockFactory`/`MovementFactory` create related records in different scopes: this hides cross-scope issues in tests
- [ ] Missing coverage: interactive `zephyr:setup` flow, URL tampering across tenants for Movement/Reorder/Location/Task resources (manually verified safe in the second review), scope purge with full domain data, changing origin/destination of an existing movement

---

## Notes
- Current version: v0.1.2
- Target: v0.2.0
- Additional items will be added later
