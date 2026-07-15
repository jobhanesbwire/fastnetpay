# Certificate Renewal

Certbot timer:

```bash
systemctl status certbot.timer
systemctl list-timers | grep certbot
```

Dry run:

```bash
sudo certbot renew --dry-run
```

Deploy hook:

```text
/etc/letsencrypt/renewal-hooks/deploy/fastnetpay-nginx.sh
```

The hook copies renewed certs to:

```text
/opt/fastnetpay/app/.docker/nginx/certs/
```

Then it runs:

```bash
docker exec fastnetpay_nginx nginx -t
docker exec fastnetpay_nginx nginx -s reload
```

Rollback: use the saved rollback certs in `/opt/fastnetpay/app/.docker/nginx/certs/rollback/` or restore from the latest `/opt/fastnetpay/backups/prod-enhancement-*` checkpoint.

Production verification:

- `certbot renew --dry-run` completed successfully.
- `certbot.timer` is enabled and active.
- The deploy hook validates Docker Nginx before reload.

Manual renewal test:

```bash
sudo certbot renew --dry-run
```

Useful logs:

```bash
sudo journalctl -u certbot.timer
sudo journalctl -u certbot.service
sudo tail -n 200 /var/log/letsencrypt/letsencrypt.log
```
