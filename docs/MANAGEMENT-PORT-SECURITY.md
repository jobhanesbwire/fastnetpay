# FASTNETPAY Management Port Security

The provisioning wizard treats the management port as admin-only access, not a customer LAN. The recommended port is `ether4`.

What the wizard generates:

- keeps the management interface out of `fastnetpay-bridge`;
- adds it to a `MGMT` interface list;
- allows internet through WAN NAT for admin testing;
- blocks forwarding from management to the customer bridge;
- warns when more than one device is visible on the management port;
- optionally binds one trusted MAC address to one controlled IP lease;
- optionally adds bridge filter rules to drop unknown MACs where RouterOS supports it.

Recommended production practice:

- plug only the admin laptop or management AP into `ether4`;
- set a trusted MAC in the wizard before enabling strict one-device enforcement;
- keep Winbox/API reachable through management or VPN until final tests pass;
- avoid using the management port to feed downstream routers or customer switches.
