# FASTNETPAY Production Deployment

## Deployment Rule

Production deployments should come from `main` only, after staging validation and manual GitHub production approval.

## Current Production Shape

- One VPS.
- Cloudflare proxy for web domains.
- Docker Compose/Portainer stack.
- Nginx reverse proxy.
- One FASTNETPAY app container.
- One scheduler container.
- MySQL 8.
- Redis 7 available for opt-in shared sessions/cache.

## Deploy Steps

1. Confirm staging passed.
2. Create a production backup:

```bash
cd /opt/fastnetpay/app
scripts/production/backup-fastnetpay.sh
```

3. Record the currently running image tag.
4. Trigger the production workflow or Portainer deployment.
5. Validate health:

```bash
scripts/production/validate-fastnetpay-migration.sh
```

6. Check tenant login and callback routes.
7. Keep the previous image available for rollback.

## Rollback

Rollback through Portainer to the previous known-good image tag, then re-run:

```bash
scripts/production/validate-fastnetpay-migration.sh
```

If a database migration caused the issue, restore only after confirming the backup and understanding data loss risk.
