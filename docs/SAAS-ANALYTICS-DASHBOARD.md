# SaaS Analytics Dashboard

SuperAdmins now see a SaaS control strip on the FASTNETPAY dashboard.

## Metrics

- total tenant ISPs
- active/suspended tenants
- online/offline routers
- active hotspot and PPPoE users
- expected SaaS revenue
- overdue SaaS revenue

The main FASTNETPAY host tenant is excluded from SaaS tenant billing metrics.

## Source

Analytics are calculated from:

- `tenants`
- `tbl_routers`
- `tbl_user_recharges`
- `saas_invoices`
- SaaS billing previews

Open detailed controls at:

`SaaS Management -> Plans / Billing`
