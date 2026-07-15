# Production Secrets

Never commit:

- `.env.production`
- `config.php`
- Cloudflare API token
- Portainer admin password
- WireGuard private keys
- Database passwords
- Router API credentials
- M-Pesa, Jovi-Pay, SMS, and payment credentials

Recommended server-only paths:

```text
/opt/fastnetpay/app/.env.production
/opt/fastnetpay/secrets/
/root/.secrets/cloudflare.ini
/etc/wireguard/
```

Permissions:

```bash
sudo chmod 600 /opt/fastnetpay/app/.env.production
sudo chmod 700 /opt/fastnetpay/secrets
sudo chmod 600 /root/.secrets/cloudflare.ini
```
