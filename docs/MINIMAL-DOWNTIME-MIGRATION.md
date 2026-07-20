# FASTNETPAY Minimal-Downtime Migration

## Plan

1. Prepare the new VPS fully.
2. Lower DNS TTL where records are DNS-only.
3. Restore a recent backup to the new VPS.
4. Start the stack on the new VPS without changing production DNS.
5. Verify with local hosts override or direct origin testing.
6. Put production into maintenance/write-freeze mode.
7. Take a final database dump and upload sync.
8. Restore final data on the new VPS.
9. Switch Cloudflare origin A records.
10. Validate web, tenants, callbacks, VPN, cron and routers.
11. Keep the old VPS online for rollback until stable.

## Estimated Downtime

With no database replication, expect downtime during final write freeze, final dump, restore and validation. For the current data size, plan a maintenance window and measure with a staging rehearsal.

Do not allow writes to both old and new databases unless replication has been deliberately configured.
