#!/bin/bash
set -e

# Worker containers (queue, scheduler) skip init — they depend on the app
# service being healthy, so migrations and assets are already handled.
if [ "$1" != "php-fpm" ]; then
    exec "$@"
fi

echo "[entrypoint] Waiting for database at ${DB_HOST:-db}:${DB_PORT:-3306}..."
if ! php artisan db:wait --timeout="${DB_WAIT_TIMEOUT:-120}" --no-ansi; then
    echo "[entrypoint] Database not reachable. Check DB_* in .env: with the bundled database"
    echo "[entrypoint] set COMPOSE_PROFILES=database and DB_HOST=db; with an existing database"
    echo "[entrypoint] set its host (host.docker.internal for the host machine) and credentials."
    exit 1
fi

echo "[entrypoint] Ensuring storage directory structure..."
mkdir -p /var/www/html/storage/{app/public,logs,framework/{cache,sessions,views}}

echo "[entrypoint] Syncing public assets to shared volume..."
rsync -a --delete /var/www/html/public/ /var/www/html/public-vol/

# The baseline data is seeded until the database has a user, so a first boot
# whose seed failed (e.g. placeholder admin password) is retried on restart.
echo "[entrypoint] Running migrations (seeding when the database has no users)..."
# Demo data needs Faker, which the production image (--no-dev) does not include.
if [ "${SEED_DEMO_DATA:-false}" = "true" ] && php -r 'require "vendor/autoload.php"; exit(class_exists(Faker\Generator::class) ? 0 : 1);'; then
    php artisan migrate:seed_demo --no-fresh --if-not-seeded
else
    if [ "${SEED_DEMO_DATA:-false}" = "true" ]; then
        echo "[entrypoint] SEED_DEMO_DATA=true ignored: Faker is not installed (see \"Loading demo data manually\" in the README)."
    fi
    php artisan migrate:seed --no-fresh --if-not-seeded
fi

if [ "${APP_ENV}" = "production" ]; then
    echo "[entrypoint] Caching config, routes, views, events..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

echo "[entrypoint] Starting PHP-FPM..."
exec "$@"
