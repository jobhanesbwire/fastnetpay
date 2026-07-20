#!/usr/bin/env bash
set -Eeuo pipefail

BACKUP_DIR="${1:-}"

log() { printf '[%s] %s\n' "$(date +%Y-%m-%dT%H:%M:%S%z)" "$*"; }
die() { log "ERROR: $*" >&2; exit 1; }

[ -n "$BACKUP_DIR" ] || die "usage: $0 /path/to/backup-directory"
[ -d "$BACKUP_DIR" ] || die "backup directory not found: $BACKUP_DIR"

cd "$BACKUP_DIR"
[ -f SHA256SUMS ] || die "SHA256SUMS missing"
sha256sum -c SHA256SUMS

for archive in mysql.sql.gz app-writable.tar.gz deployment-config.tar.gz host-vpn-config.tar.gz; do
    [ -f "$archive" ] || die "$archive missing"
done

gzip -t mysql.sql.gz
tar -tzf app-writable.tar.gz >/dev/null
tar -tzf deployment-config.tar.gz >/dev/null
tar -tzf host-vpn-config.tar.gz >/dev/null || true

log "Backup verification passed: $BACKUP_DIR"
