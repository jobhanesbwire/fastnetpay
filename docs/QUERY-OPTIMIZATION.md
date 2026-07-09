# FASTNETPAY Query Optimization

## Fixes Applied

- Versioned schema cache prevents repeated `SHOW TABLES`, `SHOW COLUMNS`, and `SHOW INDEX` checks on every request.
- Tenant-specific payment/SMS settings are now loaded only on tenant portal requests.
- Tenant and SaaS settings now use request-level caches.
- SaaS dashboard expected revenue uses billing snapshots/invoices instead of recalculating every tenant preview on every page load.
- Top tenant analytics prefers `tenant_billing_snapshots` instead of per-tenant active-user count loops.

## Indexes Added Or Verified

- `tbl_customers`: `idx_tenant_username`, `idx_tenant_phone`
- `tbl_payment_gateway`: `idx_tenant_gateway_status`, `idx_gateway_trx`
- `tbl_user_recharges`: `idx_tenant_customer_expiry`
- `jovipay_transactions`: `idx_tenant_reference`, `idx_tenant_status`, `idx_receipt_status`
- `saas_invoices`: `idx_tenant_status_due`, `idx_tenant_month`
- `saas_invoice_payments`: `idx_tenant_transaction`, `idx_invoice_status`
- `tenant_customer_payments`: `idx_tenant_status_created`, `idx_gateway_status`
- `tenant_payment_gateways`: `idx_tenant_default_enabled`
- `unmatched_payments`: `idx_source_resolved_created`

## Development Checks

Use the Performance page to spot:

- routes over `500ms`
- query counts over `50`
- slow query counts above `0`
- missing index warnings

For MySQL production, enable the slow query log and investigate repeated queries over `250ms`.
