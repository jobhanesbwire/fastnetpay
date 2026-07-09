# FASTNETPAY Performance Audit

Date: 2026-07-09

## Root Causes Found

- SaaS schema installation was running during normal request boot.
- `Tenant::installSchema()`, `SaasBilling::installSchema()`, `JoviPay::installSchema()`, and `RouterProvisioning::installSchema()` performed table/column/index checks repeatedly.
- Main-system localhost requests still loaded tenant-only payment/SMS settings.
- SuperAdmin SaaS analytics recalculated tenant preview data too aggressively.
- Chart.js was loaded globally on every admin page.

## Local Measurements

Before optimization:

- `/?_route=admin`: about `1.72s`, profiler later showed up to `738ms`, `8` DB queries.
- `/?_route=login`: about `3.48s`, profiler later showed up to `594ms`, `8` DB queries.
- `/?_route=api/hotspot/packages`: about `1.30s`, profiler later showed up to `1237ms`, `8` DB queries.

After optimization:

- `/?_route=admin`: `0.30s - 0.36s`, profiler `96ms - 234ms`, `3` DB queries.
- `/?_route=login`: `0.11s - 0.27s`, profiler `74ms - 108ms`, `3` DB queries.
- `/?_route=api/hotspot/packages`: `0.31s - 0.35s`, profiler `115ms - 222ms`, `3` DB queries.

## New Profiler

SuperAdmin page:

```text
System / Logs -> Performance
/?_route=performance
```

It shows:

- route timings
- DB query counts
- slow query counts
- memory usage
- included files
- schema cache status
- important index checks
- cron health
- asset size notes

Profiler is development-only and records samples to `performance_route_samples`.

To store a sample intentionally, append:

```text
&fnp_profile=1
```

Normal development requests avoid writing profiler rows so the profiler does not become the bottleneck.
