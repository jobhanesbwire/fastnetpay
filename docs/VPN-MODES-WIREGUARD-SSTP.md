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

## SSTP VPN

Use this for older RouterOS v6 routers that do not support WireGuard. Configure the SSTP server on the FASTNETPAY/VPS side first, then enter the SSTP server, username, and password in the wizard.

## Security Rule

When VPN is tested, enable “Restrict RouterOS API to the VPN server IP”. Do not expose MikroTik API directly to the public internet.
