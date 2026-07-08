# SuperAdmin vs Tenant Menus

## SuperAdmin Portal

SuperAdmin keeps access to global FASTNETPAY platform controls:

- SaaS tenant management
- Tenant admins and domains
- Tenant billing and audit logs
- Global payment gateway configuration
- Jovi-Pay and MPESA sensitive settings
- Plugins and plugin manager
- Tools, system info, maps, docs, and community links
- Global settings, maintenance, users, backup, and restore

## Tenant Portal

Tenant users see only tenant-safe ISP operations:

- Dashboard
- Clients
- Packages and bandwidth
- Sales, recharges, and vouchers
- Tenant-scoped routers and MikroTik tooling
- Tenant-scoped reports
- Tenant-scoped SMS sending and message logs
- Tenant settings
- Tenant-scoped logs

## Security Rule

Menu hiding is only the first layer. FASTNETPAY also blocks tenant access to SuperAdmin routes in middleware through `Tenant::denyTenantAccessToSuperAdminRoutes()`, so manually typing a hidden URL should still be denied.
