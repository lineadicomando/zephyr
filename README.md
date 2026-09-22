<p align="center">
  <img src="public/images/logo.svg" alt="Zephyr" width="280" />
</p>

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

- PHP 8.5 / Laravel 13
- Filament 5 (admin panel)
- Vite + Tailwind CSS 4
- Pest (testing)

## Getting Started

### Requirements

- PHP 8.5+
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
`zephyr:setup` is the recommended first-run flow: it interactively creates `.env` from `.env.example`, asks for the environment (`production` by default, with `LOG_LEVEL=error`), DB / locale / bootstrap admin values, the backup archive password and whether to load demo data, then runs `migrate:seed` or `migrate:seed_demo`.

`composer run setup` is the non-interactive bootstrap script: it installs dependencies, creates `.env` if missing, generates the app key, runs migrations, and builds frontend assets.

`composer run dev` starts the Laravel server, queue worker, log viewer, and Vite dev server concurrently.

## Docker Deploy

The repository ships a `docker-compose.yml` with six services: `app` (PHP-FPM 8.5), `nginx`, `db` (MariaDB 11), `redis`, `queue`, and `scheduler`. On first boot the `app` container runs migrations and seeds the database; subsequent restarts only apply pending migrations and skip seeding.

### Requirements

- Docker 23+ (or Podman with `podman-compose`)
- BuildKit enabled (default in Docker 23+)

### First-time setup

```bash
# 1. Create the environment file from the Docker template
cp .env.docker .env

# 2. Generate an application key and paste the output as APP_KEY in .env
php artisan key:generate --show
# or, without PHP:
echo "base64:$(openssl rand -base64 32)"

# 3. Edit .env: set strong passwords and update APP_URL if needed
#    DB_PASSWORD, DB_ROOT_PASSWORD, BOOTSTRAP_ADMIN_PASSWORD
#    (to use an existing database instead of the bundled one, see "Existing database")
#    APP_URL=http://your-host:8080
#    SEED_DEMO_DATA=true  # set to true to load demo data on first boot

# 4. Build and start all services
docker compose up -d --build
```

The first build takes 5–10 minutes (PHP extension compilation, npm install, composer install). Subsequent builds use cached layers and are much faster.

Once the `app` container is healthy, visit `http://localhost:8080` (or the URL set in `APP_URL`) and log in with the credentials set in `BOOTSTRAP_ADMIN_EMAIL` / `BOOTSTRAP_ADMIN_PASSWORD`.

### Useful commands

```bash
# Follow application logs
docker compose logs -f app

# Run an Artisan command inside the container
docker compose exec app php artisan tinker

# Stop all services (data volumes are preserved)
docker compose down

# Stop and destroy all data volumes
docker compose down -v
```

### Existing database

By default Compose starts a dedicated MariaDB service (`db`, enabled by `COMPOSE_PROFILES=database` in `.env`). To use a database server you already run (MariaDB 10.6+ or MySQL 8+), disable the bundled service and point the `DB_*` variables to it:

```dotenv
COMPOSE_PROFILES=
DB_CONNECTION=mariadb   # or mysql
DB_HOST=...             # see below
DB_PORT=3306
DB_DATABASE=zephyr
DB_USERNAME=zephyr
DB_PASSWORD=...
```

| Where the database runs | `DB_HOST` | Start command |
|---|---|---|
| On the Docker/Podman host | `host.docker.internal` | `docker compose up -d` |
| In another container (e.g. another Compose stack) | container name or network alias | `docker compose -f docker-compose.yml -f docker-compose.external-network.yml up -d` with `DB_EXTERNAL_NETWORK=<network>` in `.env` |
| On a remote server | hostname or IP | `docker compose up -d` |

Requirements:

- Use a **dedicated, empty database**: on first boot the entrypoint runs the migrations and the seeders when no migration has been run yet, and on every boot it runs the pending migrations. Do not share the database with other applications.
- The user needs full privileges on that database (tables are created, altered and dropped by migrations):

  ```sql
  CREATE DATABASE zephyr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'zephyr'@'%' IDENTIFIED BY 'change-me';
  GRANT ALL PRIVILEGES ON zephyr.* TO 'zephyr'@'%';
  ```

- A database on the host must listen on an address reachable from the containers (not only `127.0.0.1`) and accept connections from the container network.
- For TLS connections mount the CA certificate in the container and set `MYSQL_ATTR_SSL_CA` to its path.
- At startup the app waits up to `DB_WAIT_TIMEOUT` seconds (default 120) for the database (`php artisan db:wait`), then stops with an explicit error.
- Backups (`backup:run`) dump the database through the network with the MariaDB client included in the image; `DB_ROOT_PASSWORD` and the `db` volume are not used.

### Configuration notes

- With the bundled database `DB_HOST` must be `db`; `REDIS_HOST` must be `redis` (the Compose service names).
- `CACHE_STORE`, `SESSION_DRIVER`, and `QUEUE_CONNECTION` are set to `redis` in `.env.docker` — do not change them to `file`/`sync` in production.
- The default port is `8080` (set via `APP_PORT` in `.env`). Change it to any free port and update `APP_URL` accordingly.
- Behind a TLS terminating reverse proxy set `APP_URL=https://...` and `TRUSTED_PROXIES`: session cookies are then sent over HTTPS only (override with `SESSION_SECURE_COOKIE=true|false`).
- Persistent data is stored in named Docker volumes (`db` with the bundled database, `redis`, `storage`); back them up before running `docker compose down -v`.

### Loading demo data manually

The image is built without dev dependencies (`composer install --no-dev`), so demo seeders that rely on Faker are not available by default. To load demo data into a running container without rebuilding:

```bash
# temporarily install dev dependencies (ephemeral — lost on container restart)
docker compose exec -u root app composer install

# run the demo seeder
docker compose exec app php artisan migrate:seed_demo
```

## Backups

`backup:run` runs every hour (disable with `BACKUP_ENABLED=false`) and archives the database dump, `storage/app` (uploaded files) and `.env` (needed to decrypt data encrypted with `APP_KEY`; in Docker it lives outside the container and is not included). Set `BACKUP_ARCHIVE_PASSWORD` to encrypt the archives and store it outside the server: it is required to restore them.

## API

The REST API (`/api/products`) authenticates with Sanctum personal access tokens. Tokens need an ability for each group of endpoints: `products:read` for `GET` and `products:write` for `POST`/`PUT`/`PATCH` (the user permissions still apply):

```bash
php artisan tinker --execute '$user = App\Models\User::where("email", "ansible@example.com")->first(); echo $user->createToken("ansible", ["products:read", "products:write"])->plainTextToken;'
```

Tokens expire after `SANCTUM_TOKEN_EXPIRATION` minutes (default 525600, one year; `0` disables the expiration). Requests are limited to `API_RATE_LIMIT_PER_MINUTE` per user/IP.

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
