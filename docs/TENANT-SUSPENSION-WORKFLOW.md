# Tenant Suspension Workflow

FASTNETPAY suspension is designed to be reversible and safe.

## What Suspension Does

- Sets tenant status to `suspended`
- Sets tenant subscription status to `suspended`
- Blocks tenant admin login
- Blocks already logged-in tenant admin sessions
- Records the suspension in `tenant_suspensions`
- Writes SaaS audit logs

If enabled in billing settings, router records can also be marked:

- `provisioning_status = tenant_suspended`
- `vpn_status = blocked` where the column exists

## What Suspension Does Not Do

- It does not wipe MikroTik configuration.
- It does not delete customers.
- It does not delete packages.
- It does not remove routers.
- It does not remove payment or SMS settings.

## Restoration

Marking an invoice paid or restoring manually:

- sets tenant status to `active`
- marks active suspension rows restored
- returns router sync/VPN markers to restored/ready where applicable

This keeps nonpayment enforcement strong without destroying ISP data.
