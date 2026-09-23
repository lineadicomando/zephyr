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

### P3 - Low

### Data integrity (structural)
- [ ] Stock uniqueness is not enforced when `inventory_position_id` is NULL
- [ ] `Movement::onSaving` fills the location only when empty: derive it from the position every time
- [ ] Inventory locations can never be deleted (`defaultPosition()` always creates a related position)
- [ ] Reorder proposals: every click creates a new draft even if an open order already contains the same stocks; outside the panel the order scope is taken from the first rule

### Operations and setup
- [ ] API tokens can only be created through tinker: add a command or a panel page to issue/revoke tokens with abilities

### Performance
- [ ] Cascading saves: `Inventory`, `InventoryLocation`, `ProductType` re-save every related stock/movement item on save; replace with bulk updates of the changed columns
- [ ] `MovementItem::isLast()` runs one query per table row (twice in `MovementItemsRelationManager`): compute it with a subquery
- [ ] Missing indexes: `tasks` (`task_type_id`, `task_status_id`, `user_id`, `starts_at`), movement foreign keys, `inventories.product_id`, product foreign keys

### Code quality
- [ ] Add Larastan (new dependency, needs approval): plain PHPStan reports ~290 mostly false positive errors on Eloquent magic
- [ ] Remove dead code: `UserResource\RelationManagers\GroupsRelationManager` (no `groups` relation), unused static helpers in `InventoryResource` (lines ~62-94)
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
