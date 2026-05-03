# Zephyr 

An open-source IT asset management system built with Laravel and Filament.

[![License](https://img.shields.io/badge/license-AGPLv3-green.svg)](LICENSE)
[![Version](https://img.shields.io/github/v/tag/lineadicomando/zephyr?sort=semver&label=version)](https://github.com/lineadicomando/zephyr/tags)
[![Issues](https://img.shields.io/github/issues/lineadicomando/zephyr)](https://github.com/lineadicomando/zephyr/issues)
[![CI](https://github.com/lineadicomando/zephyr/actions/workflows/tests.yml/badge.svg)](https://github.com/lineadicomando/zephyr/actions/workflows/tests.yml)

## Features

- **Multi-scope operations** — users can belong to multiple scopes and switch `active_scope_id` at runtime; scoped models are filtered by active scope.
- **Scope lifecycle guardrails** — protected scopes cannot be deactivated/deleted; inactive scopes are deletion-requested with grace period and purged by scheduler.
- **Inventory domain** — inventories, inventory locations/positions, stocks, movements, and movement items with scope isolation.
- **Task management with calendar** — tasks linked to inventories with FullCalendar integration (drag/drop and date updates).
- **Reorder workflow** — reorder rules, proposal/evaluation services, and reorder orders lifecycle (`draft -> requested -> ordered -> received/canceled`).
- **Product catalog** — products and dictionaries (brand/group/model/type), managed in Filament and exposed via API.
- **AuthZ and access control** — Laravel auth + Sanctum, Spatie permissions, Filament Shield policies, and per-resource authorization checks.
- **Operational tooling** — interactive bootstrap (`zephyr:setup`), seed flows (`migrate:seed`, `migrate:seed_demo`), diagnostics (`db:check`), backup scheduling, and scope purge scheduling.

## Stack

- PHP 8.3 / Laravel 13
- Filament 5 (admin panel)
- Vite + Tailwind CSS 4
- Pest (testing)

## Getting Started

### Requirements

- PHP 8.3+
- Composer 2
- Node.js 20+ and npm
- MariaDB/MySQL (or SQLite for quick local tests)

### Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/lineadicomando/zephyr.git
cd zephyr
composer install
npm install
```

Create and configure the environment:

```bash
php artisan zephyr:setup
```

Or use the non-interactive bootstrap script:

```bash
composer run setup
```

Start the local development stack:

```bash
composer run dev
```

### Setup Workflows
`zephyr:setup` is the recommended first-run flow: it interactively creates `.env` from `.env.example`, asks for DB / locale / bootstrap admin values and whether to load demo data, then runs `migrate:seed` or `migrate:seed_demo`.

`composer run setup` is the non-interactive bootstrap script: it installs dependencies, creates `.env` if missing, generates the app key, runs migrations, and builds frontend assets.

`composer run dev` starts the Laravel server, queue worker, log viewer, and Vite dev server concurrently.

## Testing

```bash
composer test
```

## Versioning

- The only source of truth for project version is Git tags.
- Do not maintain version numbers in application files or docs; releases are identified by tags (for example `v1.2.0`).

## Global Scopes (v1)

Zephyr supports multiple flat operational scopes through a neutral `scopes` entity (for example `company`, `school`).

- Global entities: `users`, `products`, and product catalog dictionaries (`product_brands`, `product_groups`, `product_models`, `product_types`).
- Scoped entities: operational records (inventory, movements, tasks, reorders, orders).
- Runtime context: one `active_scope_id` in session per authenticated user.
- Access model: users can be assigned to one or more scopes and can switch active scope from the Filament user menu.
- Console bypass policy: scope filtering bypass is limited to maintenance commands (`migrate*`, `db:seed`, `db:wipe`) via `config/scopes.php`.

See [docs/architecture/global-scopes.md](docs/architecture/global-scopes.md) for architecture details.

### Scope deletion workflow

- The default scope is marked as protected and cannot be deactivated or deleted.
- Scope deletion is deferred: only inactive, non-protected scopes can receive a delete request, which sets `pending_delete` to `now + SCOPE_DELETE_GRACE_HOURS` (default `24`).
- Final purge is executed by the scheduler command `scopes:purge-pending` (hourly, `withoutOverlapping`), which removes the scope and related records.

## License

GNU AGPLv3 or later (AGPL-3.0-or-later)

### Licensing note

This project is licensed under GNU AGPLv3-or-later. If you run a modified version of this software for users over a network (for example as a SaaS/web service), you must make the corresponding source code of your modified version available to those users under AGPL terms.

Third-party dependencies keep their own licenses (for example MIT, BSD, Apache-2.0, LGPL-2.1-or-later). Review them before redistribution in regulated environments.
