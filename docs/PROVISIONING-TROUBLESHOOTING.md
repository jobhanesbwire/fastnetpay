# Router Provisioning Troubleshooting

## Wizard Connects Then Aborts

If the browser reports:

```text
Unexpected token '<', "<!DOCTYPE "... is not valid JSON
```

the wizard expected a JSON response but received an HTML page. Common causes are an expired admin session, a production proxy error, a FASTNETPAY crash page, or a RouterOS API command timing out before PHP can return JSON.

For production SSTP routers, check whether the router is already registered by VPN IP, for example `10.100.1.1`. Do not recreate or rotate the `sstp-fastnetpay` client while FASTNETPAY is currently connected through that same tunnel. The wizard now guards this case and skips SSTP client recreation when the active API host equals the configured router VPN IP.

Inspect the last provisioning steps:

```sql
SELECT id, step_name, status, error_message
FROM router_provisioning_steps
ORDER BY id DESC
LIMIT 20;
```

If the last running step is in `VPN Remote Management`, reconnect locally through the management port, confirm SSTP is already up, or rerun the wizard after the stale run has been marked failed.

If FASTNETPAY reports:

```text
TCP port is open, but RouterOS did not answer the API /login request
```

The container can reach the router, but RouterOS is not completing the API login exchange.

Check in Winbox:

```routeros
/ip service print
/ip service enable api
/ip service set api port=8728
/ip firewall filter print
/user print
```

For reset routers, set a temporary password if blank admin API login is refused:

```routeros
/user set admin password=StrongTemporaryPassword
```

Then update the wizard password and test again.

## API User Creation Fails

FASTNETPAY requires this RouterOS user:

```text
fastnet-api-usr
```

If the wizard cannot create it:

1. Confirm bootstrap/admin credentials can log in through API.
2. Confirm the bootstrap user has permission to manage RouterOS users.
3. Confirm the FASTNETPAY Server IP is correct before API service restriction is applied.
4. In Winbox, inspect:

```routeros
/user print
/user group print
/ip service print
```

If needed, manually remove a broken API user and rerun the wizard:

```routeros
/user remove [find name="fastnet-api-usr"]
/user group remove [find name="fastnet-api"]
```

Do this only if you still have Winbox/admin access.

## Backup Fails

The wizard attempts both export and binary backup. Confirm files:

```routeros
/file print where name~"before_fastnetpay"
```

Do not use backup override unless you already downloaded a manual backup.

## Captive Portal Files Missing

Check that MikroTik can reach the FASTNETPAY server IP:

```routeros
/ping 192.168.88.10
/tool fetch url="http://192.168.88.10:8088/?_route=api/hotspot/packages" keep-result=no
```

If the fetch fails, fix the FASTNETPAY server IP, Docker port, firewall, or hotspot walled garden.

## M-Pesa Starts But User Is Not Activated

Check:

- M-Pesa gateway is enabled.
- Callback URL is reachable from Safaricom.
- `tbl_payment_gateway.status` becomes `2`.
- The selected plan router matches the provisioned router.
- MikroTik API credentials still work.

Duplicate callbacks are handled by the payment gateway status checks and should not double-credit a user.
