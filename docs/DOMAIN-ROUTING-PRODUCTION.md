# FASTNETPAY Production Domain Routing

Production uses the domain only for public entry points:

- `fastnetpay.co.ke` and `www.fastnetpay.co.ke`: static "You are lost" page.
- `mother.fastnetpay.co.ke`: SuperAdmin and mother system.
- `*.fastnetpay.co.ke`: tenant ISP portals.
- Reserved names never become tenant slugs: `mother`, `www`, `api`, `vpn`, `portainer`, `admin`, `mail`, `smtp`, `ftp`, `docs`, `support`, `status`, `monitor`, `callback`, `assets`, `static`.

Unknown tenant portals return the branded `ISP Portal Not Found` page instead of falling back to the mother tenant.

Required production env values:

```env
APP_URL=https://mother.fastnetpay.co.ke
APP_BASE_DOMAIN=fastnetpay.co.ke
SUPERADMIN_HOST=mother.fastnetpay.co.ke
TENANT_WILDCARD_DOMAIN=*.fastnetpay.co.ke
```

Validation:

```bash
curl -H 'Host: fastnetpay.co.ke' http://127.0.0.1/
curl -H 'Host: mother.fastnetpay.co.ke' http://127.0.0.1/?_route=login
curl -H 'Host: unknown.fastnetpay.co.ke' http://127.0.0.1/
```
