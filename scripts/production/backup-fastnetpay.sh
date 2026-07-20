#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${FASTNETPAY_APP_DIR:-/opt/fastnetpay/app}"
BACKUP_ROOT="${FASTNETPAY_BACKUP_ROOT:-/opt/fastnetpay/backups}"
RETENTION_DAYS="${FASTNETPAY_BACKUP_RETENTION_DAYS:-14}"
MIN_FREE_MB="${FASTNETPAY_BACKUP_MIN_FREE_MB:-2048}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="${BACKUP_ROOT}/${TIMESTAMP}"
COMPOSE_FILES=(-f docker-compose.prod.yml -f compose.production.yml)

log() { printf '[%s] %s\n' "$(date +%Y-%m-%dT%H:%M:%S%z)" "$*"; }
die() { log "ERROR: $*" >&2; exit 1; }

command -v docker >/dev/null || die "docker is required"
command -v gzip >/dev/null || die "gzip is required"
command -v tar >/dev/null || die "tar is required"

cd "$APP_DIR" || die "FASTNETPAY app directory not found: $APP_DIR"
mkdir -p "$BACKUP_ROOT"
chmod 700 "$BACKUP_ROOT" 2>/dev/null || true

free_mb="$(df -Pm "$BACKUP_ROOT" | awk 'NR==2 {print $4}')"
if [ "${free_mb:-0}" -lt "$MIN_FREE_MB" ]; then
    die "not enough free space in $BACKUP_ROOT (${free_mb}MiB available, ${MIN_FREE_MB}MiB required)"
fi

umask 077
mkdir -p "$BACKUP_DIR"
log "Creating FASTNETPAY backup in $BACKUP_DIR"

docker compose "${COMPOSE_FILES[@]}" ps > "$BACKUP_DIR/docker-ps.txt" 2>&1 || true
docker compose "${COMPOSE_FILES[@]}" config > "$BACKUP_DIR/docker-compose.rendered.yml"

log "Dumping MySQL"
docker compose "${COMPOSE_FILES[@]}" exec -T fastnetpay_db sh -lc \
    'mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --single-transaction --routines --triggers "$MYSQL_DATABASE"' \
    | gzip -9 > "$BACKUP_DIR/mysql.sql.gz"
gzip -t "$BACKUP_DIR/mysql.sql.gz"

log "Archiving application writable data"
tar -czf "$BACKUP_DIR/app-writable.tar.gz" \
    --ignore-failed-read \
    system/uploads \
    ui/cache \
    ui/compiled \
    system/cache \
    qrcode/cache 2>"$BACKUP_DIR/app-writable.warnings.log" || true

log "Archiving deployment configuration"
tar -czf "$BACKUP_DIR/deployment-config.tar.gz" \
    --ignore-failed-read \
    docker-compose.prod.yml \
    compose.production.yml \
    .env.production \
    .docker/nginx \
    .docker/mysql \
    .docker/sstp \
    scripts/production 2>"$BACKUP_DIR/deployment-config.warnings.log" || true

log "Archiving host VPN/SSTP configuration when available"
tar -czf "$BACKUP_DIR/host-vpn-config.tar.gz" \
    --ignore-failed-read \
    /etc/wireguard \
    /etc/accel-ppp.conf \
    /etc/ppp \
    /opt/fastnetpay/sstp \
    /opt/fastnetpay/router-vpn 2>"$BACKUP_DIR/host-vpn-config.warnings.log" || true

{
    echo "backup_created_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    echo "hostname=$(hostname)"
    echo "app_dir=$APP_DIR"
    echo "git_commit=$(git rev-parse HEAD 2>/dev/null || echo not-a-git-repo)"
    echo "retention_days=$RETENTION_DAYS"
} > "$BACKUP_DIR/manifest.txt"

(
    cd "$BACKUP_DIR"
    sha256sum mysql.sql.gz app-writable.tar.gz deployment-config.tar.gz host-vpn-config.tar.gz manifest.txt > SHA256SUMS
)

log "Pruning backups older than ${RETENTION_DAYS} days"
find "$BACKUP_ROOT" -mindepth 1 -maxdepth 1 -type d -mtime +"$RETENTION_DAYS" -print -exec rm -rf {} \; 2>/dev/null || true

log "Backup complete: $BACKUP_DIR"
