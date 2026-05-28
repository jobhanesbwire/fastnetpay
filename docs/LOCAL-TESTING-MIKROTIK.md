# Local MikroTik Testing

Test environment:

- FASTNETPAY: `http://localhost:8088`
- MikroTik: `192.168.88.1`
- Test username: `admin`
- Test password: empty only for lab reset testing

Recommended reset test:

1. Reset MikroTik with default configuration.
2. Connect this machine to the router LAN.
3. Confirm Winbox works.
4. In FASTNETPAY, open `/?_route=routers/provision/3` or add a new router.
5. Use:
   - Host: `192.168.88.1`
   - API port: `8728`
   - Bootstrap/Admin username: `admin`
   - Bootstrap/Admin password: blank only for lab
   - FASTNETPAY API username: `fastnet-api-usr`
   - FASTNETPAY API password: choose a strong test password
6. Click Test Connection.

FASTNETPAY will create or repair `fastnet-api-usr`, reconnect using it, and store it on the router record for future API communication.

If the wizard says TCP is open but RouterOS did not answer API `/login`, check in Winbox:

```routeros
/ip service print
/ip service enable api
/ip service set api port=8728
/user print
```

Some reset states may require setting a temporary admin password before API logins work reliably. For production create a dedicated API user instead of using blank `admin`.

To verify router backups after provisioning:

```routeros
/file print where name~"before_fastnetpay"
```

To verify Hotspot and PPPoE:

```routeros
/ip hotspot print
/ip hotspot user profile print where name~"FNP"
/interface pppoe-server server print
/ppp profile print where name~"FNP"
```
