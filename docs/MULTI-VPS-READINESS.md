# FASTNETPAY Multi-VPS Readiness

## Goal

Prepare FASTNETPAY so a second origin can be added later without redesigning the application.

## Requirements Before Adding A Second VPS

- Redis sessions enabled and verified.
- Shared Redis reachable by all app origins.
- Shared MySQL or managed MySQL.
- Tenant uploads moved to object storage or replicated shared storage.
- Payment callbacks idempotent.
- Scheduler and migrations guarded so they run once.
- Central logs and monitoring.
- Health checks on every origin.

## Proposed Future Topology

```text
Cloudflare Load Balancer
  -> VPS A /healthz /readyz
  -> VPS B /healthz /readyz
Shared MySQL
Shared Redis
Shared tenant uploads/object storage
Centralized backups and logs
```

## Router And VPN Considerations

Routers should connect through WireGuard or SSTP using stable VPN IPs in the `10.100.0.0/16` management range. Do not make RouterOS API publicly reachable.

## Payment Callback Considerations

Callbacks must use stable hostnames such as:

- `callback.fastnetpay.co.ke`
- tenant payment hostnames as configured

Only the Cloudflare origin should change during scaling or migration.
