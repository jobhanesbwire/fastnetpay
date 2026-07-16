#!/usr/bin/env bash
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Run as root on the FASTNETPAY VPS." >&2
  exit 1
fi

SSTP_HOST="${SSTP_HOST:-sstp.fastnetpay.co.ke}"
SSTP_PORT="${SSTP_PORT:-4443}"
SSTP_LOCAL_IP="${SSTP_LOCAL_IP:-10.100.0.1}"
SSTP_POOL="${SSTP_POOL:-10.100.200.10-254}"
SSTP_CERT_FILE="${SSTP_CERT_FILE:-/etc/letsencrypt/live/fastnetpay.co.ke/fullchain.pem}"
SSTP_KEY_FILE="${SSTP_KEY_FILE:-/etc/letsencrypt/live/fastnetpay.co.ke/privkey.pem}"
SSTP_CA_FILE="${SSTP_CA_FILE:-/etc/letsencrypt/live/fastnetpay.co.ke/chain.pem}"
ACCEL_REPO="${ACCEL_REPO:-https://github.com/xebd/accel-ppp.git}"
ACCEL_REF="${ACCEL_REF:-master}"
SRC_DIR="${SRC_DIR:-/usr/local/src/fastnetpay-accel-ppp}"
CONF_DIR="/etc/accel-ppp"
CONF_FILE="${CONF_DIR}/accel-ppp.conf"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TEMPLATE="${REPO_ROOT}/.docker/sstp/accel-ppp.conf"

if [ ! -f "$SSTP_CERT_FILE" ] || [ ! -f "$SSTP_KEY_FILE" ] || [ ! -f "$SSTP_CA_FILE" ]; then
  echo "Missing TLS certificate files:" >&2
  echo "  $SSTP_CERT_FILE" >&2
  echo "  $SSTP_KEY_FILE" >&2
  echo "  $SSTP_CA_FILE" >&2
  echo "Issue the FASTNETPAY wildcard certificate before installing SSTP." >&2
  exit 1
fi

if ss -ltn "( sport = :${SSTP_PORT} )" | grep -q ":${SSTP_PORT}"; then
  echo "Port ${SSTP_PORT}/tcp is already in use. Choose another SSTP_PORT or stop the conflicting service." >&2
  exit 1
fi

echo "Installing build/runtime packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y \
  git build-essential cmake pkg-config ca-certificates \
  libssl-dev libpcre3-dev libpcre2-dev liblua5.3-dev \
  libnl-3-dev libnl-genl-3-dev libnetfilter-conntrack-dev \
  ppp iproute2 iptables logrotate

echo "Building accel-ppp from ${ACCEL_REPO} (${ACCEL_REF})..."
rm -rf "$SRC_DIR"
git clone --depth 1 --branch "$ACCEL_REF" "$ACCEL_REPO" "$SRC_DIR"
cmake -S "$SRC_DIR" -B "$SRC_DIR/build" \
  -DCMAKE_BUILD_TYPE=Release \
  -DCMAKE_INSTALL_PREFIX=/usr \
  -DBUILD_IPOE_DRIVER=FALSE \
  -DBUILD_VLAN_MON_DRIVER=FALSE \
  -DBUILD_PPTP_DRIVER=FALSE
cmake --build "$SRC_DIR/build" --parallel "$(nproc)"
cmake --install "$SRC_DIR/build"

mkdir -p "$CONF_DIR" /var/log/accel-ppp /etc/ppp
if [ ! -f "$TEMPLATE" ]; then
  echo "Missing SSTP config template: $TEMPLATE" >&2
  exit 1
fi

if [ -f "$CONF_FILE" ]; then
  cp -a "$CONF_FILE" "${CONF_FILE}.bak.$(date +%Y%m%d%H%M%S)"
fi

sed \
  -e "s|{{SSTP_PORT}}|${SSTP_PORT}|g" \
  -e "s|{{SSTP_HOST}}|${SSTP_HOST}|g" \
  -e "s|{{SSTP_LOCAL_IP}}|${SSTP_LOCAL_IP}|g" \
  -e "s|{{SSTP_POOL}}|${SSTP_POOL}|g" \
  -e "s|{{SSTP_CERT_FILE}}|${SSTP_CERT_FILE}|g" \
  -e "s|{{SSTP_KEY_FILE}}|${SSTP_KEY_FILE}|g" \
  -e "s|{{SSTP_CA_FILE}}|${SSTP_CA_FILE}|g" \
  "$TEMPLATE" > "$CONF_FILE"
chmod 600 "$CONF_FILE"

if [ ! -f /etc/ppp/chap-secrets ]; then
  install -m 600 /dev/null /etc/ppp/chap-secrets
else
  chmod 600 /etc/ppp/chap-secrets
fi

cat > /etc/systemd/system/fastnetpay-sstp.service <<EOF
[Unit]
Description=FASTNETPAY SSTP VPN Server (accel-ppp)
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
ExecStart=$(command -v accel-pppd) -c ${CONF_FILE}
Restart=always
RestartSec=5
LimitNOFILE=1048576

[Install]
WantedBy=multi-user.target
EOF

cat > /etc/logrotate.d/fastnetpay-accel-ppp <<'EOF'
/var/log/accel-ppp/*.log {
    weekly
    rotate 12
    compress
    missingok
    notifempty
    sharedscripts
    postrotate
        systemctl kill -s HUP fastnetpay-sstp.service >/dev/null 2>&1 || true
    endscript
}
EOF

cat > /etc/sysctl.d/99-fastnetpay-vpn.conf <<EOF
net.ipv4.ip_forward=1
EOF
sysctl --system >/dev/null

if command -v ufw >/dev/null 2>&1; then
  if ufw status | grep -qi "Status: active"; then
    ufw allow "${SSTP_PORT}/tcp" comment "FASTNETPAY SSTP VPN"
    ufw route allow from 172.16.0.0/12 to 10.100.0.0/16 comment "FASTNETPAY app containers to router VPNs" || true
  fi
fi

systemctl daemon-reload
systemctl enable --now fastnetpay-sstp.service
sleep 2
systemctl --no-pager --full status fastnetpay-sstp.service
ss -ltnp | grep ":${SSTP_PORT}" || {
  echo "SSTP service started but port ${SSTP_PORT}/tcp is not listening. Check journalctl -u fastnetpay-sstp.service." >&2
  exit 1
}

echo
echo "FASTNETPAY SSTP is installed."
echo "Host: ${SSTP_HOST}"
echo "Port: ${SSTP_PORT}/tcp"
echo "Gateway IP: ${SSTP_LOCAL_IP}"
echo "Next: add a router account with scripts/production/add-sstp-router.sh"
