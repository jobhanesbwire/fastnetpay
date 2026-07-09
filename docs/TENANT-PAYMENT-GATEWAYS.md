# Tenant Payment Gateways

SuperAdmin can configure customer-facing payment gateways per ISP tenant from:

```text
SaaS Management -> Tenant Payment Gateways
```

These gateways are for tenant customers buying Hotspot/PPPoE packages. They are separate from FASTNETPAY SaaS subscription payments.

## Supported Gateway Types

- Jovi-Pay prefix forwarding
- M-Pesa Paybill C2B
- Bank M-Pesa Paybill
- M-Pesa Till
- Manual payment
- Other future gateway

## Security

API tokens, callback secrets, passkeys, and other credentials are encrypted before storage. Tenant admins only see safe public instructions and labels. SuperAdmin controls the full gateway assignment.

## Captive Portal Compatibility

When a gateway is marked as the tenant default, FASTNETPAY syncs the public gateway label, Jovi-Pay prefix, and callback URL into the existing tenant payment settings used by the captive portal.

Use prefixes such as:

```text
WIFI_SEGA_
WIFI_TENANTSLUG_
```

This keeps customer payments tenant-aware and helps prevent cross-tenant activation.
