# Future VPN Deployment

For online FASTNETPAY deployments, do not expose RouterOS API directly to the public internet.

Recommended model:

```text
FASTNETPAY VPS
  |
WireGuard/IPsec/OpenVPN private tunnel
  |
MikroTik routers at customer sites
```

Production guidance:

- Use WireGuard where RouterOS supports it.
- Use SSTP for RouterOS v6 devices such as RB951, and configure the server with an RSA certificate plus no SSTP SNI `host-name=` enforcement.
- Give every router a unique tunnel IP.
- Restrict API/API-SSL to the FASTNETPAY VPN IP only.
- Keep Winbox/SSH restricted to trusted management IPs.
- Use strong router credentials and a dedicated FASTNETPAY API user.
- FASTNETPAY should always connect as `fastnet-api-usr`, never as `admin`, after the provisioning bootstrap.
- Keep per-router portal tokens and later per-tenant tokens for SaaS.
- Store M-Pesa and SMS credentials per tenant/site when SaaS mode is added.

Example API restriction:

```routeros
/ip service set api address=10.100.0.10/32 disabled=no
/ip service set api-ssl address=10.100.0.10/32 disabled=no
```

For SaaS readiness, add `tenant_id` to routers, packages, portal tokens, payment settings, SMS settings, logs, and provisioning runs.
