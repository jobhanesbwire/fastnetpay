# Jovi-Pay Prefix Routing

FASTNETPAY uses account reference prefixes to decide what a payment is for.

## SaaS Subscription Prefix

Configured in:

```text
SaaS Management -> SaaS Payment Settings
```

Example:

```text
FASTNETPAY_SEGA
```

Payments with this prefix settle tenant SaaS invoices.

## Tenant Customer Prefix

Configured in:

```text
SaaS Management -> Tenant Payment Gateways
```

Example:

```text
WIFI_SEGA_1_5_SESSION
```

Payments with this prefix belong to tenant customer internet access. If a matching pending Jovi-Pay transaction exists, FASTNETPAY activates the selected package. If no pending transaction exists, the payment is recorded for manual reconciliation.

## Prefix Hygiene

Keep prefixes unique across tenants and purposes:

- SaaS invoices: `FASTNETPAY_`
- Tenant customer WiFi: `WIFI_{TENANT}_`
- Tenant PPPoE invoices: `PPPOE_{TENANT}_`

Do not reuse prefixes from unrelated apps.
