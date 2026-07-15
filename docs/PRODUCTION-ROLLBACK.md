# Production Rollback

Before each production change, create a checkpoint:

```bash
sudo /opt/fastnetpay/bin/backup-fastnetpay.sh
sudo cp /opt/fastnetpay/app/.env.production /opt/fastnetpay/backups/checkpoint-YYYYMMDD-HHMMSS/
sudo cp /opt/fastnetpay/app/docker-compose.prod.yml /opt/fastnetpay/backups/checkpoint-YYYYMMDD-HHMMSS/
sudo cp /opt/fastnetpay/app/.docker/nginx/fastnetpay.conf /opt/fastnetpay/backups/checkpoint-YYYYMMDD-HHMMSS/
```

Rollback app files:

```bash
cd /opt/fastnetpay/app
docker compose --env-file .env.production -f docker-compose.prod.yml down
rsync -a /opt/fastnetpay/backups/checkpoint-YYYYMMDD-HHMMSS/app/ /opt/fastnetpay/app/
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

Database restore should be done only from a verified SQL backup and after stopping app writes.
