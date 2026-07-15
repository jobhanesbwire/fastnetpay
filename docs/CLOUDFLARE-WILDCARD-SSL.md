# Cloudflare Wildcard SSL

FASTNETPAY production uses a DNS-01 Let's Encrypt wildcard certificate for:

- `fastnetpay.co.ke`
- `*.fastnetpay.co.ke`

Credential path on the VPS:

```text
/root/.secrets/cloudflare.ini
```

The file must stay root-owned with mode `600`. Do not commit, print, or copy the token into app logs, Portainer stack files, or off-server backups.

Production storage status:

- Directory: `/root/.secrets`
- Directory mode: `700`
- Credential file: `/root/.secrets/cloudflare.ini`
- File mode: `600`
- Owner/group: `root:root`
- Token validation: Cloudflare API token is active for the `fastnetpay.co.ke` zone.
- Token limitation found: the current token can manage DNS, but API attempts to update zone SSL mode and rulesets returned permission errors. Use the Cloudflare dashboard or issue a second restricted token with ruleset/zone-setting permissions for those actions.

Certificate paths:

```text
/etc/letsencrypt/live/fastnetpay.co.ke/fullchain.pem
/etc/letsencrypt/live/fastnetpay.co.ke/privkey.pem
```

FASTNETPAY's Docker Nginx reads copied certificates from:

```text
/opt/fastnetpay/app/.docker/nginx/certs/fullchain.pem
/opt/fastnetpay/app/.docker/nginx/certs/privkey.pem
```

The deploy hook `/etc/letsencrypt/renewal-hooks/deploy/fastnetpay-nginx.sh` copies renewed certificates into the Docker-mounted cert path and reloads `fastnetpay_nginx`.

Issued certificate status:

- Issuer: Let's Encrypt
- SANs: `fastnetpay.co.ke`, `*.fastnetpay.co.ke`
- Validation method: Cloudflare DNS-01
- Certbot plugin: `dns-cloudflare`

Rollback:

1. Restore the previous certs from `/opt/fastnetpay/app/.docker/nginx/certs/rollback/`.
2. Run `docker exec fastnetpay_nginx nginx -t`.
3. Reload with `docker exec fastnetpay_nginx nginx -s reload`.

Cloudflare SSL/TLS mode should be `Full (strict)` after the origin certificate is active.
