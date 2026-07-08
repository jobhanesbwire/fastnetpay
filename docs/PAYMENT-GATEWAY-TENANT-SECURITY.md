# Payment Gateway Tenant Security

## Principle

Payment secrets stay with SuperAdmin. Tenants can see only safe payment status and public payment instructions.

## SuperAdmin Controls

SuperAdmin can assign tenant payment availability from the tenant form:

- Enable or disable payment for a tenant
- Assign active gateway names
- Set public payment label
- Set tenant Jovi-Pay account prefix
- Set non-secret callback URL metadata
- Set support/payment instruction text

Sensitive credentials such as MPESA consumer secret, passkey, Jovi-Pay secret, API tokens, and callback verification secrets are not exposed in tenant views.

## Tenant View

Tenant Settings shows a read-only payment status panel:

- Whether payment is enabled
- Public label
- Support/instruction message

Tenants cannot open `paymentgateway`, `jovipay`, or global payment configuration routes.
