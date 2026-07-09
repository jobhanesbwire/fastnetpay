# FASTNETPAY Caching Strategy

## Schema Cache

Schema checks are cached in:

```text
system/cache/schema/
```

Cached areas:

- tenant
- SaaS billing
- Jovi-Pay
- router provisioning
- performance profiler

Each cache entry has a schema version. When code changes require migrations, bump the version constant in the related class.

Force a schema refresh locally:

```text
http://localhost:8088/?_route=admin&refresh_schema=1
```

or run:

```bash
docker compose exec fastnetpay_app php -r '$_GET["refresh_schema"]=1; require "init.php"; Tenant::installSchema(); SaasBilling::installSchema();'
```

## Request-Level Cache

Implemented for:

- tenant settings
- SaaS billing settings
- SaaS payment settings
- Jovi-Pay settings per tenant

These caches reset on every request and do not expose secrets to the browser.

## Dashboard Snapshots

Use `tenant_billing_snapshots` for heavy SaaS dashboard summaries. Refresh snapshots when invoices are generated or from cron/background jobs.
