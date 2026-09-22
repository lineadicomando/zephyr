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

echo "[entrypoint] Checking migration status..."
if php artisan migrate:status --no-ansi 2>/dev/null | grep -q " Ran"; then
    echo "[entrypoint] Database already migrated — running pending migrations..."
    php artisan migrate --force
else
    echo "[entrypoint] Fresh database — running migrations and seeding..."
    if [ "${SEED_DEMO_DATA:-false}" = "true" ]; then
        php artisan migrate:seed_demo --no-fresh
    else
        php artisan migrate:seed --no-fresh
    fi
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
