# FASTNETPAY Router Provisioning Wizard

The FASTNETPAY Router Provisioning Wizard helps an ISP onboard a MikroTik RouterOS v6/v7 router without replacing the existing router add/edit workflow.

It is designed for the production architecture where FASTNETPAY runs externally on Docker/Linux/VPS and MikroTik remains the access controller for Hotspot, PPPoE, DHCP, NAT, firewall, and RADIUS/API integration.

## What The Wizard Does

- Tests RouterOS API or API-SSL connection.
- Detects RouterOS version, board/model, identity, interfaces, and existing Hotspot/PPPoE/DHCP/firewall/NAT configuration.
- Generates RouterOS commands for Base ISP, Hotspot, PPPoE, RADIUS where enabled, walled garden, captive portal, MPESA readiness, and security hardening.
- Shows all commands before applying.
- Creates a MikroTik export backup with `/export file=before_fastnetpay_...` before applying.
- Applies commands in grouped RouterOS scripts through the API.
- Saves run and step logs in FASTNETPAY.

## How To Prepare A New MikroTik

1. Upgrade RouterOS to a stable v6 or v7 release.
2. Set a strong admin password or create a dedicated FASTNETPAY API user.
3. Enable RouterOS API in normal PHPNuxBill-compatible mode first. Do not restrict `/ip service api address=` until the wizard has successfully connected from Docker/Colima.
4. Confirm the FASTNETPAY server can reach the router IP and API port.
5. Keep Winbox/MAC access available while testing, so you can recover if a firewall rule is wrong.

Example minimum RouterOS commands before using the wizard:

```routeros
/user add name=fastnetpay group=full password="CHANGE_THIS_STRONG_PASSWORD"
/ip service set api disabled=no port=8728
/ip service set api-ssl disabled=no port=8729
```

After the wizard is working, use Strict ISP Mode to restrict API/Winbox/SSH to the real FASTNETPAY management IP or VPN IP. If that IP is wrong, RouterOS will refuse API connections even when the username and password are correct.

## PHPNuxBill Router Connection Compatibility

The wizard intentionally follows the default PHPNuxBill router connection behavior first:

- Saved router records keep using `tbl_routers.ip_address`, including custom ports such as `192.168.88.1:3232`.
- The wizard tests the saved router credentials using the same `Mikrotik::getClient()` connection style used by Hotspot, PPPoE, import, and monitor modules.
- If `fastnet-api-usr` is missing, the wizard can create it through the saved PHPNuxBill router connection.
- If API user setup fails, detection/provisioning can temporarily fall back to saved PHPNuxBill router credentials so the feature remains usable while the API user is repaired.

## Wizard Routes

- `/?_route=routers/provision`
- `/?_route=routers/provision/{router_id}`
- `/?_route=routers/provision-preview/{router_id}`
- `/?_route=routers/provision-run/{router_id}`
- `/?_route=routers/provision-logs/{router_id}`

Draft routers can preview and download scripts. Automatic apply requires a saved FASTNETPAY router record so provisioning can be logged.

## Deployment Modes

- Hotspot Only: configures Hotspot server, DHCP, user profiles, portal/walled garden.
- PPPoE Only: configures PPPoE server and PPP profiles.
- Hotspot + PPPoE: combined ISP deployment.
- Base ISP Setup Only: identity, DNS, interface lists, NAT.
- Security Hardening Only: firewall/API/DNS abuse protections.

## Payment Flow

1. MikroTik redirects unpaid Hotspot users to FASTNETPAY.
2. FASTNETPAY displays packages and MPESA STK Push payment.
3. Customer enters phone number and approves STK Push.
4. MPESA callback confirms payment.
5. FASTNETPAY activates the selected package using existing PHPNuxBill/RouterOS logic.
6. Customer session is refreshed/disconnected/reconnected where supported so internet is granted.

Before production, verify MPESA STK Push is enabled and has shortcode, consumer key, consumer secret, passkey, and HTTPS callback URL.

## Backup Process

The wizard attempts:

```routeros
/export file=before_fastnetpay_ROUTERID_YYYYMMDD_HHMMSS
```

If FASTNETPAY cannot download the file through API, open MikroTik Files and download the `.rsc` export manually.

## Common Errors

- Connection failed: confirm router IP, API/API-SSL port, firewall, username, and password.
- RouterOS SSL failure: disable “Prefer API-SSL” temporarily or install a valid router certificate.
- Existing Hotspot detected: preview carefully; the wizard adds FASTNETPAY objects and does not wipe current config.
- MPESA not ready: open Payment Gateway settings and complete MPESA STK Push credentials.
- Locked out risk: use Basic/Recommended first, confirm management IP, and keep Winbox/MAC access available.
- SSID appears but phone does not show “Sign in required”: confirm the wireless interface is a bridge port on `fastnetpay-bridge`, Hotspot server `fastnetpay-hotspot` is running on that bridge, DHCP gives the router gateway as DNS, and the Hotspot profile uses `html-directory=hotspot`.

## Manual Work That May Still Be Required

- Public DNS and HTTPS/reverse proxy for FASTNETPAY.
- FreeRADIUS deployment and CoA if not yet enabled.
- Uploading a static MikroTik portal theme if you choose the static portal option.
  Use `fastnetpay-captive-portal/mikrotik-hotspot/` as the lightweight MikroTik hotspot folder, then set the wizard to allow FASTNETPAY portal/assets/payment domains in the walled garden.
- Final field testing with a real client, package purchase, MPESA callback, and MikroTik session refresh.

## SaaS Upgrade Path

The tables are router-scoped now. Future SaaS work should add `tenant_id`, per-tenant router groups, per-tenant payment credentials, per-tenant portal branding, and tenant-isolated provisioning templates/logs.

## 2026-05-27 Reliability Update

The wizard now logs provisioning as explicit steps:

- FASTNETPAY API User Bootstrap
- Router API Connection
- Backup Before Provisioning
- Base ISP Setup
- Hotspot Setup
- PPPoE Setup
- Walled Garden and Captive Portal
- MikroTik Captive Portal Files
- Security Hardening
- Final Test

Every RouterOS command is applied as an individual temporary script so failures show the exact command number and command text. Failed runs can be retried from the wizard after fixing the router/API issue.

Backups now attempt both:

```routeros
/export file=before_fastnetpay_<router_id>_<timestamp>
/system backup save name=before_fastnetpay_<router_id>_<timestamp>
```

Provisioning stops if both backup methods fail unless the admin explicitly enables the backup override checkbox.

The MikroTik Hosted Portal mode downloads lightweight portal files into the router hotspot folder using `/tool fetch`. Those files call customer-safe FASTNETPAY endpoints under:

```text
/?_route=api/hotspot
```

The portal does not redirect users to the admin dashboard.

## Captive Portal Health Checks

The final wizard step now runs live checks against MikroTik instead of showing static checkmarks. It verifies:

- API connection and RouterOS version
- `fastnetpay-bridge` existence and attached bridge ports
- `FastNet Test` wireless/SSID state where wireless interfaces exist
- Hotspot server/profile, DHCP, gateway IP, portal DNS, and MikroTik portal files
- PPPoE server when selected
- NAT, walled garden, packages, and MPESA readiness

If the bridge exists but has no ports, clients can see the SSID but will not reach the captive portal. Rerun provisioning after selecting the correct WAN and Hotspot/LAN interfaces so the wizard can attach LAN/wireless interfaces to the Hotspot bridge.

For MikroTik-hosted portal mode, `portal.fastnetpay.test` is used as the Hotspot DNS name. Avoid `.local` names because phones often treat `.local` as mDNS instead of normal router DNS, which can prevent captive portal redirects. Portal files are fetched with the explicit FASTNETPAY base URL, including local ports such as `:8088`, so package loading works from the phone.

The MikroTik-hosted portal now installs the full compatibility file set used by RouterOS captive redirects:

```text
hotspot/index
hotspot/index.html
hotspot/login
hotspot/login.html
hotspot/rlogin.html
hotspot/redirect.html
hotspot/status.html
hotspot/logout.html
hotspot/alogin.html
hotspot/error.html
hotspot/radvert.html
hotspot/capport.json
hotspot/fastnetpay-hotspot.css
hotspot/fastnetpay-hotspot.js
```

CAPPORT/DHCP option 114 points clients to:

```text
http://portal.fastnetpay.test/capport.json
```

If Android initially opens `connectivitycheck.gstatic.com`, the login file immediately redirects the captive browser to:

```text
http://portal.fastnetpay.test/login.html
```

CSS/JS assets are loaded from the same portal DNS name, while package/payment/voucher calls go to the customer-safe FASTNETPAY hotspot API. The API uses router portal tokens and does not expose admin routes or payment secrets.

The wizard also runs a direct RouterOS API reconciliation after applying scripts. This verifies and repairs critical objects such as `fastnetpay-dhcp`, `fastnetpay-hotspot-profile`, `fastnetpay-hotspot`, `fastnetpay-pppoe`, package profiles, and `wlan1` bridge membership. RouterOS v6 returns some script/API errors as `!trap` responses instead of PHP exceptions, so FASTNETPAY now checks those responses explicitly.

## Bandwidth Enforcement

FASTNETPAY package speeds are enforced by MikroTik Hotspot/PPPoE profiles and the dynamic simple queues RouterOS creates for active users. If RouterOS FastTrack is left above customer traffic, those queues can be bypassed and clients may see full uplink speed.

The provisioning wizard now creates two firewall rules before any active `fasttrack-connection` rule:

```text
FASTNETPAY hotspot queue guard upload
FASTNETPAY hotspot queue guard download
```

These rules keep `fastnetpay-bridge` Hotspot traffic out of FastTrack while preserving FastTrack for unrelated traffic. The Final Test screen includes `Bandwidth queue guard`; it should show success before production use.

## Management Interface

The wizard defaults the management interface to `ether4` (`Port4`). Detection fills the interface picker with live MikroTik interfaces, so you can select a different management port if needed.

During provisioning, the selected management interface is:

- added to a `MGMT` interface list;
- kept out of `fastnetpay-bridge`;
- preserved on the existing reset/default management bridge so `192.168.88.1` Winbox/API does not drop during the run;
- removed only if it was accidentally swept into `fastnetpay-bridge`;
- accepted as `Port4`, `port 4`, or `ether4` in the wizard.

Use this port for recovery/Winbox/API management while Hotspot and PPPoE clients are isolated on `fastnetpay-bridge`. The wizard uses `ether1` as WAN, `ether4` as management, and a separate default client subnet `192.168.90.0/24` for Hotspot/DHCP so the reset management IP `192.168.88.1` stays reachable.

## Dedicated MikroTik API User

FASTNETPAY now provisions through a dedicated RouterOS API user:

```text
fastnet-api-usr
```

Wizard credential flow:

1. Enter bootstrap/admin credentials only for first-time setup or repair.
2. Enter a strong password for `fastnet-api-usr`.
3. The wizard attempts to connect with `fastnet-api-usr`.
4. If the API user does not exist, the wizard uses bootstrap/admin credentials to create or repair it.
5. FASTNETPAY reconnects with `fastnet-api-usr`.
6. The router record is saved with `fastnet-api-usr`, so future package activation, PPPoE sync, hotspot sync, monitoring, and provisioning use the API user instead of admin.

The RouterOS group is:

```text
fastnet-api
```

The group is limited to API automation policies required by FASTNETPAY:

```text
read,write,policy,test,api,sensitive
```

The wizard also restricts RouterOS API/API-SSL service access to the configured FASTNETPAY Server IP. In production this should be a VPN IP, not a public internet address.

## 2026 Enhancements

- The wizard now labels the management port as safe admin access only and defaults it to `ether4`.
- Management port settings can bind one trusted MAC/IP and warn when more than one device is detected.
- Connection modes now include Local, WireGuard VPN, and SSTP VPN.
- Router records include future-ready tenant/site/router-group fields without changing current single-tenant behavior.
- Provisioning writes audit rows to `router_management_audit_logs`.
- Preview output redacts sensitive passwords before sending scripts to the browser.
- SSTP preview output now generates RB951/RouterOS v6-safe commands using `connect-to=sstp.fastnetpay.co.ke:4443`, imports the Let's Encrypt root CA, and avoids the unsupported `port=4443` one-liner form.

Recommended beginner setup:

1. Use `ether1` as WAN.
2. Use `ether4` as management.
3. Use `fastnetpay-bridge` for Hotspot and PPPoE client traffic.
4. Keep Local mode while testing in the same LAN.
5. Move to WireGuard or SSTP when the router is managed from a VPS/cloud FASTNETPAY server.

## SSTP RB951 Notes

The tested RB951 runs RouterOS `6.49.19`, so WireGuard is unavailable. Use SSTP for remote management through the FASTNETPAY VPS.

RouterOS v6 requirements:

- Use `connect-to=sstp.fastnetpay.co.ke:4443`; do not use a separate `port=4443` argument.
- Keep `add-default-route=no` so the VPN does not take over customer internet traffic.
- The VPS SSTP server must use an RSA certificate for `sstp.fastnetpay.co.ke`; older RB951 clients can fail against the ECDSA wildcard web certificate.
- The VPS `accel-ppp` SSTP config must not set `host-name=` because RouterOS v6 does not send SSTP client SNI.
- Restrict RouterOS API to the VPN server IP only after `/interface sstp-client print` shows the tunnel running and `/ping 10.100.0.1` succeeds.
- Import and trust `ISRG Root X1` on RouterOS before enabling `verify-server-certificate=yes`.
- Pin a stable router management address such as `10.100.1.1/32` on `sstp-fastnetpay`; FASTNETPAY should register the router by this VPN IP, not `192.168.88.x`.

Production RB951 validation completed:

- RB951 SSTP interface `sstp-fastnetpay` was running.
- Router had `10.100.1.1/32` on `sstp-fastnetpay`.
- Router could ping FASTNETPAY/VPS `10.100.0.1`.
- VPS could ping `10.100.1.1` and open RouterOS API `8728`.
- FASTNETPAY PHP container could open TCP `10.100.1.1:8728`.
