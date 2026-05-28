# FASTNETPAY MikroTik Hotspot Portal

FASTNETPAY can provision a lightweight captive portal directly into MikroTik Hotspot files.

Generated files:

- `login.html`
- `status.html`
- `logout.html`
- `alogin.html`
- `error.html`
- `md5.js`
- `fastnetpay-hotspot.css`
- `fastnetpay-hotspot.js`

The router downloads them with `/tool fetch` from:

```text
http://FASTNETPAY_SERVER:8088/?_route=api/hotspot/portal-file
```

The portal uses a router/site token and exposes only customer-safe operations:

- list hotspot packages
- start M-Pesa STK Push
- poll payment status
- activate voucher login

For local testing, set FASTNETPAY Server IP in the wizard to the LAN IP reachable from MikroTik, for example `192.168.88.10`. Do not use `localhost`, because on the router `localhost` means the router itself.

After provisioning, confirm files in Winbox:

```routeros
/file print where name~"hotspot"
```

If package cards do not load, check walled garden access to the FASTNETPAY server IP and confirm the router can fetch:

```routeros
/tool fetch url="http://192.168.88.10:8088/?_route=api/hotspot/packages" keep-result=no
```
