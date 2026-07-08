# FASTNETPAY SaaS Multi-Tenancy

FASTNETPAY now has an additive SaaS layer. Existing single-tenant data is assigned to the default tenant:

- Name: FASTNETPAY Main
- Slug: `main`
- Subdomain: `fastnetpay`
- Custom domain: `fastnetpay.co.ke`

Tenant resolution:

- `fastnetpay.co.ke` and local development hosts run in mother-system mode.
- `{tenant}.fastnetpay.co.ke` resolves by tenant subdomain.
- Custom domains resolve through `tenant_domains`.
- Local testing supports `http://sega.localhost:8088` and `http://localhost:8088/?tenant=sega`; `TENANT_LOCAL_TESTING=true` can also force local shortcuts in development.

Core tables:

- `tenants`
- `tenant_domains`
- `tenant_settings`
- `tenant_subscription_plans`
- `saas_audit_logs`

Nullable `tenant_id` columns are added to tenant-owned records first. Existing rows are migrated to the default tenant without deleting or rewriting business data.

SuperAdmin routes:

- `/?_route=saas/tenants`
- `/?_route=saas/add`
- `/?_route=saas/domains`
- `/?_route=saas/billing`
- `/?_route=saas/2fa`
- `/?_route=saas/audit`

Tenant-owned records include routers, customers, packages, bandwidth profiles, vouchers, payments, transactions, Jovi-Pay records, SMS/message logs, provisioning runs, expiry worker logs, ODP/fiber records, and admin users.

Important migration note: this is the SaaS foundation layer. New payment, captive-portal, Jovi-Pay, and admin-user records are tenant stamped. The tenant dashboard, plans, vouchers, recharges, SMS, logs, reports, exports, and router provisioning paths now use tenant-scoped queries in tenant mode. Any new module should use `Tenant::scopeIfTenant()` or `Tenant::enforceTenantScope()` before listing, editing, deleting, exporting, or reporting tenant-owned data.

Internal/non-billable tenants can be marked with `internal_tenant`, `billing_exempt`, and `exemption_reason`.
