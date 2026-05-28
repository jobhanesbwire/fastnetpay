# FASTNETPAY Router Provisioning Templates

These templates are seeded into `router_provisioning_templates` the first time the wizard opens.

## Small Hotspot

- Use case: cafe, small estate, small shop WiFi.
- Profile: Hotspot Only.
- LAN: `192.168.88.1/24`
- Hotspot pool: `192.168.88.50-192.168.88.250`
- DNS name: `login.fastnetpay.local`
- Security: Recommended.

Recommended packages:
- 1 hour light browsing
- Daily unlimited with rate limit
- Weekly plan for repeat users

## Hostel WiFi

- Use case: student hostel, apartments, short-term residents.
- Profile: Hotspot Only.
- LAN: `10.10.0.1/22`
- Pool: `10.10.1.10-10.10.3.250`
- DNS name: `wifi.fastnetpay.local`
- Security: Recommended.

Recommended package mapping:
- Shared users: 1 or 2
- Strict validity and idle timeout
- MPESA STK Push as default gateway

## School WiFi

- Use case: school/private institution.
- Profile: Hotspot Only.
- LAN: `10.20.0.1/22`
- Pool: `10.20.1.10-10.20.3.250`
- DNS name: `school.fastnetpay.local`
- Security: Strict after testing.

Recommended package mapping:
- Staff profile
- Student profile
- Guest profile

## Market/Public WiFi

- Use case: public hotspots, market WiFi, bus stage WiFi.
- Profile: Hotspot Only.
- LAN: `172.16.10.1/23`
- Pool: `172.16.10.50-172.16.11.250`
- DNS name: `pay.fastnetpay.local`
- Security: Strict after confirming management access.

Recommended package mapping:
- Low-cost short-duration plans
- Shared users: 1
- Strong abuse/rate controls

## PPPoE ISP

- Use case: fiber, wireless last mile, fixed subscribers.
- Profile: PPPoE Only.
- Gateway: `100.64.0.1/24`
- PPPoE pool: `100.64.1.10-100.64.1.250`
- Security: Recommended.

Recommended package mapping:
- PPPoE profiles by speed tier
- One active session per customer
- RADIUS accounting when available

## Mixed Hotspot + PPPoE ISP

- Use case: operator serving prepaid hotspot and fixed PPPoE clients.
- Profile: Hotspot + PPPoE.
- Hotspot LAN: `192.168.90.1/24`
- Hotspot pool: `192.168.90.50-192.168.90.250`
- PPPoE pool: `100.64.10.10-100.64.10.250`
- DNS name: `portal.fastnetpay.local`
- Security: Recommended.

Recommended package mapping:
- Hotspot daily/weekly packs
- PPPoE monthly tiers
- MPESA STK Push enabled for prepaid top-ups

## IP Range Notes

- Avoid overlapping WAN, LAN, Hotspot, PPPoE, and management networks.
- For production PPPoE CGNAT, use `100.64.0.0/10` internally where appropriate.
- For public IP PPPoE customers, map public pools deliberately and document assignments.
