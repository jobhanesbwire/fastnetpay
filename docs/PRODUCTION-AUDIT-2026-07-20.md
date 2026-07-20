# FASTNETPAY Production Audit - 2026-07-20

## Infrastructure Snapshot

- Host uptime: about 5 days during audit.
- CPU: 4 vCPU.
- RAM: about 5.8 GiB.
- Swap: 2 GiB, almost unused.
- Disk: about 99 GiB total, about 17% used.
- Load average: low.

## Docker Snapshot

- `fastnetpay_nginx`: serving ports 80/443.
- `fastnetpay_app`: running.
- `fastnetpay_scheduler`: running.
- `fastnetpay_db`: MySQL 8, healthy.
- `portainer`: bound to localhost.

## Observations

- Production response times were healthy during the audit.
- OPcache is enabled and configured for production.
- MySQL buffer pool was still at a low default-like value before the repo tuning file.
- No multi-origin load balancing exists yet.
- The production app directory on the VPS was not a Git checkout, so deployments should be image/Portainer/Compose based.

## Decision

Keep production as a safe single-origin stack for now. Prepare Redis sessions, Nginx upstreams, health endpoints, backups, CI/CD, staging and migration runbooks before enabling multiple app containers or Cloudflare Load Balancing.
