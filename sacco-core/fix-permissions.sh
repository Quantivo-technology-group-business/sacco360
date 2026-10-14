#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

code_dir="${ROOT}/code"

mkdir -p \
    "${code_dir}/storage/app/public" \
    "${code_dir}/storage/framework/cache/data" \
    "${code_dir}/storage/framework/sessions" \
    "${code_dir}/storage/framework/views" \
    "${code_dir}/storage/logs" \
    "${code_dir}/bootstrap/cache" \
    "${code_dir}/public/uploads"

chmod -R 775 "${code_dir}/storage" "${code_dir}/bootstrap/cache" "${code_dir}/public/uploads"

if [ -f "${code_dir}/.env" ]; then
    chmod 666 "${code_dir}/.env"
fi

docker compose exec -T -u root app bash -c '
    chown -R www-data:www-data storage bootstrap/cache public/uploads .env
    chmod -R 775 storage bootstrap/cache public/uploads
    chmod 664 .env
    php artisan storage:link --force 2>/dev/null || true
'

echo "Permissions fixed. Retry: http://149.102.129.28:8080/install"
