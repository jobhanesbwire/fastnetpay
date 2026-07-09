# Payment Reconciliation

Open:

```text
SaaS Management -> Payment Reconciliation
```

Use this page to review:

- unmatched callbacks
- SaaS invoice payments
- tenant customer payment callbacks

## Reconcile Unmatched Payment

1. Find the unmatched callback.
2. Select the correct unpaid SaaS invoice.
3. Click `Reconcile`.
4. FASTNETPAY records the payment against that invoice.
5. If the invoice becomes fully paid, the tenant can be restored.

## Common Causes Of Unmatched Payments

- wrong account reference
- missing prefix
- typo in tenant slug
- callback sent to SaaS endpoint for a customer payment with no pending transaction
- duplicate transaction with a different account reference

Never edit raw payment payloads. Reconcile through the SuperAdmin UI so the audit trail remains intact.
