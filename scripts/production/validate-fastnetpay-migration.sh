#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${FASTNETPAY_APP_DIR:-/opt/fastnetpay/app}"
BASE_URL="${FASTNETPAY_BASE_URL:-https://mother.fastnetpay.co.ke}"
ROOT_URL="${FASTNETPAY_ROOT_URL:-https://fastnetpay.co.ke}"
CALLBACK_URL="${FASTNETPAY_CALLBACK_URL:-https://callback.fastnetpay.co.ke/?_route=api/health}"
COMPOSE_FILES=(-f docker-compose.prod.yml -f compose.production.yml)

log() { printf '[%s] %s\n' "$(date +%Y-%m-%dT%H:%M:%S%z)" "$*"; }
check_url() {
    local label="$1" url="$2"
    local code total
    code="$(curl -k -sS -o /tmp/fnp-check.out -w '%{http_code}' "$url" || true)"
    total="$(curl -k -sS -o /dev/null -w '%{time_total}' "$url" || true)"
    printf '%-28s HTTP %s %ss %s\n' "$label" "$code" "$total" "$url"
}

cd "$APP_DIR" || { log "App directory not found: $APP_DIR"; exit 1; }

log "Docker container status"
docker compose "${COMPOSE_FILES[@]}" ps

log "Health endpoints"
check_url "Root" "$ROOT_URL/"
check_url "SuperAdmin login" "$BASE_URL/?_route=login"
check_url "Health" "$BASE_URL/healthz"
check_url "Readiness" "$BASE_URL/readyz"
check_url "Callback health" "$CALLBACK_URL"

log "Database check"
docker compose "${COMPOSE_FILES[@]}" exec -T fastnetpay_db sh -lc 'mysqladmin ping -h 127.0.0.1 -u"$MYSQL_USER" -p"$MYSQL_PASSWORD"'

if docker compose "${COMPOSE_FILES[@]}" ps --services | grep -qx fastnetpay_redis; then
    log "Redis check"
    docker compose "${COMPOSE_FILES[@]}" exec -T fastnetpay_redis redis-cli ping
fi

log "Validation complete"
