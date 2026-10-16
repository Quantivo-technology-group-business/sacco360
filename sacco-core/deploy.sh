#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

if [ ! -f .env ]; then
    cp .env.docker.example .env
    echo "Created .env from .env.docker.example"
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is not installed. Install Docker first, then run this script again."
    exit 1
fi

set_permissions() {
    local code_dir="${ROOT}/code"

    echo "Setting folder permissions..."

    mkdir -p \
        "${code_dir}/storage/app/public" \
        "${code_dir}/storage/framework/cache/data" \
        "${code_dir}/storage/framework/sessions" \
        "${code_dir}/storage/framework/views" \
        "${code_dir}/storage/logs" \
        "${code_dir}/bootstrap/cache" \
        "${code_dir}/public/uploads"

    chmod -R 775 \
        "${code_dir}/storage" \
        "${code_dir}/bootstrap/cache" \
        "${code_dir}/public/uploads"

    if [ -f "${code_dir}/.env" ]; then
        chmod 666 "${code_dir}/.env"
    elif [ -f "${code_dir}/.env.example" ]; then
        cp "${code_dir}/.env.example" "${code_dir}/.env"
        chmod 666 "${code_dir}/.env"
    fi

    if docker compose ps --status running --services 2>/dev/null | grep -q '^app$'; then
        docker compose exec -T -u root app bash -c '
            chown -R www-data:www-data storage bootstrap/cache public/uploads .env
            chmod -R 775 storage bootstrap/cache public/uploads
            chmod 664 .env
            php artisan storage:link --force 2>/dev/null || true
        '
    fi
}

set_permissions

run_pending_migrations() {
    if ! docker compose ps --status running --services 2>/dev/null | grep -q '^app$'; then
        echo "App container is not running; skipping migrations."
        return
    fi

    echo "Waiting for database..."
    docker compose exec -T app bash -c '
        set -e
        if [ -n "${DB_HOST:-}" ]; then
            until php -r "
                try {
                    new PDO(
                        \"mysql:host=${DB_HOST};port=${DB_PORT:-3306}\",
                        \"${DB_USERNAME}\",
                        \"${DB_PASSWORD}\"
                    );
                    exit(0);
                } catch (Exception \$e) {
                    exit(1);
                }
            " 2>/dev/null; do
                sleep 2
            done
        fi
    '

    echo "Running pending migrations..."
    docker compose exec -T app bash -c '
        set -e
        php artisan config:clear 2>/dev/null || true
        php artisan migrate --force --no-interaction
        php artisan module:migrate --force --no-interaction
    '

    echo "Migrations complete."
}

docker compose up -d --build

set_permissions

run_pending_migrations

APP_URL="$(grep -E '^APP_URL=' .env | cut -d= -f2- || true)"
APP_PORT="$(grep -E '^APP_PORT=' .env | cut -d= -f2 || true)"
APP_PORT_ALT="$(grep -E '^APP_PORT_ALT=' .env | cut -d= -f2- || true)"
APP_PORT="${APP_PORT:-8080}"
APP_PORT_ALT="${APP_PORT_ALT:-8088}"
APP_HOST="$(grep -E '^APP_HOST=' .env | cut -d= -f2- || true)"
APP_HOST="${APP_HOST:-localhost}"
APP_URL="${APP_URL:-http://${APP_HOST}:${APP_PORT}}"

echo ""
echo "Ultimate Loan Manager deployed."
echo "Primary URL:  ${APP_URL}"
echo "Alternate URL: http://${APP_HOST}:${APP_PORT_ALT}"
echo ""
echo "Open ports ${APP_PORT} and ${APP_PORT_ALT} in your server firewall / hosting panel."
echo "Stop with: ./stop.sh"
