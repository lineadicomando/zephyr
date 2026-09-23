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

### Operations and setup

### Performance

### Code quality
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
