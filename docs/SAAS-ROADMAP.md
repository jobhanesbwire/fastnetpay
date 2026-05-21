# FASTNETPAY SaaS Roadmap

This project is still a single-tenant PHPNuxBill-based billing app. Do not enable SaaS or multi-tenant sales until tenant isolation, billing boundaries, and operational controls are designed and tested.

## Future Multi-Tenant Foundation

- Add a `tenants` table with tenant status, slug, owner, lifecycle dates, and billing state.
- Add company/ISP profile tables for business name, contacts, branding, tax data, invoice metadata, and support channels.
- Add `tenant_id` to business tables such as customers, plans, routers, vouchers, transactions, logs, invoices, messages, and payment records.
- Add database indexes that include `tenant_id` for all high-traffic tenant-scoped queries.
- Decide early whether FASTNETPAY will use shared tables, separate databases per tenant, or a hybrid model.

## Tenant-Aware Operations

- Make MikroTik routers tenant-aware so one ISP cannot see or control another ISP's routers.
- Store payment credentials per tenant, including M-Pesa shortcode, passkey, callback URLs, and settlement metadata.
- Store SMS and WhatsApp gateway credentials per tenant.
- Add domain and subdomain mapping such as `tenant.fastnetpay.com` and custom ISP domains.
- Add queue and cron separation so one tenant's slow router, payment callback, or message batch cannot block others.

## SaaS Commercial Layer

- Add subscription plans for FASTNETPAY tenants, including limits for routers, customers, staff users, messages, and payment volume.
- Add plan enforcement and grace-period logic before restricting tenant access.
- Build a centralized super-admin area for tenant onboarding, suspension, billing, support access, and impersonation with audit trails.
- Add API keys per tenant with scoped permissions, rotation, and request logging.

## Security And Compliance

- Enforce tenant isolation in every query, report, export, API route, cron job, webhook, and plugin hook.
- Add audit logs for admin access, router changes, payment configuration changes, exports, and tenant impersonation.
- Encrypt sensitive tenant credentials at rest.
- Separate production secrets from source control and local Docker defaults.
- Review all plugins and payment gateways for tenant isolation before enabling them in SaaS mode.

## Main Risks

- PHPNuxBill was designed as a single-tenant app, so missing one `tenant_id` filter can leak customer or payment data.
- Router automation and payment callbacks are high-risk because they can affect real customer connectivity and money movement.
- Existing plugins may assume global settings and must be audited before use in a SaaS environment.
- Background jobs need tenant-aware locking, retries, and rate limits.

SaaS readiness should be implemented as a deliberate architecture phase, not a cosmetic database change.
