# FASTNETPAY PPPoE Sync

FASTNETPAY uses the existing PHPNuxBill device layer for PPPoE sync.

When a PPPoE package is created or updated, the MikroTik PPP profile is created/updated through `MikrotikPppoe`.

When a client is recharged or renewed:

- FASTNETPAY creates/updates the active recharge record.
- `Package::rechargeUser()` resolves the plan device.
- `MikrotikPppoe::add_customer()` creates or updates the `/ppp secret`.
- Expired or removed services disable/remove the customer from MikroTik through the existing device method.

Manual sync is available from the Clients table and customer view through:

```text
/?_route=customers/sync/<customer_id>
```

The sync action now instantiates the device class correctly before checking `sync_customer()`, so PPPoE users can be pushed again after profile or credential changes.

Validation on MikroTik:

```routeros
/ppp secret print where name="CLIENT_USERNAME"
/ppp active print where name="CLIENT_USERNAME"
/ppp profile print where name~"FNP"
```

For multi-router deployments, map each PPPoE client to the correct router through the package/router assignment. Do not push PPPoE users to every router unless the client is intentionally shared across sites.
