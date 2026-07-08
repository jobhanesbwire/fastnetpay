# MikroTik Firewall Rules for FASTNETPAY

These RouterOS v7 rules are templates for a FASTNETPAY ISP/hotspot deployment. Apply from console or Safe Mode. Adjust interface names, subnets, server IPs, and management networks before using them.

Example assumptions:

- WAN interface: `ether1`
- Customer LAN bridge: `bridge-lan`
- FASTNETPAY server: `192.168.88.10`
- Customer subnet: `192.168.88.0/24`
- Management VPN subnet: `10.255.0.0/24`
- RouterOS API current compatibility: TCP `8728`
- RouterOS API-SSL future target: TCP `8729`
- RADIUS auth/acct: UDP `1812/1813`
- RADIUS Disconnect-Message: UDP `3799`

## Interface Lists

```routeros
/interface list
add name=WAN comment="Internet-facing interfaces"
add name=LAN comment="Trusted LAN/customer bridge interfaces"
add name=MGMT comment="Management/VPN interfaces"

/interface list member
add list=WAN interface=ether1
add list=LAN interface=bridge-lan
```

Add your WireGuard/IPsec/VLAN management interface to `MGMT` when configured.

## Address Lists

```routeros
/ip firewall address-list
add list=fastnetpay-servers address=192.168.88.10 comment="FASTNETPAY app/RADIUS server"
add list=mgmt-allowed address=192.168.88.10 comment="FASTNETPAY server"
add list=mgmt-allowed address=10.255.0.0/24 comment="Management VPN"
add list=customer-subnets address=192.168.88.0/24 comment="Hotspot customer subnet"
```

Bogon examples for WAN source filtering:

```routeros
/ip firewall address-list
add list=bogons address=0.0.0.0/8
add list=bogons address=10.0.0.0/8
add list=bogons address=100.64.0.0/10
add list=bogons address=127.0.0.0/8
add list=bogons address=169.254.0.0/16
add list=bogons address=172.16.0.0/12
add list=bogons address=192.0.2.0/24
add list=bogons address=192.168.0.0/16
add list=bogons address=198.18.0.0/15
add list=bogons address=198.51.100.0/24
add list=bogons address=203.0.113.0/24
add list=bogons address=224.0.0.0/4
add list=bogons address=240.0.0.0/4
```

Do not drop private ranges on WAN if your upstream legitimately uses private handoff or CGNAT. Adjust before applying.

## RAW Pre-Filtering

RAW rules reduce connection tracking load during obvious abuse.

```routeros
/ip firewall raw
add chain=prerouting in-interface-list=WAN src-address-list=blacklist action=drop comment="Drop blacklisted sources before conntrack"
add chain=prerouting in-interface-list=WAN src-address-list=bogons action=drop comment="Drop invalid WAN source ranges"
```

## Basic Input Chain

Protect the router itself:

```routeros
/ip firewall filter
add chain=input action=accept connection-state=established,related,untracked comment="Accept established/related input"
add chain=input action=drop connection-state=invalid comment="Drop invalid input"

add chain=input action=accept protocol=icmp limit=10,20:packet comment="Allow limited ICMP"

add chain=input action=accept in-interface-list=LAN protocol=udp dst-port=67,68 comment="Allow DHCP from LAN"
add chain=input action=accept in-interface-list=LAN protocol=udp dst-port=53 comment="Allow DNS UDP from LAN"
add chain=input action=accept in-interface-list=LAN protocol=tcp dst-port=53 comment="Allow DNS TCP from LAN"

add chain=input action=accept src-address-list=mgmt-allowed protocol=tcp dst-port=22,8291 comment="Allow SSH/Winbox from management"
add chain=input action=accept src-address-list=fastnetpay-servers protocol=tcp dst-port=8728,8729 comment="Allow FASTNETPAY RouterOS API"
add chain=input action=accept src-address-list=fastnetpay-servers protocol=udp dst-port=3799 comment="Allow RADIUS Disconnect-Message"

add chain=input action=drop in-interface-list=WAN protocol=udp dst-port=53 comment="Block public DNS UDP"
add chain=input action=drop in-interface-list=WAN protocol=tcp dst-port=53 comment="Block public DNS TCP"

add chain=input action=drop in-interface-list=WAN comment="Drop all other WAN input"
```

If the router itself sends RADIUS to FASTNETPAY, that is output traffic and does not need an input allow rule for UDP `1812/1813`.

## Forward Chain

Protect traffic passing through the router:

```routeros
/ip firewall filter
add chain=forward action=accept connection-state=established,related,untracked comment="Accept established/related forward"
add chain=forward action=drop connection-state=invalid comment="Drop invalid forward"

add chain=forward action=drop connection-nat-state=!dstnat connection-state=new in-interface-list=WAN comment="Drop unsolicited WAN to LAN"
add chain=forward action=accept in-interface-list=LAN out-interface-list=WAN comment="Allow customers to internet after hotspot/PPPoE policy"
```

FastTrack caution: do not FastTrack managed customer traffic until you have verified it does not bypass queues, hotspot visibility, accounting, or shaping requirements. FASTNETPAY provisioning adds `FASTNETPAY hotspot queue guard upload` and `FASTNETPAY hotspot queue guard download` before the default FastTrack rule so Hotspot package speeds continue to be enforced.

## Router Management Brute Force Protection

```routeros
/ip firewall filter
add chain=input protocol=tcp dst-port=22,8291,8728,8729 connection-state=new src-address-list=mgmt-allowed action=accept comment="Allow management ports from trusted sources"
add chain=input protocol=tcp dst-port=22,8291,8728,8729 connection-state=new action=add-src-to-address-list address-list=router-bruteforce address-list-timeout=1d comment="Track unauthorized management attempts"
add chain=input src-address-list=router-bruteforce action=drop comment="Drop unauthorized management attempts"
```

The best brute-force protection is still not exposing management ports publicly.

## Port Scan Protection

```routeros
/ip firewall filter
add chain=input protocol=tcp psd=21,3s,3,1 action=add-src-to-address-list address-list=port-scanners address-list-timeout=1d comment="Detect TCP port scanners"
add chain=input src-address-list=port-scanners action=drop comment="Drop TCP port scanners"
```

Tune `psd` carefully if you have legitimate monitoring that touches several ports.

## DDoS Detection Template

This follows MikroTik's documented pattern: detect excessive new flows, list attacker/target, and drop early in RAW.

```routeros
/ip firewall address-list
add list=ddos-attackers comment="Dynamic DDoS attackers"
add list=ddos-targets comment="Dynamic DDoS targets"

/ip firewall filter
add chain=forward connection-state=new action=jump jump-target=detect-ddos comment="Jump new forwarded flows to DDoS detection"
add chain=detect-ddos dst-limit=32,32,src-and-dst-addresses/10s action=return comment="Return normal flow rates"
add chain=detect-ddos action=add-dst-to-address-list address-list=ddos-targets address-list-timeout=10m comment="Mark DDoS target"
add chain=detect-ddos action=add-src-to-address-list address-list=ddos-attackers address-list-timeout=10m comment="Mark DDoS attacker"

/ip firewall raw
add chain=prerouting src-address-list=ddos-attackers dst-address-list=ddos-targets action=drop comment="Drop DDoS traffic before conntrack"
```

Enable SYN cookies:

```routeros
/ip settings
set tcp-syncookies=yes
```

These rules reduce router load but do not replace upstream DDoS protection.

## Hotspot Walled Garden

Minimum FASTNETPAY portal access before login:

```routeros
/ip hotspot walled-garden
add dst-host=fastnetpay.example.com comment="FASTNETPAY portal"
add dst-host=*.fastnetpay.example.com comment="FASTNETPAY assets/subdomains"

/ip hotspot walled-garden ip
add dst-address=192.168.88.10 action=accept comment="Local FASTNETPAY server"
```

Optional WhatsApp support:

```routeros
/ip hotspot walled-garden
add dst-host=wa.me comment="Optional WhatsApp support"
add dst-host=*.whatsapp.com comment="Optional WhatsApp support"
```

Do not add broad bypass rules for Google, CDNs, or payment domains unless the customer browser truly needs them. STK Push API calls are performed by FASTNETPAY server-side.

## Hotspot Bypass Controls

Review these regularly:

```routeros
/ip hotspot ip-binding print
/ip hotspot walled-garden print
/ip hotspot walled-garden ip print
/ip hotspot active print
/ip hotspot cookie print
```

Avoid:

- `type=bypassed` for unknown devices.
- Full-subnet bypass rules.
- Walled garden wildcard domains that allow normal browsing before payment.
- Customer access to management VLANs or the FASTNETPAY database.

## RADIUS Firewall Notes

When FreeRADIUS is on `192.168.88.10`:

```routeros
/ip firewall filter
add chain=input src-address=192.168.88.10 protocol=udp dst-port=3799 action=accept comment="Allow RADIUS Disconnect-Message from FASTNETPAY/RADIUS"
```

On the FASTNETPAY server firewall, allow:

- UDP `1812` from MikroTik routers to FreeRADIUS.
- UDP `1813` from MikroTik routers to FreeRADIUS.
- Outbound UDP `3799` to MikroTik routers for Disconnect-Message.

## Validation Commands

```routeros
/ip service print
/ip firewall filter print stats
/ip firewall raw print stats
/radius monitor 0
/ip hotspot active print
/ppp active print
/log print where topics~"radius|hotspot|ppp|firewall"
```

Expected result:

- WAN cannot reach router management services.
- FASTNETPAY can reach RouterOS API only from its trusted IP/VPN.
- RADIUS auth/acct works.
- Disconnect-Message works if enabled.
- Customers cannot browse before Hotspot login except walled garden.
- Public DNS recursion from WAN is blocked.

## Reference Links

- MikroTik DDoS protection example: https://help.mikrotik.com/docs/spaces/ROS/pages/28606504/DDoS%2BProtection
- MikroTik firewall documentation: https://help.mikrotik.com/docs/spaces/ROS/pages/250708066/Firewall
- MikroTik RAW filtering: https://help.mikrotik.com/docs/spaces/ROS/pages/48660574/Filter
- MikroTik HotSpot walled garden: https://help.mikrotik.com/docs/spaces/ROS/pages/56459266/HotSpot%2B-%2BCaptive%2Bportal
