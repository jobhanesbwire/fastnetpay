# SaaS-Ready Router Design

FASTNETPAY remains single-tenant today, but router provisioning now stores future-ready metadata.

Prepared fields:

- `tenant_id`
- `site_id`
- `router_group`
- `management_url`
- `management_port`
- `management_mac`
- `management_ip`
- `vpn_mode`
- `vpn_ip`
- `vpn_status`
- `vpn_last_seen`
- `api_restrict_mode`

Future SaaS direction:

- every ISP tenant owns many routers;
- every router belongs to a site or router group;
- packages can be mapped per tenant/site/router;
- payment credentials can move from global settings to tenant/site/router settings;
- captive portal branding can become tenant/site/router-specific;
- VPN addresses should be allocated from a central range such as `10.100.0.0/16`;
- audit logs should remain per tenant, per router, and per admin action.

Current behavior:

- nullable SaaS fields do not change existing PHPNuxBill billing behavior;
- old router add/edit/list pages keep working;
- provisioning uses these fields only when the wizard needs them.
