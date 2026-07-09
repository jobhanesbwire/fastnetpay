# FASTNETPAY SaaS Tenant Payment Flow

FASTNETPAY can collect SaaS subscription payments from ISP tenants while each tenant continues to collect internet customer payments through its own configured gateway.

## SuperAdmin Setup

1. Open `SaaS Management -> SaaS Payment Settings`.
2. Enable SaaS payments.
3. Select the provider, for example `Jovi-Pay` or `M-Pesa C2B`.
4. Set the mother-system Paybill/Till/shortcode.
5. Keep the SaaS invoice prefix unique, for example `FASTNETPAY_`.
6. Copy the callback URL:

```text
/?_route=api/saas/mpesa/callback
```

7. Configure the callback secret/signature key if Jovi-Pay issues one.
8. Enable auto-settle and auto-restore when you want paid invoices to restore suspended tenants automatically.

## Tenant Payment Screen

Suspended tenants are redirected to a payment-only screen. They see:

- tenant name
- invoice number
- billing period
- active Hotspot and PPPoE users
- amount paid and balance due
- due date and grace deadline
- Paybill/Till details
- account reference such as `FASTNETPAY_SEGA`
- support phone

Suspended tenants cannot access the normal dashboard until the invoice is settled or SuperAdmin restores them manually.

## Settlement

When the callback arrives, FASTNETPAY:

1. validates the signature if a secret is configured
2. normalizes the M-Pesa/Jovi-Pay payload
3. checks the account reference prefix
4. matches the latest unpaid tenant invoice
5. records the transaction in `saas_invoice_payments`
6. prevents duplicate settlement by transaction code
7. marks the invoice `partial` or `paid`
8. restores the tenant when fully paid and auto-restore is enabled

Partial payments remain visible on the invoice and in Payment Reconciliation.
