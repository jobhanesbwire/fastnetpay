# FASTNETPAY Security Hardening

FASTNETPAY should be protected in layers: upstream network, reverse proxy, Docker host, PHP application, payment callbacks, database, RADIUS, MikroTik, and future tenant isolation.

This document is based on the current project structure and Docker setup. It is not a replacement for a security audit, but it gives the production baseline for an ISP-grade deployment.

## Current Security Posture

Observed local development posture:

- `docker-compose.yml` exposes FASTNETPAY on `8088`, phpMyAdmin on `8089`, and MySQL on `3309`.
- Local DB credentials use root user `root` and password `root1234`.
- `config.php` supports environment variables, but development defaults are still present.
- PHP display errors are enabled for local development through the Docker PHP config and compose environment.
- Apache modules `rewrite` and `headers` are enabled.
- Router credentials are stored through the existing PHPNuxBill router records.
- M-Pesa STK Push has idempotent callback handling, phone validation, and masked settings, but callback source validation still depends on HTTPS, transaction matching, and perimeter controls.
- TALKSASA token is masked in admin UI and not logged by the plugin.
- CSRF support exists in `system/autoload/Csrf.php`, but enforcement depends on `csrf_enabled`.
- Admin and customer login record failed attempts, but there is no strong built-in brute-force throttling layer.
- Plugin Manager extracts uploaded ZIP files into application paths; restrict this feature to trusted super admins only.
- RADIUS disconnect uses `radclient`, but production packaging must ensure the binary exists.

Production must not use the local development exposure model.

## Recommended Production Model

```text
Internet
  -> Upstream DDoS filtering / ISP edge
  -> Reverse proxy with HTTPS, rate limits, WAF rules
  -> FASTNETPAY app container on private Docker network
  -> MySQL private network only
  -> FreeRADIUS private network/VPN to MikroTik routers
  -> MikroTik routers through VPN or private routed links
```

Minimum production changes:

- Put FASTNETPAY behind HTTPS.
- Set `APP_STAGE=Live`.
- Set `APP_DISPLAY_ERRORS=0`.
- Remove public phpMyAdmin.
- Remove public MySQL port mapping.
- Use a non-root MySQL application user.
- Restrict admin routes by VPN or trusted IP where possible.
- Restrict MikroTik API to the FASTNETPAY server or management VPN only.
- Enable CSRF protection.
- Add login and payment rate limiting.
- Keep server-side backups and restore tests.

## Docker and Server Hardening

Recommended production compose principles:

- Do not publish MySQL to the internet.
- Do not publish phpMyAdmin in production. If absolutely required, bind it to `127.0.0.1`, place it behind VPN, and add HTTP auth.
- Use a private Docker network for app-to-database traffic.
- Use a reverse proxy container or host-level Nginx/Caddy/Traefik for HTTPS.
- Store secrets in `.env`, Docker secrets, or a secret manager.
- Use `docker-compose.prod.yml` for production so MySQL is private, phpMyAdmin is not exposed, Nginx rate limits public traffic, and PHP runs with display errors disabled.
- Use `System / Logs -> Security Throttling` to tune application-level request limits and review blocked attempts.
- Do not commit `.env`, `config.php` with real credentials, database dumps, or router exports.
- Run regular image updates and vulnerability scans.
- Keep mounted writable paths narrow.

Example production direction:

```yaml
services:
  fastnetpay_app:
    expose:
      - "80"
    environment:
      APP_STAGE: Live
      APP_DISPLAY_ERRORS: "0"
      DB_USER: fastnetpay_app
      DB_PASSWORD: "${FASTNETPAY_DB_PASSWORD}"

  fastnetpay_db:
    ports: []
    environment:
      MYSQL_DATABASE: fast_pay_net
      MYSQL_USER: fastnetpay_app
      MYSQL_PASSWORD: "${FASTNETPAY_DB_PASSWORD}"
      MYSQL_ROOT_PASSWORD: "${MYSQL_ROOT_PASSWORD}"
```

For local admin access to MySQL, use SSH tunneling or `docker exec`, not a public port.

## Reverse Proxy and Rate Limiting

Use a reverse proxy in front of Apache. This is where most Layer 7 protection should live.

Example Nginx concepts:

```nginx
limit_req_zone $binary_remote_addr zone=fnp_general:20m rate=20r/s;
limit_req_zone $binary_remote_addr zone=fnp_auth:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=fnp_payment:10m rate=10r/m;
limit_conn_zone $binary_remote_addr zone=fnp_conn:10m;

server {
    listen 443 ssl http2;
    server_name fastnetpay.example.com;

    client_max_body_size 64m;
    limit_conn fnp_conn 40;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;

    location / {
        limit_req zone=fnp_general burst=40 nodelay;
        proxy_pass http://fastnetpay_app;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }

    location ~* "(_route=login|_route=admin|_route=forgot|_route=register)" {
        limit_req zone=fnp_auth burst=10 nodelay;
        proxy_pass http://fastnetpay_app;
    }

    location ~* "(_route=callback/mpesastkpush|callback/mpesastkpush)" {
        limit_req zone=fnp_payment burst=20 nodelay;
        proxy_pass http://fastnetpay_app;
    }
}
```

Tune limits to real traffic. Do not block Safaricom callbacks accidentally. Start in logging mode if using a WAF.

## DDoS and DoS Protection

Layer 3/4 attacks:

- Use an upstream provider or cloud edge with DDoS filtering.
- Keep FASTNETPAY behind a reverse proxy and firewall.
- Do not expose Docker service ports directly.
- Use router/firewall connection limits at the network edge.
- Avoid hosting high-volume customer transit traffic on the same server that runs billing.

Layer 7 attacks:

- Rate limit login, registration, forgot password, payment start, callback, and plugin upload routes.
- Add request body size limits.
- Use fail2ban against reverse proxy logs.
- Add bot/scanner deny rules for obvious probes such as `.env`, `wp-login.php`, `phpmyadmin`, and random PHP shells.
- Add app-level throttling for login and STK Push attempts by IP, username, customer ID, and phone number.

Captive portal abuse:

- Keep the portal lightweight.
- Serve local assets instead of public CDNs.
- Walled garden only the FASTNETPAY domain and necessary support links.
- Rate limit package order and payment initiation.

## Admin Panel Protection

Recommended controls:

- Enable CSRF.
- Enable session timeout and single-session options where available.
- Add account lockout or progressive delay after repeated failed logins.
- Use strong passwords and unique admin accounts.
- Restrict `/admin` access by VPN or trusted IP range.
- Add 2FA readiness for SuperAdmin accounts.
- Keep audit logs for settings changes, payment gateway changes, plugin uploads, router changes, and admin logins.
- Disable or restrict Plugin Manager in production except during maintenance windows.
- Validate uploaded plugin ZIP files before extraction and keep backups before installing plugins.

## Payment Security

M-Pesa STK Push protection:

- Use HTTPS callback URLs only.
- Store Consumer Secret and Passkey server-side only.
- Never log access tokens, passkeys, or consumer secrets.
- Match callbacks by `CheckoutRequestID` and expected transaction state.
- Validate amount, phone, merchant request ID, and checkout request ID before activation.
- Keep idempotency so duplicate callbacks do not double-credit.
- Consider a final STK query before activation for high-value plans.
- Rate limit STK Push initiation by phone, customer, IP, and plan.
- Keep an immutable payment audit trail containing safe metadata only.

Fraud and replay controls:

- Expire pending STK Push requests.
- Reject callbacks for unknown, expired, or already failed transactions.
- Alert on repeated failed STK attempts from the same phone or IP.
- Alert on callback/result mismatches.

## SMS Gateway Security

TALKSASA protection:

- Store API token server-side only.
- Do not expose token in templates or JavaScript.
- Rate limit SMS send actions.
- Log only safe error messages.
- Validate endpoint URL and keep it HTTPS.
- Monitor failed sends and sudden message spikes.

## Database Security

MySQL production baseline:

- No public MySQL port.
- Non-root app user with only required privileges.
- Separate backup user with limited privileges.
- Strong root password stored outside git.
- Daily logical backups and regular full volume snapshots.
- Restore tests on a separate host.
- Slow query logging for performance monitoring.
- Regular SQL injection review for custom plugins and payment gateways.
- Encrypt backups before offsite storage.

## Router and API Credential Security

- Use one MikroTik API account per router or site.
- Restrict API by source IP or VPN subnet.
- Prefer API-SSL after FASTNETPAY support is verified.
- Do not reuse router passwords.
- Rotate router API passwords after staff changes.
- Store router credentials in a protected database and plan future encryption at rest.
- Use WireGuard/IPsec for remote routers.

## RADIUS Security

- Use unique RADIUS secrets per router/site.
- Restrict UDP `1812`, `1813`, and `3799` to router and RADIUS server IPs only.
- Keep FreeRADIUS logs protected because they may contain usernames and session metadata.
- Use interim accounting intervals that are useful but not noisy, for example `5m`.
- Monitor RADIUS rejects and timeouts.
- Use Disconnect-Message carefully; protect the source with firewall rules.

## Logging and Monitoring

Log and alert on:

- Failed admin logins.
- Failed customer logins.
- Password reset attempts.
- Plugin uploads.
- Payment gateway configuration changes.
- M-Pesa STK failures and callbacks.
- Duplicate callback attempts.
- Router API failures.
- RADIUS rejects/timeouts.
- Voucher abuse.
- SMS spikes and failures.
- High 4xx/5xx rates.
- Reverse proxy rate-limit triggers.
- Disk usage and backup failures.

Recommended stack:

- Reverse proxy access/error logs.
- Application logs.
- MySQL slow query log.
- FreeRADIUS logs.
- MikroTik syslog to a central collector.
- Uptime checks for FASTNETPAY, MySQL, FreeRADIUS, and each router.
- Alerting through email, SMS, or chat.

## Backup and Disaster Recovery

Back up:

- MySQL database.
- `.env` and production config.
- Uploaded assets.
- Custom plugins and payment gateways.
- Custom themes.
- FreeRADIUS configuration.
- MikroTik `/export hide-sensitive` and encrypted binary backups.
- Reverse proxy configuration.
- SSL certificate renewal configuration.

Recovery requirements:

- Test restore monthly.
- Keep at least one offsite encrypted backup.
- Document how to restore M-Pesa pending transactions.
- Document how to recreate RADIUS clients and MikroTik router records.
- Keep rollback instructions for plugin/theme updates.

## SaaS Security Preparation

Future SaaS requirements:

- Add tenant isolation to routers, customers, plans, transactions, payment settings, SMS settings, vouchers, staff users, and audit logs.
- Use per-tenant M-Pesa credentials.
- Use per-tenant SMS credentials.
- Use per-tenant router/API credentials.
- Add tenant-aware RBAC.
- Add tenant-level audit logs.
- Add tenant-level rate limits.
- Add per-tenant API keys for integrations.
- Prevent staff from crossing tenant boundaries at the query layer.
- Plan for tenant data export and deletion.

## Production Validation Checklist

- FASTNETPAY public URL uses HTTPS.
- `APP_STAGE=Live`.
- `APP_DISPLAY_ERRORS=0`.
- MySQL is not publicly reachable.
- phpMyAdmin is removed or VPN-only.
- Admin routes are protected by strong auth and preferably IP/VPN restriction.
- CSRF is enabled.
- Login and payment routes are rate limited.
- Plugin Manager is restricted.
- M-Pesa callback is HTTPS and idempotent.
- MikroTik API is restricted by IP/VPN.
- RADIUS ports are restricted to routers.
- Backups restore successfully.
- Logs and alerts are working.

## Reference Links

- MikroTik RouterOS DDoS protection: https://help.mikrotik.com/docs/spaces/ROS/pages/28606504/DDoS%2BProtection
- MikroTik RouterOS firewall: https://help.mikrotik.com/docs/spaces/ROS/pages/250708066/Firewall
- MikroTik RouterOS API: https://help.mikrotik.com/docs/spaces/ROS/pages/47579160/API
- MikroTik RouterOS RADIUS: https://help.mikrotik.com/docs/spaces/ROS/pages/328097/RADIUS
