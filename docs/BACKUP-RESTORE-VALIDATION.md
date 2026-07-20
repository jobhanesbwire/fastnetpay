# FASTNETPAY Backup, Restore And Validation

## Scripts

- `scripts/production/backup-fastnetpay.sh`
- `scripts/production/verify-fastnetpay-backup.sh`
- `scripts/production/restore-fastnetpay.sh`
- `scripts/production/preflight-fastnetpay-server.sh`
- `scripts/production/validate-fastnetpay-migration.sh`

## Daily Backup

Example cron:

```cron
15 2 * * * cd /opt/fastnetpay/app && FASTNETPAY_BACKUP_RETENTION_DAYS=14 scripts/production/backup-fastnetpay.sh >> /var/log/fastnetpay-backup.log 2>&1
```

## Weekly Full Backup

Copy the backup directory off-server after encryption. Include database, uploads, deployment config, Nginx, WireGuard, SSTP and stack metadata.

## Verification

Run:

```bash
scripts/production/verify-fastnetpay-backup.sh /opt/fastnetpay/backups/YYYYMMDD-HHMMSS
```

## Restore Rehearsal

A backup is not proven until restored in staging or a disposable VPS:

```bash
scripts/production/restore-fastnetpay.sh --backup /path/to/backup
scripts/production/restore-fastnetpay.sh --backup /path/to/backup --yes
scripts/production/validate-fastnetpay-migration.sh
```

The first command is a dry run.
