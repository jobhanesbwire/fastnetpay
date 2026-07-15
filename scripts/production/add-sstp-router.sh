#!/usr/bin/env bash
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Run as root on the FASTNETPAY VPS." >&2
  exit 1
fi

if [ $# -lt 2 ] || [ $# -gt 3 ]; then
  echo "Usage: $0 ROUTER_USERNAME ROUTER_VPN_IP [PASSWORD]" >&2
  echo "Example: $0 fastnet-rb951-001 10.100.1.1" >&2
  exit 1
fi

USER_NAME="$1"
ROUTER_IP="$2"
PASSWORD="${3:-}"
CHAP_FILE="/etc/ppp/chap-secrets"

if ! [[ "$USER_NAME" =~ ^[A-Za-z0-9._-]{3,64}$ ]]; then
  echo "Invalid username. Use 3-64 letters, numbers, dot, underscore, or hyphen." >&2
  exit 1
fi

if ! [[ "$ROUTER_IP" =~ ^10\.100\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
  echo "Router VPN IP must be inside 10.100.0.0/16, for example 10.100.1.1." >&2
  exit 1
fi

if [ -z "$PASSWORD" ]; then
  PASSWORD="$(openssl rand -base64 32 | tr -d '=+/' | cut -c1-32)"
fi

mkdir -p /etc/ppp
touch "$CHAP_FILE"
chmod 600 "$CHAP_FILE"
cp -a "$CHAP_FILE" "${CHAP_FILE}.bak.$(date +%Y%m%d%H%M%S)"

tmp="$(mktemp)"
awk -v user="$USER_NAME" '$1 != user { print }' "$CHAP_FILE" > "$tmp"
printf '%s\t*\t%s\t%s\n' "$USER_NAME" "$PASSWORD" "$ROUTER_IP" >> "$tmp"
install -m 600 "$tmp" "$CHAP_FILE"
rm -f "$tmp"

if systemctl is-active --quiet fastnetpay-sstp.service; then
  systemctl restart fastnetpay-sstp.service
fi

cat <<EOF
FASTNETPAY SSTP router account created.

Username: ${USER_NAME}
Password: ${PASSWORD}
Router VPN IP: ${ROUTER_IP}

RouterOS v6 RB951 command:
/interface sstp-client add name=sstp-fastnetpay connect-to=sstp.fastnetpay.co.ke port=4443 user="${USER_NAME}" password="${PASSWORD}" profile=default-encryption verify-server-certificate=yes add-default-route=no disabled=no

After it connects:
  ping ${ROUTER_IP}
  register this router in FASTNETPAY using IP/Host ${ROUTER_IP}
EOF
