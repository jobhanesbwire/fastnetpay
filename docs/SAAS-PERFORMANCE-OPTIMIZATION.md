# SaaS Performance Optimization

## Changes Implemented

- Tenant resolution is cached per request by host and debug tenant key.
- Tenant tables now receive `tenant_id` indexes when the schema boots.
- Common dashboard paths have compound indexes:
  - `tbl_routers(tenant_id, status)`
  - `tbl_customers(tenant_id, status)`
  - `tbl_transactions(tenant_id, recharged_on)`
  - `tbl_payment_gateway(tenant_id, status, created_date)`
  - `tbl_user_recharges(tenant_id, status, type, expiration)`
- Dashboard widgets avoid global scans in tenant mode.
- Monthly dashboard cache files are separated per tenant to prevent stale/global chart reuse.

## Future Improvements

- Add debug-mode query timing logs.
- Add route-level cache for menu arrays if the sidebar becomes expensive.
- Add background aggregate tables for very large ISP tenants.
- Keep payment/SMS secrets out of any cache layer.
