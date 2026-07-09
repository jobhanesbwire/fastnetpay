# Cron Worker Performance

Heavy operational work should not run inside normal page loads.

## Keep In Cron / Background Jobs

- expiry checks
- expired Hotspot/PPPoE disconnects
- tenant invoice generation
- tenant billing snapshots
- router health checks
- VPN health checks
- large report generation
- old log cleanup/archive

## UI Health

The SuperAdmin Performance page shows expiry worker health if the worker tables exist.

Open:

```text
/?_route=performance
```

## Recommended Schedule

- expiry worker: every 5 minutes
- router health snapshots: every 1-5 minutes depending on router count
- SaaS billing snapshots: hourly and after invoice generation
- log cleanup/archive: daily

Prevent duplicate workers with a lock row/file and timeout. Long-running router/API scans should write progress logs instead of blocking admin pages.
