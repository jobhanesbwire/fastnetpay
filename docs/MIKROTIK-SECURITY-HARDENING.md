# MikroTik Security Hardening for FASTNETPAY

This guide hardens RouterOS for FASTNETPAY hotspot, PPPoE, RADIUS, and RouterOS API deployments. Apply from console or Safe Mode, and adjust interface names before pasting commands.

## Security Goals

- MikroTik controls network access.
- FASTNETPAY controls billing, packages, payments, SMS, and user lifecycle.
- Router management is reachable only from trusted networks.
- Customers cannot bypass hotspot or PPPoE payment enforcement.
- RADIUS, API, and CoA are private and authenticated.
- Remote sites use VPN instead of public router management ports.

## Initial Router Baseline

```routeros
/system identity
set name=FNP-MAIN-01

/system clock
set time-zone-name=Africa/Nairobi

/system ntp client
set enabled=yes
```

Upgrade RouterOS and firmware during a maintenance window:

```routeros
/system package update check-for-updates
/system routerboard print
```

Create individual admin users. Do not share the default `admin` account.

```routeros
/user
add name=netadmin group=full password="CHANGE_LONG_RANDOM_PASSWORD"
disable admin
```

## Management Plane

Disable unused services and restrict the ones you keep:

```routeros
/ip service
set [find name=telnet] disabled=yes
set [find name=ftp] disabled=yes
set [find name=www] disabled=yes
set [find name=www-ssl] disabled=yes
set [find name=ssh] address=192.168.88.10/32,10.255.0.0/24 port=22 disabled=no
set [find name=winbox] address=192.168.88.0/24,10.255.0.0/24 disabled=no
set [find name=api] address=192.168.88.10/32 port=8728 disabled=no
set [find name=api-ssl] address=192.168.88.10/32,10.255.0.0/24 port=8729 disabled=yes
```

Use `api` on port `8728` only where current FASTNETPAY compatibility requires it and only across LAN/VPN. Prefer `api-ssl` on port `8729` after FASTNETPAY support is verified.

Create a dedicated FASTNETPAY API user:

```routeros
/user group
add name=fastnetpay-api policy=api,read,write,test,!local,!telnet,!ssh,!ftp,!reboot,!policy,!password,!web,!sniff,!sensitive,!romon

/user
add name=fastnetpay-api group=fastnetpay-api password="CHANGE_LONG_RANDOM_PASSWORD"
```

## RADIUS Security

Use a unique long secret per router:

```routeros
/radius
add service=hotspot,ppp address=192.168.88.10 secret="CHANGE_LONG_RADIUS_SECRET" authentication-port=1812 accounting-port=1813 timeout=3s

/radius incoming
set accept=yes port=3799
```

Restrict RADIUS traffic in firewall:

- Router to RADIUS server: UDP `1812` and `1813`.
- RADIUS server to router: UDP `3799` for Disconnect-Message.
- No public RADIUS ports.

Enable accounting:

```routeros
/ip hotspot profile
set [find name=fastnetpay-hs] use-radius=yes radius-accounting=yes interim-update=5m

/ppp aaa
set use-radius=yes accounting=yes interim-update=5m
```

## Hotspot Security

Recommended Hotspot profile direction:

```routeros
/ip hotspot profile
set [find name=fastnetpay-hs] login-by=http-chap,http-pap,mac-cookie http-cookie-lifetime=1d use-radius=yes radius-accounting=yes interim-update=5m
```

Controls:

- Keep MAC-cookie lifetime short enough to limit shared-device abuse.
- Use `shared-users=1` for individual retail packages unless a package intentionally allows multiple devices.
- Use idle timeout and keepalive timeout to clean abandoned sessions.
- Avoid broad IP bindings with `bypassed`.
- Keep walled garden narrow.
- Keep AP client isolation enabled on public WiFi where appropriate.
- Monitor `/ip hotspot active` and RADIUS accounting for simultaneous-session abuse.

## PPPoE Security

```routeros
/interface pppoe-server server
set [find service-name=FASTNETPAY] one-session-per-host=yes authentication=pap,chap disabled=no

/ppp aaa
set use-radius=yes accounting=yes interim-update=5m
```

Controls:

- Prefer RADIUS `Simultaneous-Use` and profile attributes for scale.
- Use per-customer PPPoE usernames and strong generated passwords.
- Disconnect active sessions when plan status changes.
- Monitor duplicate logins and unusual session duration.

## DNS and DHCP Abuse Prevention

DNS should serve customers on LAN/hotspot, not the public WAN:

```routeros
/ip dns
set allow-remote-requests=yes servers=1.1.1.1,8.8.8.8

/ip firewall filter
add chain=input in-interface-list=WAN protocol=udp dst-port=53 action=drop comment="Drop public DNS UDP"
add chain=input in-interface-list=WAN protocol=tcp dst-port=53 action=drop comment="Drop public DNS TCP"
```

DHCP recommendations:

- Use reasonable lease times.
- Use AP isolation for public WiFi.
- Do not bridge untrusted customer segments with management VLANs.
- Use VLANs for customer, management, backhaul, and server networks.

## Remote Site Management

For remote routers:

- Use WireGuard or IPsec to connect each router to the FASTNETPAY management network.
- Use private tunnel IPs for RouterOS API and RADIUS.
- Do not expose API, Winbox, SSH, or RADIUS on public WAN.
- Use unique router identity and NAS shortname per site.
- Keep a documented out-of-band recovery method.

## Hotspot Bypass Prevention

- Do not create broad `bypassed` IP bindings.
- Do not add wide walled garden wildcards.
- Do not place broad firewall forward accepts before hotspot dynamic enforcement.
- Keep customer traffic separated from management VLANs.
- Use RADIUS or API sync to remove/expire unpaid users.
- Set one-session policies for plans that should not be shared.
- Review `/ip hotspot host`, `/ip hotspot active`, and `/ip hotspot cookie` regularly.

## Logging

Send logs to a central collector:

```routeros
/system logging action
add name=fastnetpay-syslog target=remote remote=192.168.88.10 remote-port=514

/system logging
add topics=critical,error,warning action=fastnetpay-syslog
add topics=radius,account action=fastnetpay-syslog
add topics=hotspot,info action=fastnetpay-syslog
```

Enable debug topics only temporarily:

```routeros
/system logging
add topics=radius,debug action=memory
```

Remove debug logging after troubleshooting.

## Backup

```routeros
/export hide-sensitive file=fnp-main-01-export
/system backup save name=fnp-main-01-encrypted password="CHANGE_BACKUP_PASSWORD"
```

Store backups off-router. Test restore on a spare router or CHR before relying on backups.

## Validation

```routeros
/ip service print
/ip firewall filter print stats
/radius monitor 0
/ip hotspot active print
/ppp active print
/log print where topics~"radius|hotspot|ppp|firewall"
```

Confirm:

- API is reachable only from FASTNETPAY/VPN.
- RADIUS requests and accounting are working.
- CoA/Disconnect-Message works if enabled.
- Hotspot users cannot browse before login except walled garden.
- PPPoE sessions receive the expected profile/speed.
- Public WAN cannot access DNS, API, Winbox, SSH, phpMyAdmin, MySQL, or RADIUS.

## Reference Links

- MikroTik RouterOS services/API ports: https://help.mikrotik.com/docs/spaces/ROS/pages/47579160/API
- MikroTik RouterOS RADIUS and Disconnect-Message: https://help.mikrotik.com/docs/spaces/ROS/pages/328097/RADIUS
- MikroTik HotSpot captive portal and walled garden: https://help.mikrotik.com/docs/spaces/ROS/pages/56459266/HotSpot%2B-%2BCaptive%2Bportal
