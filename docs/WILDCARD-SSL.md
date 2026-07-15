# Wildcard SSL

FASTNETPAY needs a wildcard certificate for:

- `mother.fastnetpay.co.ke`
- `*.fastnetpay.co.ke`

Use Certbot DNS-01 through Cloudflare:

```bash
sudo apt-get update
sudo apt-get install -y certbot python3-certbot-dns-cloudflare
sudo install -d -m 700 /root/.secrets
sudo nano /root/.secrets/cloudflare.ini
sudo chmod 600 /root/.secrets/cloudflare.ini
```

`/root/.secrets/cloudflare.ini`:

```ini
dns_cloudflare_api_token = paste_cloudflare_dns_edit_token_here
```

Issue:

```bash
sudo certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials /root/.secrets/cloudflare.ini \
  -d fastnetpay.co.ke \
  -d '*.fastnetpay.co.ke'
```

The Cloudflare token must have DNS edit permission for the FASTNETPAY zone. Do not store it in Git.

Temporary origin TLS fallback:

If Cloudflare is already set to `Full` and the wildcard certificate is not ready yet, create a temporary self-signed origin certificate so Nginx can listen on `443`:

```bash
cd /opt/fastnetpay/app
sudo install -d -m 700 .docker/nginx/certs
sudo openssl req -x509 -nodes -newkey rsa:2048 -days 14 \
  -subj "/CN=fastnetpay.co.ke" \
  -keyout .docker/nginx/certs/privkey.pem \
  -out .docker/nginx/certs/fullchain.pem
sudo chmod 600 .docker/nginx/certs/privkey.pem
sudo chmod 644 .docker/nginx/certs/fullchain.pem
docker compose --env-file .env.production -f docker-compose.prod.yml up -d
```

Replace the temporary certificate with the Certbot wildcard certificate as soon as the Cloudflare token is available.
