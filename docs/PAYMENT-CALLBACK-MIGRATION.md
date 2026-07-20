# FASTNETPAY Payment Callback Migration Safety

## Keep Callback Hostnames Stable

During VPS migration or scaling, do not change customer-facing payment callback URLs unless the payment provider configuration is deliberately updated.

Critical flows:

- MPESA STK callback.
- Jovi-Pay forwarded C2B callback.
- SaaS tenant payment callback.
- Hotspot activation after payment.
- PPPoE activation after payment.

## Required Data To Preserve

- Pending transactions.
- Payment receipts.
- Account reference prefixes.
- Tenant IDs.
- Router IDs.
- MAC/phone mapping for hotspot users.
- Callback shared secrets.
- Idempotency markers.

## Post-Migration Tests

1. Initiate a sandbox or low-value STK.
2. Confirm payment provider callback reaches FASTNETPAY.
3. Confirm duplicate callback is ignored safely.
4. Confirm hotspot user activation.
5. Confirm PPPoE recharge activation.
6. Confirm tenant invoice settlement.

## Failure Handling

If callback settlement fails after migration:

- Do not re-trigger customer charges blindly.
- Check callback logs first.
- Reconcile by transaction code/account reference.
- Confirm tenant and router mapping.
- Use idempotent settlement paths only.
