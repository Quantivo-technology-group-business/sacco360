#!/bin/bash
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Sync public URL and DB settings from Docker env into Laravel .env
sync_env() {
    local key="$1" val="$2"
    [ -z "$val" ] && return
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${val}|" .env
    else
        echo "${key}=${val}" >> .env
    fi
}
sync_env APP_URL "${APP_URL:-}"
sync_env DB_HOST "${DB_HOST:-}"
sync_env DB_PORT "${DB_PORT:-}"
sync_env DB_DATABASE "${DB_DATABASE:-}"
sync_env DB_USERNAME "${DB_USERNAME:-}"
sync_env DB_PASSWORD "${DB_PASSWORD:-}"

php artisan config:clear 2>/dev/null || true

if [ ! -d vendor ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    public/uploads

if [ -f docker/install-fontawesome-fonts.sh ]; then
    bash docker/install-fontawesome-fonts.sh
fi

chown -R www-data:www-data storage bootstrap/cache public/uploads .env
if [ -d public/fonts ]; then
    chown -R www-data:www-data public/fonts
fi
chmod -R 775 storage bootstrap/cache public/uploads
chmod 664 .env

php artisan storage:link --force 2>/dev/null || true

if [ -n "${DB_HOST}" ]; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    until php -r "
        try {
            new PDO(
                'mysql:host=${DB_HOST};port=${DB_PORT:-3306}',
                '${DB_USERNAME}',
                '${DB_PASSWORD}'
            );
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        sleep 2
    done
    echo "Database is ready."
fi

exec "$@"
