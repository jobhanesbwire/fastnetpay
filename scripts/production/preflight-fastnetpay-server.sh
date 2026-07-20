#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${FASTNETPAY_APP_DIR:-/opt/fastnetpay/app}"
MIN_RAM_MB="${FASTNETPAY_MIN_RAM_MB:-4096}"
MIN_DISK_MB="${FASTNETPAY_MIN_DISK_MB:-20480}"

ok() { printf '[OK] %s\n' "$*"; }
warn() { printf '[WARN] %s\n' "$*" >&2; }
fail() { printf '[FAIL] %s\n' "$*" >&2; exit 1; }

command -v docker >/dev/null || fail "Docker is not installed"
docker compose version >/dev/null || fail "Docker Compose plugin is not available"

ram_mb="$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo 2>/dev/null || echo 0)"
[ "$ram_mb" -ge "$MIN_RAM_MB" ] && ok "RAM ${ram_mb}MiB" || warn "RAM ${ram_mb}MiB is below recommended ${MIN_RAM_MB}MiB"

disk_mb="$(df -Pm "$APP_DIR" 2>/dev/null | awk 'NR==2 {print $4}')"
[ "${disk_mb:-0}" -ge "$MIN_DISK_MB" ] && ok "Free disk ${disk_mb}MiB" || warn "Free disk ${disk_mb:-0}MiB is below recommended ${MIN_DISK_MB}MiB"

if ss -ltn 2>/dev/null | awk '{print $4}' | grep -Eq ':(80|443)$'; then
    ok "HTTP/HTTPS listeners present"
else
    warn "No listener found on 80/443 yet"
fi

if [ -d "$APP_DIR" ]; then
    ok "App directory exists: $APP_DIR"
else
    warn "App directory missing: $APP_DIR"
fi

if [ -f "$APP_DIR/.env.production" ]; then
    ok "Production env file exists"
else
    warn ".env.production missing. Create it from .env.production.example with real secrets."
fi

if [ -d /etc/wireguard ]; then ok "WireGuard config directory exists"; else warn "WireGuard directory not found"; fi
if [ -f /etc/accel-ppp.conf ] || [ -d /etc/ppp ]; then ok "SSTP/PPP config paths exist"; else warn "SSTP/PPP config paths not found"; fi

ok "Preflight completed"
