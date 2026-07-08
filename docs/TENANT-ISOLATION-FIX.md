# FASTNETPAY Tenant Isolation Fix

## Problem

Tenant portals such as `http://sega.localhost:8088` were resolving the tenant brand correctly, but several legacy PHPNuxBill dashboard and operational queries still read global tables without `tenant_id` filtering. This allowed tenant users to see main/SuperAdmin statistics and created risk around clients, routers, vouchers, payments, reports, logs, and SMS batches.

## What Changed

- Added tenant helper aliases and enforcement methods in `system/autoload/Tenant.php`:
  - `currentTenant()`
  - `currentTenantId()`
  - `isSuperAdmin()`
  - `isTenantAdmin()`
  - `enforceTenantScope()`
  - `denyTenantAccessToSuperAdminRoutes()`
  - `denyCrossTenantResourceAccess()`
- Scoped dashboard widgets to current tenant when the request is for a tenant portal.
- Scoped plans, vouchers, recharges, customers, transactions, routers, logs, reports, exports, and SMS controller queries.
- Blocked tenant access to global/SuperAdmin routes, including payment gateway secrets, Jovi-Pay settings, plugin manager, tools, global settings, and backup/restore.
- Added tenant-aware indexes for common dashboard and operational queries.
- Added audit entries for tenant logins, blocked global route attempts, tenant settings changes, and SuperAdmin tenant payment assignment changes.

## Validation

Test with a tenant admin on:

```bash
http://sega.localhost:8088/?_route=dashboard
http://sega.localhost:8088/?_route=settings/tenant
```

Confirm the tenant cannot access:

```bash
http://sega.localhost:8088/?_route=paymentgateway
http://sega.localhost:8088/?_route=jovipay/settings
http://sega.localhost:8088/?_route=settings/app
http://sega.localhost:8088/?_route=pluginmanager
```

SuperAdmin should still use the main portal for global SaaS administration.
