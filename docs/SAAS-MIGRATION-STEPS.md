# SaaS Migration Steps

1. Back up MySQL and uploaded files.
2. Deploy the FASTNETPAY SaaS code.
3. Open the app once as SuperAdmin so `Tenant::installSchema()` creates SaaS tables and nullable `tenant_id` columns.
4. Confirm the default tenant exists:

```sql
SELECT id, name, slug, subdomain, status FROM tenants;
```

5. Confirm existing rows were assigned:

```sql
SELECT COUNT(*) FROM tbl_customers WHERE tenant_id IS NULL;
SELECT COUNT(*) FROM tbl_routers WHERE tenant_id IS NULL;
SELECT COUNT(*) FROM tbl_plans WHERE tenant_id IS NULL;
```

6. Create a test tenant from `SaaS Management -> Add Tenant`.
7. Create a tenant admin.
8. Test login through the tenant subdomain.
9. Add a router/package/client inside the tenant and confirm records carry that tenant id.
10. Configure Jovi-Pay with a unique tenant prefix.

Rollback:

- Existing business columns are preserved.
- The SaaS migration adds nullable columns and new tables.
- To roll back application behavior, disable wildcard routing and use the main host while keeping the added columns.
