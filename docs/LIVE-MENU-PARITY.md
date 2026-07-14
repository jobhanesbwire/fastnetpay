# Live Menu Parity Notes

FASTNETPAY was compared with the live WiFiPay-style admin sidebar as a reference for mother-system operations.

## Added Locally

- Clients now includes Client Locations through the local `maps/customer` route.
- Sales & Recharge now includes Expiring Today, Expiration Tracker, and Not Expired views through local `plan/*` routes.
- Payments now includes Gateway Audit for `mpesastkpush` and Pending Reconciliation for Jovi-Pay transactions.
- MikroTik now includes Router Maps and ODP / Fiber Maps through the local `maps/*` routes.
- SMS & Notifications now includes Query-Based SMS and Router-Based SMS shortcuts using the existing bulk SMS form.
- Tools now exposes common content pages: Order Voucher, Voucher Template, Customer Announcement, Registration Info, Privacy Policy, and Terms and Conditions.
- System / Logs now exposes Radius Logs when Radius is enabled.
- Point of Sale now has local product, stock, sales, report, and checkout screens.
- ACS now has a local device registry that can be assigned to customers.
- Support Tickets now has local create, list, filter, view, update, and comment flows.
- Daily Reports now have live-style summary cards and quick date shortcuts while keeping tenant-scoped report data.

## Existing Local Equivalents

- Live `customers_PPOE` maps to local `customers/pppoe`.
- Live `map/customer` maps to local `maps/customer`.
- Live `reports/mpesaLogs` maps to local `reports/mpesa-logs`.
- Live `paymentgateway/audit/mpesa` maps to local `paymentgateway/audit/mpesastkpush`.
- Live MikroTik monitor links map to local `plugin/mikrotik_monitor_ui`, `plugin/mikrotik_monitor_hotspot`, and `plugin/mikrotik_monitor_pppoe`.

## Not Duplicated Yet

These live modules do not have local controllers/templates in this codebase yet, so they were intentionally not added as active menu links:

- Marketing pipeline, campaigns, and lead labels.
- Network layout designer.
- Dedicated DHCP leases, firewall rules, MikroTik bandwidth-test, and traffic-graph controllers.
- WhatsApp module.
- Dedicated uncompleted transaction resolution workflow.
- Traffic analysis and activated-account list controllers.

## SaaS Safety

Mother-system-only routes remain guarded by the existing SuperAdmin/Admin and tenant-portal checks in `header.tpl` and `Tenant::denyTenantAccessToSuperAdminRoutes()`. New menu links use existing local routes so tenant isolation and route-level access checks continue to apply.
