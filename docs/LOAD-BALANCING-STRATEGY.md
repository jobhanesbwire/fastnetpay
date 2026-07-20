# FASTNETPAY Load-Balancing Strategy

## Current Status

FASTNETPAY currently runs as a single production origin on one VPS behind Cloudflare. Cloudflare gives TLS proxying, CDN behavior, bot/DDoS filtering and edge protection, but it does not create a second FASTNETPAY origin by itself.

The current Docker topology is:

- `fastnetpay_nginx`
- `fastnetpay_app`
- `fastnetpay_scheduler`
- `fastnetpay_db`
- `fastnetpay_redis`

The production Nginx config now uses an upstream named `fastnetpay_app_pool` with `least_conn` and keepalive. Today that upstream points at the single app container. This improves connection reuse and prepares the config for future app instances without claiming high availability too early.

## Why Multiple App Containers Are Not Enabled Yet

Multiple app containers are safe only when the app is stateless:

- Sessions must be shared through Redis.
- Cache must be shared or disposable.
- Uploads and tenant files must live on shared persistent storage.
- Scheduler and migration jobs must not run once per app container.
- Payment callbacks must remain idempotent.

The production compose file includes Redis and opt-in Redis session support, but `.env.production.example` intentionally keeps `SESSION_HANDLER=files` until Redis sessions are validated in staging.

## Single-VPS Recommendation

Use one app container for live production until the staging environment proves:

- Login sessions survive app container restart.
- Tenant sessions do not cross domains.
- Captive portal sessions and MPESA/Jovi-Pay callbacks work with Redis sessions.
- Uploads are shared outside app container filesystem.
- Scheduler remains a single worker.

After that validation, add app containers behind `fastnetpay_app_pool` and keep the scheduler as one separate service.

## Future Multi-VPS Topology

Recommended later topology:

```text
Cloudflare Load Balancer
  -> Origin VPS A: Nginx -> FASTNETPAY app workers
  -> Origin VPS B: Nginx -> FASTNETPAY app workers
Shared MySQL / managed MySQL
Shared Redis
Shared object storage or replicated uploads
Centralized logs and backups
```

Cloudflare Load Balancing becomes useful only after there are at least two healthy origins or a real failover target.

## Health Endpoints

Nginx exposes:

- `/healthz` -> `/?_route=api/health`
- `/readyz` -> `/?_route=api/ready`

Use `/healthz` for basic uptime and `/readyz` for database/Redis readiness.
