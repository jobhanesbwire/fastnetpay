# Cloudflare DNS For FASTNETPAY

Recommended records:

| Name | Type | Target | Proxy |
| --- | --- | --- | --- |
| `@` | A | VPS public IP | Proxied |
| `www` | CNAME | `fastnetpay.co.ke` | Proxied |
| `mother` | CNAME | `fastnetpay.co.ke` | Proxied |
| `*` | CNAME | `fastnetpay.co.ke` | Proxied |
| `vpn` | A | VPS public IP | DNS only |

Important: WireGuard is UDP and cannot pass through Cloudflare's orange-cloud HTTP proxy. The `vpn.fastnetpay.co.ke` record must be DNS-only.

For tenant portals, keep wildcard `*.fastnetpay.co.ke` proxied. For VPN/router management, use the dedicated DNS-only `vpn.fastnetpay.co.ke` record.
