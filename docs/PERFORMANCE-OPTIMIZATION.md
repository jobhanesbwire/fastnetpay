# FASTNETPAY Performance Optimization

## Production Audit Snapshot

Audit date: `2026-07-20`

- CPU: 4 vCPU
- RAM: about 5.8 GiB
- Disk: about 99 GiB total, about 17% used
- Load average during audit: low (`0.08`, `0.04`, `0.01`)
- App container memory during audit: about 42 MiB
- MySQL memory during audit: about 430 MiB
- OPcache: enabled
- OPcache validate timestamps: disabled
- Redis: available in the updated production compose

Measured from the VPS during audit:

- Root site: about `0.070s`
- SuperAdmin login: about `0.079s`
- SuperAdmin dashboard unauthenticated redirect path: about `0.052s`
- Customers unauthenticated redirect path: about `0.063s`
- Routers unauthenticated redirect path: about `0.078s`
- Hotspot packages invalid-token response: about `0.127s`
- Jovi callback invalid GET response: about `0.062s`

## Changes Added

- Nginx upstream with keepalive.
- `/healthz` and `/readyz` endpoints.
- Redis service in production compose.
- Opt-in Redis session support.
- MySQL production baseline config:
  - `innodb_buffer_pool_size=1G`
  - slow query log enabled
  - `long_query_time=1.5`
  - larger temporary table limits
  - `skip_name_resolve=ON`

## What Not To Cache

Do not cache:

- Authenticated dashboards.
- Tenant-specific pages with sessions.
- Payment callbacks.
- Hotspot payment status endpoints.
- Router provisioning/API responses.

## Slow Route Investigation

When pages slow down:

1. Check `docker stats`.
2. Check `/readyz`.
3. Check MySQL slow query log.
4. Check dashboard widgets for live router scans.
5. Check log table growth and pagination.
6. Check tenant domain resolution queries.
7. Confirm external payment/SMS providers are not called during page rendering.

## Index Candidates

Review and add indexes only after checking existing schema:

- `tenant_id`
- `router_id`
- `customer_id`
- `status`
- `created_at`
- `expires_at`
- `transaction_code`
- `account_reference`
- `phone`
- `mac_address`
- `invoice_id`
- tenant domain/subdomain fields

Use non-destructive migrations and test in staging first.
