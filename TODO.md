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

Items left open after the September 2026 code review. Fixed items are in the git history.

### Authorization and multi-tenancy
- [ ] Domain rules inside policies are bypassed by super admins (Shield `intercept_gate => before`), e.g. `ReorderOrderPolicy::delete` (draft only): move them to models/actions or switch the intercept to `after`
- [ ] Roles are global (`permission.teams => false`): an admin is admin in every scope they belong to; per-scope roles require spatie/permission teams
- [ ] Global catalog (products, product types/groups/brands/models, task statuses/types) is editable by any admin and affects every tenant: decide whether to restrict it to super admins

### Data integrity
- [ ] Stock can still go negative at model level (availability is validated only in the UI actions)
- [ ] Add foreign keys on `inventories.product_id` and on the product columns of `stocks` (clean up orphan rows first)
- [ ] `EditStock` delete action: deleting a stock cascades reorders/reorder order items and nulls movement item references; protect it (`PreventRelatedDeletion` or restrict FKs)
- [ ] Stock uniqueness is not enforced when `inventory_position_id` is NULL
- [ ] `Movement::onSaving` fills the location only when empty: derive it from the position every time
- [ ] Inventory locations can never be deleted (`defaultPosition()` always creates a related position)
- [ ] Reorder proposals: every click creates a new draft even if an open order already contains the same stocks; outside the panel the order scope is taken from the first rule
- [ ] `ScopeSeeder` creates demo scopes during baseline seeding, reactivates scopes and rewrites `created_at` on every run (a scope pending deletion becomes active again)

### Operations and setup
- [ ] Verify that `mysqldump`/`mariadb-dump` is available in the Docker image after build
- [ ] API tokens can only be created through tinker: add a command or a panel page to issue/revoke tokens with abilities
- [ ] CI: align PHP version (8.4 in CI, 8.5 in Docker), add a Pint check and a `permissions:` block, pin actions to a SHA
- [ ] README: document the new behaviours (confirmation of `migrate:seed*` in production, required `BOOTSTRAP_ADMIN_PASSWORD`, admin vs super admin rules) and warn to back up before `php artisan migrate` (the duplicate stock merge is irreversible)

### Performance
- [ ] Cascading saves: `Inventory`, `InventoryLocation`, `Product`, `ProductType` re-save every related stock/movement item on save; replace with bulk updates of the changed columns
- [ ] `MovementItem::isLast()` runs one query per table row (twice in `MovementItemsRelationManager`): compute it with a subquery
- [ ] `db:check` loads every stock with `Stock::all()`: use `chunkById()`
- [ ] Missing indexes: `tasks` (`task_type_id`, `task_status_id`, `user_id`, `starts_at`), movement foreign keys, `inventories.product_id`, product foreign keys

### Code quality
- [ ] Apply Pint to the ~100 files that are not formatted yet
- [ ] Add Larastan (new dependency, needs approval): plain PHPStan reports ~290 mostly false positive errors on Eloquent magic
- [ ] Remove dead code: `UserResource\RelationManagers\GroupsRelationManager` (no `groups` relation), `config/scopes.php` `console_bypass_commands` (unused, still mentioned in the README), unused static helpers in `InventoryResource` (lines ~62-94)
- [ ] Deduplicate the inventory table/filters (`InventoryResource`, `TaskResource\InventoriesRelationManager`, `StockResource`) and the `requestDeletion` action (`ScopeResource`, `EditScope`)

### Tests
- [ ] `UserFactory` attaches every user to the `default` scope, and `StockFactory`/`MovementFactory` create related records in different scopes: this hides cross-scope issues in tests
- [ ] Missing coverage: interactive `zephyr:setup` flow, URL tampering across tenants for Movement/Reorder/Location/Task resources, scope purge with full domain data, changing origin/destination of an existing movement

---

## Notes
- Current version: v0.1.2
- Target: v0.2.0
- Additional items will be added later
