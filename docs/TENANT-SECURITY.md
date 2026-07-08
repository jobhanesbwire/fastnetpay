# Tenant Security

FASTNETPAY tenant isolation is layered:

- host/subdomain tenant resolution;
- nullable `tenant_id` ownership columns;
- tenant-aware login checks;
- tenant-stamped payments, captive portal customers, vouchers, transactions, and admin users;
- `saas_audit_logs` for sensitive SaaS actions;
- per-tenant Jovi-Pay settings and account prefixes.

Payment safety:

- Use a unique Jovi-Pay prefix per tenant, for example `WIFI_ISP1_`.
- Callback handling resolves tenant by account reference prefix before validating/reconciling.
- Payment activation checks plan, router, and customer tenant ids before activating access.

Admin safety:

- Tenant admins must log in through their tenant host.
- A tenant admin session opened against a different tenant host is blocked and audited.
- Suspended tenants cannot log in or keep active admin sessions.
- SuperAdmin SaaS Management is available only to `SuperAdmin`.
- SuperAdmin SMS 2FA can be enabled from `SaaS Management -> SuperAdmin 2FA` after SMS is configured.

Remaining hardening before public SaaS:

- continue automated review for any newly added legacy list/edit/delete query;
- add CSRF checks to any old routes that still mutate by GET;
- add backup recovery codes and hardware-key support for SuperAdmin 2FA;
- add tenant-aware file upload paths;
- encrypt all router/payment/SMS credentials with managed keys;
- add automated cross-tenant access tests.

Tenant isolation docs added in this pass:

- `docs/TENANT-ISOLATION-FIX.md`
- `docs/SUPERADMIN-VS-TENANT-MENUS.md`
- `docs/TENANT-SETTINGS-SCOPE.md`
- `docs/PAYMENT-GATEWAY-TENANT-SECURITY.md`
- `docs/SAAS-PERFORMANCE-OPTIMIZATION.md`
- `docs/INTERNAL-NON-BILLABLE-TENANT.md`
