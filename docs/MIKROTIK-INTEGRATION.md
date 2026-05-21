# FASTNETPAY MikroTik Integration

FASTNETPAY should remain on a Docker/Linux server or VPS. MikroTik should be the network access controller, not the place where the billing application runs.

Recommended production role split:

```text
Customers
  -> Access points / switches
  -> MikroTik RouterOS gateway
       - Hotspot gateway
       - PPPoE server
       - RADIUS client
       - Captive portal redirector
       - Firewall and NAT
  -> FASTNETPAY server
       - Billing
       - Vouchers
       - Plans/packages
       - M-Pesa STK Push
       - TALKSASA SMS
       - Customer portal
       - RouterOS API orchestration
       - FreeRADIUS SQL data owner
  -> MySQL / FreeRADIUS / payment and SMS providers
```

## Current Capability Inspection

The current FASTNETPAY/PHPNuxBill codebase already includes these MikroTik integration paths:

- Direct RouterOS API for Hotspot in `system/devices/MikrotikHotspot.php`.
- Direct RouterOS API for PPPoE in `system/devices/MikrotikPppoe.php`.
- Direct RouterOS API for VPN in `system/devices/MikrotikVpn.php`.
- Shared API helper in `system/autoload/Mikrotik.php`.
- Router management UI in `system/controllers/routers.php`, using router `ip_address`, `username`, and `password`.
- FreeRADIUS SQL support in `system/devices/Radius.php` and `install/radius.sql`.
- NAS management UI in `system/controllers/radius.php`.
- Plan activation through `Package::rechargeUser()` in `system/autoload/Package.php`.
- M-Pesa STK Push success path calls `Package::rechargeUser()` from `system/paymentgateway/mpesastkpush.php`.
- Captive/customer theme files exist under `ui/themes/fastnetpay-captive`.

Current useful behavior:

- Hotspot users can be created, synced, removed, connected, and disconnected through the RouterOS API.
- PPPoE secrets, profiles, pools, and active sessions can be managed through the RouterOS API.
- RADIUS plans can write `radcheck`, `radreply`, `radgroupreply`, `radusergroup`, `nas`, and `radacct`-related data.
- RADIUS disconnect is attempted with `radclient` from PHP, using the NAS secret and Disconnect-Message flow.
- Router IP can include a custom API port, for example `192.168.88.1:8728`.

Important current gaps:

- The direct RouterOS API code does not expose an explicit API-SSL toggle. Current compatibility is strongest with API on TCP `8728`; API-SSL on TCP `8729` should be tested or added deliberately.
- Router credentials are stored in the database through the existing PHPNuxBill router model. Protect the database and plan future encryption/secrets storage.
- The local Docker setup exposes app `8088`, phpMyAdmin `8089`, and MySQL `3309`; this is acceptable for local development only.
- The Dockerfile does not currently install FreeRADIUS client tools such as `radclient`, so RADIUS disconnect/CoA needs production packaging work.
- M-Pesa callback handling is idempotent, but production should add reverse proxy rate limits, HTTPS, audit logs, and optional STK query verification before activation.

## Recommended Production Topology

Use this model for a real ISP deployment:

```text
Internet
  |
Edge firewall / reverse proxy / DDoS protection
  |
FASTNETPAY public HTTPS domain
  |
Docker host or VPS
  |-- fastnetpay_app, internal HTTP only
  |-- fastnetpay_db, private MySQL only
  |-- FreeRADIUS, private UDP 1812/1813 to routers
  |-- monitoring and backups
  |
VPN or private routed network
  |
MikroTik routers at hotspot/PPPoE sites
```

For a single local site, FASTNETPAY may sit on `192.168.88.10` and the MikroTik LAN gateway may be `192.168.88.1`. For remote sites, use WireGuard, IPsec, or another private tunnel. Do not expose RouterOS API to the public internet.

## Flow Overview

### Hotspot Flow

1. Customer connects to WiFi.
2. MikroTik gives DHCP and DNS.
3. MikroTik Hotspot intercepts unauthenticated HTTP/HTTPS access.
4. MikroTik serves a lightweight login/redirect page or redirects to the FASTNETPAY customer portal.
5. FASTNETPAY shows packages, vouchers, login, and M-Pesa STK Push.
6. Customer pays or activates a voucher.
7. FASTNETPAY activates the plan through direct RouterOS API or RADIUS data.
8. MikroTik grants access. If the user already has a hotspot session, FASTNETPAY can reconnect/disconnect through API or RADIUS Disconnect-Message where supported.

### PPPoE Flow

1. Customer CPE starts PPPoE discovery.
2. MikroTik PPPoE server receives the login.
3. Authentication is checked locally on MikroTik, through direct API-created `/ppp secret`, or through RADIUS.
4. FASTNETPAY controls profile, pool, speed, expiry, and payment status.
5. Accounting is stored by RADIUS where enabled.

### RADIUS Flow

1. MikroTik sends Access-Request to FreeRADIUS.
2. FreeRADIUS reads the SQL tables controlled by FASTNETPAY.
3. FASTNETPAY plans produce reply attributes such as speed limits, session limits, data limits, expiration, pools, or static IPs.
4. MikroTik sends accounting Start, Interim-Update, and Stop packets.
5. FASTNETPAY can use RADIUS accounting for session reporting and Disconnect-Message for active-session refresh.

For scale, multi-router deployments should prefer RADIUS for authentication/accounting and reserve direct RouterOS API for provisioning, health checks, manual sync, and emergency disconnects.

### M-Pesa Payment Activation Flow

1. Customer selects a package.
2. Customer selects M-Pesa STK Push.
3. FASTNETPAY normalizes the phone number and sends STK Push server-to-server.
4. Safaricom calls the configured HTTPS callback URL.
5. FASTNETPAY matches `CheckoutRequestID`, validates amount/status, and updates the transaction.
6. On success, FASTNETPAY calls `Package::rechargeUser()`.
7. `Package::rechargeUser()` selects the device path:
   - RADIUS when the plan is marked as RADIUS.
   - `MikrotikPppoe` for PPPoE.
   - `MikrotikHotspot` for Hotspot.
8. The package is activated and the router session is refreshed where possible.

## Captive Portal Placement

Recommended scalable design:

- FASTNETPAY hosts the real portal, package list, payment forms, and voucher activation.
- MikroTik hosts only a lightweight Hotspot login/redirect file.
- MikroTik walled garden allows unauthenticated customers to reach the FASTNETPAY portal and its static assets.

This keeps business logic, payments, SMS, and customer data on the server where it can be backed up, monitored, patched, and later made tenant-aware.

## RouterOS v7 Baseline Setup

Assumptions:

- FASTNETPAY server: `192.168.88.10`
- MikroTik LAN gateway: `192.168.88.1`
- WAN interface: `ether1`
- Customer LAN bridge: `bridge-lan`
- Customer subnet: `192.168.88.0/24`

Run commands from MikroTik Safe Mode or console access. Adjust interface names before pasting.

### LAN, DHCP, DNS, and NAT

```routeros
/interface bridge
add name=bridge-lan protocol-mode=rstp comment="FASTNETPAY customer LAN"

/ip address
add address=192.168.88.1/24 interface=bridge-lan comment="FASTNETPAY LAN gateway"

/ip pool
add name=hs-pool ranges=192.168.88.100-192.168.88.254

/ip dhcp-server
add name=dhcp-hotspot interface=bridge-lan address-pool=hs-pool lease-time=1h disabled=no

/ip dhcp-server network
add address=192.168.88.0/24 gateway=192.168.88.1 dns-server=192.168.88.1,1.1.1.1

/ip dns
set allow-remote-requests=yes servers=1.1.1.1,8.8.8.8

/ip firewall nat
add chain=srcnat out-interface=ether1 action=masquerade comment="NAT customers to internet"
```

### Hotspot

```routeros
/ip hotspot profile
add name=fastnetpay-hs hotspot-address=192.168.88.1 dns-name=login.fastnetpay.local html-directory=hotspot use-radius=yes login-by=http-chap,http-pap,mac-cookie http-cookie-lifetime=1d

/ip hotspot
add name=fastnetpay-hotspot interface=bridge-lan address-pool=hs-pool profile=fastnetpay-hs disabled=no

/ip hotspot user profile
add name=expired rate-limit=512K/512K shared-users=1 comment="Fallback profile for expired users"
```

For direct API mode, FASTNETPAY will create `/ip hotspot user` entries and profiles. For RADIUS mode, MikroTik should consult FreeRADIUS and the plan should be marked as RADIUS inside FASTNETPAY.

### PPPoE

```routeros
/ip pool
add name=pppoe-pool ranges=10.20.0.10-10.20.15.254

/ppp profile
add name=fastnetpay-pppoe local-address=10.20.0.1 remote-address=pppoe-pool use-compression=no use-encryption=no only-one=yes

/interface pppoe-server server
add service-name=FASTNETPAY interface=bridge-lan default-profile=fastnetpay-pppoe authentication=pap,chap one-session-per-host=yes disabled=no
```

For production PPPoE, RADIUS is the better long-term architecture because accounting, speed limits, one-session rules, and multi-router reporting stay centralized.

### RADIUS Client

Use a long random secret and keep it different per router/site.

```routeros
/radius
add service=hotspot,ppp address=192.168.88.10 secret="CHANGE_LONG_RADIUS_SECRET" authentication-port=1812 accounting-port=1813 timeout=3s accounting-backup=no

/radius incoming
set accept=yes port=3799

/ip hotspot profile
set [find name=fastnetpay-hs] use-radius=yes radius-accounting=yes interim-update=5m

/ppp aaa
set use-radius=yes accounting=yes interim-update=5m
```

FASTNETPAY side:

1. Import `install/radius.sql` if the RADIUS tables are not present.
2. Configure the RADIUS DB environment variables if the RADIUS DB is separate from the main DB.
3. Enable Radius in Settings.
4. Add the NAS/router in the Radius NAS screen with the same secret.
5. Restart FreeRADIUS after changing NAS client definitions if your FreeRADIUS deployment requires static client reloads.

CoA/disconnect note: RouterOS supports Disconnect-Message through `/radius incoming`. The current PHP code calls `radclient`, so production containers or hosts need FreeRADIUS client utilities installed.

### RouterOS API

Current FASTNETPAY direct API expects host and port in the router IP field, for example:

```text
192.168.88.1:8728
```

Create a dedicated API user:

```routeros
/user group
add name=fastnetpay-api policy=api,read,write,test,!local,!telnet,!ssh,!ftp,!reboot,!policy,!password,!web,!sniff,!sensitive,!romon

/user
add name=fastnetpay-api group=fastnetpay-api password="CHANGE_LONG_RANDOM_PASSWORD"
```

For current compatibility on a private LAN or VPN:

```routeros
/ip service
set [find name=telnet] disabled=yes
set [find name=ftp] disabled=yes
set [find name=www] disabled=yes
set [find name=api] address=192.168.88.10/32 port=8728 disabled=no
set [find name=winbox] address=192.168.88.0/24 disabled=no
set [find name=ssh] address=192.168.88.10/32 disabled=no
```

Preferred future target:

```routeros
/ip service
set [find name=api] disabled=yes
set [find name=api-ssl] address=192.168.88.10/32 port=8729 disabled=no certificate=router-api-cert
```

Before switching production to API-SSL, add/test an API-SSL option in FASTNETPAY or verify the current RouterOS PHP client can connect to `8729` with the required TLS behavior.

## Walled Garden

For STK Push, the customer's browser mainly needs access to FASTNETPAY. Daraja token/STK calls are server-to-server from FASTNETPAY, and the Safaricom callback reaches FASTNETPAY from the internet, not from the hotspot client.

Minimum walled garden:

```routeros
/ip hotspot walled-garden
add dst-host=fastnetpay.example.com comment="FASTNETPAY portal"
add dst-host=*.fastnetpay.example.com comment="FASTNETPAY assets and subdomains"

/ip hotspot walled-garden ip
add dst-address=192.168.88.10 action=accept comment="Local FASTNETPAY server"
```

Optional support links:

```routeros
/ip hotspot walled-garden
add dst-host=wa.me comment="Optional WhatsApp support"
add dst-host=*.whatsapp.com comment="Optional WhatsApp support"
```

Only add payment-provider browser domains if the customer browser must load them directly. Avoid broad wildcards that create payment bypass paths.

## FASTNETPAY Admin Setup

### Direct API Mode

1. Go to Routers.
2. Add router:
   - Router name: stable site name, for example `Main-Branch`.
   - IP address: `192.168.88.1:8728` or VPN address.
   - Username: `fastnetpay-api`.
   - Password: the strong API password.
3. Test connection.
4. Create bandwidth plans.
5. Create Hotspot or PPPoE plans assigned to this router.

### RADIUS Mode

1. Enable Radius in Settings.
2. Add NAS with the MikroTik IP or subnet and the same RADIUS secret.
3. Create bandwidth plans.
4. Create Hotspot or PPPoE plans and mark them as Radius.
5. Ensure FreeRADIUS reads the same SQL tables FASTNETPAY writes.
6. Verify accounting writes into `radacct`.

## Automatic Internet Refresh After Payment

Use the strongest available method for the selected integration:

- Direct Hotspot API: call `connect_customer()` when the session has `nux-mac` and `nux-ip`, or disconnect old active sessions so the user reauthenticates.
- Direct PPPoE API: remove/disconnect `/ppp active` so the CPE reconnects and receives the new profile.
- RADIUS: send Disconnect-Message with `radclient`, then MikroTik reauthenticates and receives updated attributes.
- Fallback: show a payment success screen telling the user to reconnect WiFi or restart PPPoE if no active-session refresh is possible.

## Multi-Router and SaaS Preparation

Prepare these conventions now:

- Use stable router names, NAS shortnames, and RouterOS identities.
- Keep one router/NAS entry per physical site or routed site group.
- Use unique RADIUS secrets per router/site.
- Use VPN addressing for remote router management.
- Store `tenant_id` in future router, customer, plan, recharge, transaction, payment credential, SMS credential, and audit-log tables.
- Keep payment credentials per tenant, not global.
- Keep SMS sender/API credentials per tenant.
- Add per-tenant API keys for future captive portal and router agents.
- Add router health checks and last-seen timestamps.
- Add router grouping by tenant, region, site, and service type.

## Troubleshooting

Router API test fails:

- Check `/ip service print`.
- Confirm API is enabled on the expected port.
- Confirm firewall input allows FASTNETPAY server IP.
- Confirm router username policy includes API, read, write, and test.
- Confirm FASTNETPAY router IP includes the correct port.

RADIUS rejects users:

- Run `freeradius -X`.
- Check NAS secret and source IP.
- Check `radcheck`, `radreply`, and `radusergroup`.
- On MikroTik, run `/radius monitor 0` and enable radius debug logging temporarily.

Payment succeeds but internet does not activate:

- Confirm the plan has the correct router/device/RADIUS setting.
- Check FASTNETPAY logs for `Package::rechargeUser()` errors.
- Test router API or RADIUS separately.
- For RADIUS, confirm FreeRADIUS can see the newly written SQL rows.
- For active sessions, trigger disconnect/reconnect or ask the customer to reconnect.

Captive portal loads without CSS or JS:

- Add the FASTNETPAY asset host to walled garden.
- Prefer same-origin local assets.
- Avoid external CDNs for captive portal assets.

CoA/disconnect fails:

- Install FreeRADIUS client tools where FASTNETPAY executes `radclient`.
- Enable `/radius incoming`.
- Allow UDP `3799` from the RADIUS/FASTNETPAY host to the router.
- Match NAS secret exactly.

## Validation Checklist

- MikroTik hotspot redirects unauthenticated users.
- FASTNETPAY portal loads from the walled garden.
- Packages display correctly.
- M-Pesa STK Push starts.
- M-Pesa callback confirms the payment.
- Successful payment activates the selected plan.
- Failed/cancelled payment does not activate a plan.
- Direct API test works if direct router mode is used.
- RADIUS auth and accounting work if RADIUS mode is used.
- Disconnect/reconnect works for active sessions.
- Users cannot browse the internet before payment except through approved walled garden entries.

## Reference Links

- MikroTik RouterOS API: https://help.mikrotik.com/docs/spaces/ROS/pages/47579160/API
- MikroTik RouterOS RADIUS: https://help.mikrotik.com/docs/spaces/ROS/pages/328097/RADIUS
- MikroTik HotSpot captive portal and walled garden: https://help.mikrotik.com/docs/spaces/ROS/pages/56459266/HotSpot%2B-%2BCaptive%2Bportal
