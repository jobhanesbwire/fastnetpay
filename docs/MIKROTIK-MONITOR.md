# FASTNETPAY MikroTik Monitor

FASTNETPAY exposes MikroTik operations from the admin sidebar:

- `Mikrotik -> Dashboard`
- `Mikrotik -> PPPoE Monitor`
- `Mikrotik -> Hotspot Monitor`

The pages use the existing PHPNuxBill RouterOS API credentials stored in `tbl_routers`. No MikroTik credentials are rendered in the browser.

## Pages

### Dashboard

Route: `?_route=plugin/mikrotik_monitor_ui`

Shows enabled routers, router online state from FASTNETPAY, active Hotspot and PPPoE sessions from RouterOS when reachable, total traffic, hotspot server count, interface list, and a live traffic chart.

### PPPoE Monitor

Route: `?_route=plugin/mikrotik_monitor_pppoe`

Shows PPPoE secrets and active sessions with IP address, uptime, service, profile, caller ID, traffic, status badges, details modal, CSV export, search, status/profile filters, manual refresh, auto-refresh, and disconnect action for online sessions.

### Hotspot Monitor

Route: `?_route=plugin/mikrotik_monitor_hotspot`

Shows Hotspot users and active sessions with IP, MAC, uptime, traffic, profile, server, status badges, details modal, CSV export, search, status/profile filters, live traffic chart, manual refresh, auto-refresh, and disconnect action for online sessions.

## Safety

- Disconnect actions require an admin session and CSRF token.
- Router IDs are validated against enabled routers.
- Search/filter/export are client-side on the loaded monitor data.
- If RouterOS API is unavailable, the pages fall back to local FASTNETPAY customer records where possible and show a clear warning instead of crashing the UI.
- API credentials remain server-side.

## Performance Notes

- Router data is loaded lazily through AJAX after the page renders.
- Auto-refresh is enabled by default and can be paused from the page toolbar.
- Polling is intentionally lightweight and should be tuned before very large deployments.
- For SaaS/multi-tenant scaling, add tenant-router scoping before exposing these monitor endpoints to tenant admins.

## Files

- `system/plugin/mikrotik_monitor.php`
- `system/plugin/ui/mikrotik_monitor.tpl`
- `system/plugin/ui/mikrotik_pppoe_monitor.tpl`
- `system/plugin/ui/mikrotik_hotspot_monitor.tpl`
- `system/plugin/ui/mikrotik_session_modal.tpl`
- `ui/ui/scripts/fastnetpay-monitor.js`
- `assets/js/fastnetpay-monitor.js`
- `ui/ui/styles/fastnetpay-theme.css`
- `assets/css/fastnetpay-theme.css`
