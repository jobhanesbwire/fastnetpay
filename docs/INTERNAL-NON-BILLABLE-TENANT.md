# Internal and Non-Billable Tenants

FASTNETPAY can keep the mother ISP or internal demo tenant inside the tenant table without billing it.

## Tenant Flags

The `tenants` table includes:

- `internal_tenant`
- `billing_exempt`
- `exemption_reason`

## Billing Behavior

`SaasBilling::previewInvoice()` returns zero billing lines for exempt tenants.

`SaasBilling::runCron()` skips tenants marked `billing_exempt = 1`.

SaaS analytics count exempt tenants separately and exclude them from expected billable revenue.

## Recommended Use

Use these flags for:

- FASTNETPAY's own ISP account
- Internal testing tenants
- Demo tenants
- Waived partner accounts

Do not use billing exemption as a substitute for suspension or access control.
