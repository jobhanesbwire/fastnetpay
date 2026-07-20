# FASTNETPAY VPS Migration

## Migration Bundle

A complete migration must include:

- Docker Compose files.
- Exact application image tag.
- `.env.production` transferred securely.
- MySQL dump.
- Redis handling plan.
- `system/uploads`.
- Tenant portal files.
- Nginx configuration and certificates.
- WireGuard configuration.
- SSTP configuration.
- Portainer data or stack definitions.
- Cron/scheduler configuration.
- Firewall notes.
- DNS records.
- Payment callback secrets.

Use `scripts/production/backup-fastnetpay.sh` to create the first bundle. Store it off-server after encryption.

## Secrets

Do not commit secrets to Git. Transfer production secrets over SSH/SCP to the new server and restrict permissions:

```bash
chmod 600 .env.production
chmod 700 /opt/fastnetpay/backups
```

## New VPS Preflight

Run:

```bash
scripts/production/preflight-fastnetpay-server.sh
```

Then restore and validate.
