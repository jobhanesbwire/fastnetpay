# Expired Client Auto-Disconnect

FASTNETPAY now includes an expiry worker that complements the existing PHPNuxBill cron.

Routes:

- Status page: `/?_route=expiry/status`
- Manual run: use “Run Expiry Check Now” on the status page

What it does:

- finds active recharges whose expiration date/time has passed;
- calls the existing PHPNuxBill device adapter for Hotspot or PPPoE;
- disconnects active sessions and removes/disables access according to the existing device logic;
- marks the recharge as `off`;
- records every disconnect attempt in `expiry_worker_logs`;
- records each worker run in `expiry_worker_runs`;
- warns on the dashboard when cron/expiry health is stale.

Production cron example:

```bash
* * * * * docker exec fastnetpay_app php /var/www/html/system/cron.php >/dev/null 2>&1
```

If users keep browsing after expiry, check:

1. `/?_route=expiry/status`
2. `docker logs fastnetpay_app`
3. router credentials in `/?_route=routers`
4. whether the expired recharge has the correct router name
5. whether the MikroTik API user can remove Hotspot active users or PPPoE active sessions
