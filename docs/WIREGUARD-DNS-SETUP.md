# WireGuard DNS Setup

WireGuard must not use Cloudflare orange-cloud proxying.

Required DNS:

```text
wg.fastnetpay.co.ke A 212.95.35.229 DNS-only
```

Server endpoint:

```text
wg.fastnetpay.co.ke:51820/udp
```

Current VPS WireGuard server:

```text
Interface: wg0
Address: 10.100.0.1/16
ListenPort: 51820
```

RouterOS v7 routers can use WireGuard. RouterOS v6 routers such as the tested RB951 should use SSTP instead.

Production checks:

- `wg.fastnetpay.co.ke` must resolve directly to `212.95.35.229`.
- UDP `51820` must be open on the VPS firewall.
- `wg-quick@wg0` should be enabled at boot.
- MikroTik API should be reachable over VPN addresses only, not exposed publicly.

Recommended management network:

```text
FASTNETPAY/VPS: 10.100.0.1
Router 1:       10.100.1.1
Router 2:       10.100.2.1
```

RouterOS support:

- RouterOS v7: prefer WireGuard.
- RouterOS v6: use SSTP fallback because WireGuard is not available.
