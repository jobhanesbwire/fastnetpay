#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${FASTNETPAY_APP_DIR:-/opt/fastnetpay/app}"
BACKUP_DIR=""
CONFIRM="no"
COMPOSE_FILES=(-f docker-compose.prod.yml -f compose.production.yml)

while [ "$#" -gt 0 ]; do
    case "$1" in
        --backup) BACKUP_DIR="${2:-}"; shift 2 ;;
        --yes) CONFIRM="yes"; shift ;;
        --app-dir) APP_DIR="${2:-}"; shift 2 ;;
        *) echo "Unknown argument: $1" >&2; exit 1 ;;
    esac
done

log() { printf '[%s] %s\n' "$(date +%Y-%m-%dT%H:%M:%S%z)" "$*"; }
die() { log "ERROR: $*" >&2; exit 1; }

[ -n "$BACKUP_DIR" ] || die "usage: $0 --backup /path/to/backup-directory [--yes]"
[ -d "$BACKUP_DIR" ] || die "backup directory not found: $BACKUP_DIR"

"$(dirname "$0")/verify-fastnetpay-backup.sh" "$BACKUP_DIR"

if [ "$CONFIRM" != "yes" ]; then
    log "Dry run only. Re-run with --yes to restore files and database."
    log "Target app directory would be: $APP_DIR"
    exit 0
fi

cd "$APP_DIR" || die "FASTNETPAY app directory not found: $APP_DIR"

log "Stopping application containers before restore"
docker compose "${COMPOSE_FILES[@]}" stop fastnetpay_app fastnetpay_scheduler || true

log "Restoring writable files"
tar -xzf "$BACKUP_DIR/app-writable.tar.gz" -C "$APP_DIR"

log "Restoring deployment config"
tar -xzf "$BACKUP_DIR/deployment-config.tar.gz" -C "$APP_DIR"

log "Restoring MySQL database"
gzip -dc "$BACKUP_DIR/mysql.sql.gz" | docker compose "${COMPOSE_FILES[@]}" exec -T fastnetpay_db sh -lc \
    'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'

log "Starting application containers"
docker compose "${COMPOSE_FILES[@]}" up -d

log "Restore complete. Run validate-fastnetpay-migration.sh next."
