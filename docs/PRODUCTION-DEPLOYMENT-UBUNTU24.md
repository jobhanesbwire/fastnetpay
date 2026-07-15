# FASTNETPAY Production Deployment on Ubuntu 24

This guide prepares FASTNETPAY for a production VPS using Docker, MySQL on a private Docker network, and Nginx as the public reverse proxy.

## Production Architecture

```text
Internet / Cloudflare
        |
Ubuntu 24 VPS firewall
        |
fastnetpay_nginx :80
        |
fastnetpay_app :80 on private Docker network
        |
fastnetpay_db :3306 on private Docker network only
```

Production differences from local development:

- No public phpMyAdmin.
- No public MySQL port.
- PHP browser errors are disabled.
- Nginx applies connection and request throttling before PHP.
- FASTNETPAY also applies application-level route-aware throttling and logs blocked attempts.
- Known AI crawler user-agents are blocked by Nginx and the PHP throttle layer.

## VPS Preparation

Install Docker Engine and the Docker Compose plugin on Ubuntu 24, then clone the FASTNETPAY repository.

Copy the production env file:

```bash
cp .env.production.example .env.production
nano .env.production
```

Required changes:

- `APP_URL=https://your-real-domain`
- `APP_BASE_DOMAIN=your-real-domain`
- `DB_PASSWORD=<long random password>`
- `MYSQL_ROOT_PASSWORD=<long random password>`
- keep `APP_STAGE=Live`
- keep `APP_DISPLAY_ERRORS=0`

Use a non-root MySQL user in production. The production compose file creates `DB_USER` and `DB_PASSWORD` automatically when the MySQL volume is first initialized.

## Start Production Stack

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

Check status:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml ps
docker logs fastnetpay_nginx
docker logs fastnetpay_app
docker logs fastnetpay_db
```

The public service is `fastnetpay_nginx` on port `80`.

## Firewall Baseline

On the VPS, expose only the ports you need:

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

Do not expose MySQL, phpMyAdmin, MikroTik API, RADIUS, or Docker daemon ports to the internet.

## Cloudflare Readiness

When Cloudflare is in front of the VPS:

1. Point DNS to the VPS.
2. Proxy the DNS record through Cloudflare.
3. Add Cloudflare WAF/rate-limit rules for:
   - `?_route=admin`
   - `?_route=admin/post`
   - `?_route=login`
   - `?_route=api/*`
   - `?_route=api/jovipay/callback`
4. Restrict direct VPS access to Cloudflare IP ranges if possible.
5. In FASTNETPAY, enable `Trust Proxy Headers` only after direct public access is blocked.

The production env value is:

```env
SECURITY_TRUST_PROXY_HEADERS=yes
```

## Application Throttling UI

Open:

```text
System / Logs -> Security Throttling
```

The screen lets SuperAdmin/Admin users:

- Enable or disable app throttling.
- Set request limits for guest, logged-in, login, API, and payment callback routes.
- Set block duration.
- Review throttled/blocked events.
- Block or whitelist IPs.
- Add CIDR, route, and user-agent rules.
- Clean old event logs.

Recommended starting limits:

- Guest: `120` per `60` seconds
- Logged-in: `240` per `60` seconds
- Login/Forgot/Register: `15` per `60` seconds
- API: `180` per `60` seconds
- Payment/Callback: `300` per `60` seconds
- Block minutes: `15`

Tune after watching real traffic.

## AI Crawler Restriction

FASTNETPAY includes:

- `robots.txt` with `Disallow: /`
- `.well-known/ai.txt`
- `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noai, noimageai`
- Nginx user-agent blocks for common AI crawlers
- PHP user-agent blocks for common AI crawlers

Important limitation: no website can force every AI or scraper to comply. Dishonest clients can spoof a normal browser. Keep the admin panel authenticated, use Cloudflare/WAF, and monitor the Security Throttling screen.

## Backups

Create an encrypted daily database backup:

```bash
docker exec fastnetpay_db mysqldump -u"$DB_USER" -p"$DB_PASSWORD" fast_pay_net | gzip > fastnetpay_$(date +%F).sql.gz
```

Also back up:

- `.env.production`
- `system/uploads`
- Docker volume `fastnetpay_prod_db_data`
- MikroTik exports and backups

Test restore before going live.

## Rollback

Before major upgrades:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml down
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

Keep the previous Git commit and database backup available.
