# Suspended Tenant Payment Page

When an ISP tenant is suspended for SaaS billing, FASTNETPAY redirects tenant admins to:

```text
/?_route=tenant-payment
```

The page shows a clean payment card and blocks normal dashboard access.

## Shown To Tenant

- suspension reason
- tenant name
- invoice number
- active Hotspot users
- active PPPoE users
- amount due
- amount already paid
- due date
- grace deadline
- Paybill/Till/shortcode
- account reference
- support phone

## Restoration

When the SaaS invoice is fully paid:

1. invoice becomes `paid`
2. tenant status becomes `active`
3. active suspension records are marked restored
4. router/VPN management flags previously blocked by SaaS suspension are restored

If auto-restore is disabled, SuperAdmin must restore the tenant manually.
