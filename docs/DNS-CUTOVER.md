# FASTNETPAY DNS Cutover

## Web Records Behind Cloudflare

For proxied web records, keep hostnames the same and update the Cloudflare origin A record to the new VPS IP.

Important hostnames:

- `fastnetpay.co.ke`
- `mother.fastnetpay.co.ke`
- `*.fastnetpay.co.ke`
- `callback.fastnetpay.co.ke`
- `pay.fastnetpay.co.ke`

## VPN Records

VPN records are usually DNS-only:

- `wg.fastnetpay.co.ke`
- `sstp.fastnetpay.co.ke`

During migration, preserve server keys and VPN IP plans where possible. If changing endpoints, verify firewall and router reconnection before switching production routers.

## Before Cutover

- Verify TLS certificates on the new origin.
- Verify `/healthz` and `/readyz`.
- Verify tenant domain routing.
- Verify payment callbacks.
- Verify WireGuard/SSTP separately from web traffic.
- Reduce DNS TTL for DNS-only records in advance.

## After Cutover

- Test SuperAdmin login.
- Test tenant login.
- Test hotspot package API.
- Test STK initiation and callback settlement.
- Test router API through VPN.
- Watch Nginx, app, scheduler and MySQL logs.
