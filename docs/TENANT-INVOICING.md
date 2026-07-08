# Tenant Invoicing

Tenant invoices are stored separately from customer package payments.

## Tables

- `saas_invoices`
- `saas_invoice_items`
- `tenant_billing_snapshots`
- `tenant_suspensions`
- `saas_billing_settings`
- `saas_billing_bands`

## Invoice Preview

Preview uses current active recharge records:

- Hotspot: active `tbl_user_recharges` where type is `Hotspot`
- PPPoE: active `tbl_user_recharges` where type is `PPPOE` or `PPPoE`
- Routers: tenant routers in `tbl_routers`

First invoice adds:

- one-time configuration fee
- first month payment

## Payment Status

Marking a SaaS invoice paid:

- sets invoice status to `paid`
- records `paid_at`
- restores tenant status if it was suspended
- restores router provisioning/VPN status markers where applicable

No MikroTik router configuration is wiped by tenant suspension or restoration.
