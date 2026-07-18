# VPN Modes: WireGuard and SSTP

FASTNETPAY supports three router connection modes in the provisioning wizard.

## Default Local Setup

Use this while testing on-site. FASTNETPAY connects to the router by local IP, for example `192.168.88.1:8728`.

## WireGuard VPN

Use this for RouterOS v7 routers. Recommended management range:

```text
FASTNETPAY/VPS: 10.100.0.1
Router 1: 10.100.1.1
Router 2: 10.100.2.1
```

The wizard can generate RouterOS WireGuard interface/IP/peer commands. Add the FASTNETPAY/VPS public key before applying automatic peer configuration.

WireGuard requirements:

- RouterOS v7 with `/interface wireguard` support.
- A DNS-only endpoint such as `vpn.fastnetpay.co.ke`.
- UDP `51820` open on the VPS firewall.
- Matching server-side and router-side peers.

Cloudflare orange-cloud/proxied DNS cannot forward WireGuard UDP. Keep `vpn.fastnetpay.co.ke` DNS-only and reserve proxied DNS for web portals.

RB951 test result: the tested RB951Ui-2HnD is running RouterOS `6.49.19 (long-term)`, so WireGuard is not available on this router. Use SSTP mode for this router, or upgrade/replace with a RouterOS v7-capable router before selecting WireGuard mode.

## SSTP VPN

Use this for older RouterOS v6 routers that do not support WireGuard. Configure the SSTP server on the FASTNETPAY/VPS side first, then enter the SSTP server, username, and password in the wizard.

Production server setup is documented in:

```text
docs/SSTP-PRODUCTION-SERVER.md
```

FASTNETPAY provides:

- `scripts/production/install-sstp-accel-ppp.sh` to install/configure `accel-ppp`.
- `scripts/production/add-sstp-router.sh` to create one unique SSTP account per router.

Recommended SSTP endpoint:

```text
sstp.fastnetpay.co.ke:4443/tcp
```

Keep this DNS record DNS-only, never Cloudflare-proxied.

For RB951/RouterOS v6 commands, use `connect-to=sstp.fastnetpay.co.ke:4443`. Do not add a separate `port=4443` argument. The FASTNETPAY `accel-ppp` SSTP server also leaves SNI host enforcement off because RouterOS v6 does not send SSTP client SNI.

The tested RB951 required the Let's Encrypt `ISRG Root X1` certificate to be imported into RouterOS before `verify-server-certificate=yes` succeeded. The wizard now imports the root, marks it trusted, creates `sstp-fastnetpay`, adds a stable router API IP such as `10.100.1.1/32`, and allows management input from the VPS VPN IP `10.100.0.1`.

Use a dedicated RSA certificate for the SSTP endpoint. The FASTNETPAY wildcard web certificate may be ECDSA, which is fine for browsers but can fail TLS handshakes on older RouterOS v6 SSTP clients.

## Security Rule

When VPN is tested, enable “Restrict RouterOS API to the VPN server IP”. Do not expose MikroTik API directly to the public internet.
