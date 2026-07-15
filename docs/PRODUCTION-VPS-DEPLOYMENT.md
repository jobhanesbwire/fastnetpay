# FASTNETPAY Production VPS Deployment Handover

Date: 2026-07-15

## Server

- Public IP: `212.95.35.229`
- Domain planned: `fastnetpay.co.ke`
- Deployment user: `fastnetpay`
- SSH command: `ssh fastnetpay@212.95.35.229`
- App path: `/opt/fastnetpay/app`
- Protected inventory: `/opt/fastnetpay/secrets/deployment-inventory.env`
- Backup path: `/var/backups/fastnetpay`

Secrets are stored only on the VPS in permission-restricted files. Do not commit `.env.production` or private VPN keys.

## Baseline

- OS: Ubuntu 24.04.4 LTS
- Kernel: `6.8.0-134-generic`
- CPU: 4 vCPU
- RAM: about 6 GB
- Disk: about 99 GB root disk
- Swap: 2 GB `/swapfile`
- Timezone: `Africa/Nairobi`

## Running Services

- Docker Engine and Docker Compose plugin
- `fastnetpay_db` on the internal Docker network only
- `fastnetpay_app` on the internal Docker network only
- `fastnetpay_scheduler` for `system/cron.php`
- `fastnetpay_nginx` publicly serving HTTP on port `80`
- WireGuard `wg0` on UDP `51820`
- Fail2Ban for SSH
- UFW firewall
- Docker `DOCKER-USER` ingress guard

## Firewall

Publicly allowed:

- `22/tcp` SSH
- `80/tcp` HTTP
- `443/tcp` reserved for HTTPS after DNS/TLS
- `51820/udp` WireGuard router VPN

Blocked from public access:

- MySQL `3306`
- Redis `6379`
- MikroTik API `8728/8729`
- Winbox `8291`
- Docker/internal container services

## Docker Operations

```bash
cd /opt/fastnetpay/app
docker compose --env-file .env.production -f docker-compose.prod.yml ps
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f fastnetpay_app
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f fastnetpay_scheduler
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

## Backups

Daily backup cron:

```text
15 2 * * * /opt/fastnetpay/bin/backup-fastnetpay.sh >> /var/log/fastnetpay-backup.log 2>&1
```

The backup script creates:

- MySQL dump: `/var/backups/fastnetpay/db-*.sql.gz`
- Upload/env/config archive: `/var/backups/fastnetpay/uploads-*.tar.gz`

Current limitation: backups are still local to the VPS. Add an encrypted off-server backup destination before full go-live.

## DNS Records Required

Create these records at the DNS provider:

```text
fastnetpay.co.ke        A      212.95.35.229
www.fastnetpay.co.ke    A      212.95.35.229
*.fastnetpay.co.ke      A      212.95.35.229
```

At deployment time the records had not propagated, so HTTPS certificates were not issued yet.

## HTTPS Next Step

After DNS resolves to `212.95.35.229`:

1. Configure TLS for `fastnetpay.co.ke`.
2. Configure wildcard TLS for `*.fastnetpay.co.ke` using DNS challenge.
3. Change `/opt/fastnetpay/app/.env.production`:

```env
APP_URL=https://fastnetpay.co.ke
SECURITY_TRUST_PROXY_HEADERS=yes
```

4. Restart:

```bash
cd /opt/fastnetpay/app
docker compose --env-file .env.production -f docker-compose.prod.yml up -d
```

Do not enable HSTS until HTTPS and tenant subdomains are verified.

## Validation Completed

- SSH key login works for `fastnetpay`.
- Direct root SSH login disabled.
- SSH password login disabled.
- UFW active.
- Fail2Ban SSH jail active.
- Docker stack builds and starts.
- MySQL is healthy and private.
- Public HTTP login page loads.
- Scheduler cron runs expiry/SaaS billing workers.
- Security throttling tables exist.
- Tenant tables exist.
- AI crawler user-agent test returns `403`.
- Default upload assets restored.
- Backup script dry-run completed successfully.

## Remaining Manual Actions

- Point DNS records to the VPS.
- Add HTTPS/wildcard certificate after DNS propagation.
- Configure Cloudflare WAF/rate limits after DNS is proxied.
- Add off-server encrypted backups.
- Configure live M-Pesa/Jovi-Pay callback URLs after HTTPS is live.
- Create/change production SuperAdmin credentials immediately.
- Add router WireGuard peers through the provisioning workflow.
- Decide whether SSTP is still required for older RouterOS v6 routers and install it only with a trusted TLS certificate.
