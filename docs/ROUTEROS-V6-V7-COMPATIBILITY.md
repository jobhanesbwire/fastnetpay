# RouterOS v6/v7 Compatibility

The FASTNETPAY Router Provisioning Wizard generates conservative RouterOS commands that work across RouterOS v6 and v7 where possible.

## Supported Areas

- System identity and clock.
- DNS server configuration.
- Interface lists and members.
- NAT masquerade.
- IP pools.
- DHCP server and DHCP network.
- Hotspot profiles, servers, and user profiles.
- PPP profiles and PPPoE server.
- RADIUS client and PPP AAA when FASTNETPAY RADIUS is enabled.
- Hotspot walled garden.
- Firewall filter and address-list hardening.
- API/API-SSL service restrictions.

## Avoided By Default

- RouterOS container features.
- Destructive reset commands.
- Removing existing firewall rules.
- Removing existing Hotspot/PPPoE users.
- Version-specific advanced routing syntax.

## Version Differences To Watch

- RouterOS v7 has newer routing and firewall capabilities, but the wizard avoids those for compatibility.
- API-SSL behavior depends on router certificates and TLS support.
- Some older v6 builds may reject newer TLS ciphers; use API temporarily or upgrade RouterOS.
- FreeRADIUS/CoA behavior depends on your FASTNETPAY server-side RADIUS deployment.

## Fallback Behaviour

- If version detection fails, the wizard still allows preview but automatic apply should wait until connectivity is fixed.
- If API-SSL fails, retry with plain API from a restricted FASTNETPAY server IP.
- If backup export fails, the wizard reports it and documents manual MikroTik export steps.

## Safety Notes

Always preview first, keep out-of-band router access available, and apply Strict ISP Mode only after confirming the FASTNETPAY server IP and management access rules.
