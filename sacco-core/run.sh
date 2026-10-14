#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

if [ ! -f .env ]; then
    cp .env.docker.example .env
    echo "Created .env from .env.docker.example"
fi

docker compose up -d --build

APP_URL="$(grep -E '^APP_URL=' .env | cut -d= -f2- || true)"
APP_PORT="$(grep -E '^APP_PORT=' .env | cut -d= -f2 || true)"
APP_PORT_ALT="$(grep -E '^APP_PORT_ALT=' .env | cut -d= -f2- || true)"
APP_PORT="${APP_PORT:-8080}"
APP_PORT_ALT="${APP_PORT_ALT:-8088}"
APP_URL="${APP_URL:-http://149.102.129.28:${APP_PORT}}"

echo ""
echo "Ultimate Loan Manager is running."
echo "Primary URL:   ${APP_URL}"
echo "Alternate URL: http://149.102.129.28:${APP_PORT_ALT}"
echo "Stop with: ./stop.sh"
