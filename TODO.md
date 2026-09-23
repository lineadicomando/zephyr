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

The items of the September 2026 code reviews are fixed: see the git history.

- [ ] Upgrade `bezhansalleh/filament-shield` to 4.3+ (pinned to 4.2): it rejects the `_` separator with the `snake` case, so every permission (`view_any_product`...) must be renamed in the database, policies and seeders
- [ ] Larastan (`composer analyse`, level 5) still has 67 known errors in `phpstan-baseline.neon`: fix them and shrink the baseline

---

## Notes
- Current version: v0.1.2
- Target: v0.2.0
- Additional items will be added later
