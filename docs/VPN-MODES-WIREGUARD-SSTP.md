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

## Security Rule

When VPN is tested, enable “Restrict RouterOS API to the VPN server IP”. Do not expose MikroTik API directly to the public internet.
