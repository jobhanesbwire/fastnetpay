# Local Tenant Testing

FASTNETPAY can test SaaS tenants locally without production DNS.

## Supported Local URLs

- Main system: `http://localhost:8088`
- Tenant by query string: `http://localhost:8088/?tenant=sega`
- Tenant by local subdomain: `http://sega.localhost:8088`
- Another tenant example: `http://isp1.localhost:8088`

The query string accepts tenant `slug` or `subdomain`.

## Environment Flags

Use `.env` values copied from `.env.example`:

```env
APP_ENV=local
APP_BASE_DOMAIN=fastnetpay.co.ke
APP_LOCAL_DOMAIN=localhost
TENANT_LOCAL_TESTING=true
```

In production, use your real wildcard/custom domain and disable local-only shortcuts:

```env
APP_ENV=production
TENANT_LOCAL_TESTING=false
```

## Notes

- `localhost` always resolves to the main FASTNETPAY tenant.
- `sega.localhost` resolves the tenant whose slug or subdomain is `sega`.
- `?tenant=sega` is accepted on local hosts for quick testing.
- Existing production domains still work through `tenant_domains` and `custom_domain`.
