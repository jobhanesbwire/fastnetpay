# SaaS Billing

FASTNETPAY now includes a SuperAdmin SaaS billing module for ISP tenants.

## Defaults

- Configuration fee: Ksh 1000
- First month payment: Ksh 500
- Billing day: 23
- Grace day: 28
- Auto suspend unpaid tenants: enabled

## Billing Bands

Hotspot tenants are billed by active hotspot users:

- 0-50 users: Ksh 500
- 51-300 users: Ksh 1000
- 301-700 users: Ksh 1500
- 701-1300 users: Ksh 2000
- 1301-2000 users: Ksh 2500
- 2001-3000 users: Ksh 3000
- 3001+ users: Ksh 3500

PPPoE default billing:

- 0-25 active PPPoE users: Ksh 500
- 26+ active PPPoE users: Ksh 500 plus Ksh 20 per user above 25

Example: 27 PPPoE users = Ksh 500 + 2 x Ksh 20 = Ksh 540.

## Admin Screen

Open:

`SaaS Management -> Plans / Billing`

From there a SuperAdmin can:

- edit billing settings
- edit hotspot and PPPoE bands
- preview tenant invoices
- generate one tenant invoice
- generate all tenant invoices
- mark invoices paid
- suspend or restore tenants through billing workflow

## Cron

The existing `system/cron.php` now also runs the SaaS billing worker. If invoice generation mode is automatic, invoices generate from the configured billing day. If auto suspension is enabled, unpaid tenants are suspended after the grace day.
