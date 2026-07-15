# Production Release Checklist

Before release:

- Confirm `docker compose config` passes.
- Confirm PHP lint passes on changed PHP files.
- Confirm database backup exists.
- Confirm `.docker/nginx/certs/fullchain.pem` and `.docker/nginx/certs/privkey.pem` exist before starting HTTPS.
- Confirm `.env.production` is not in Git.
- Confirm root/www show "You are lost".
- Confirm `mother.fastnetpay.co.ke` opens SuperAdmin.
- Confirm unknown tenants show `ISP Portal Not Found`.
- Confirm MySQL is not publicly exposed.
- Confirm Portainer is only reachable by SSH tunnel or VPN.
- Confirm WireGuard UDP 51820 is open and `vpn.fastnetpay.co.ke` is DNS-only.
- Confirm Cloudflare wildcard SSL token is stored outside Git before issuing certificates.

After release:

- Watch `docker logs fastnetpay_app`.
- Watch `docker logs fastnetpay_nginx`.
- Check throttle/security logs in the admin security panel.
- Test tenant login on at least one tenant subdomain.
