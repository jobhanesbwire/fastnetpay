# FASTNETPAY Environment Architecture

FASTNETPAY uses three isolated environments.

## Development

- Local Docker on the developer machine.
- URL: `http://localhost:8088`
- Debug output may be enabled.
- Uses test data only.
- No live MPESA, Jovi-Pay, SMS, VPN or router secrets.
- Template: `.env.development.example`
- Compose: `docker-compose.yml` plus optional `compose.development.yml`

## Staging

- Separate deployed stack for testing.
- Suggested URL: `https://staging.fastnetpay.co.ke`
- Suggested tenant test URL: `https://sega.staging.fastnetpay.co.ke` or `https://staging-sega.fastnetpay.co.ke`
- Uses its own database, Redis, session cookie name and Redis namespace.
- Uses sandbox/test payment credentials only.
- Should be protected by Cloudflare Access, VPN or HTTP authentication.
- Must show a visible staging indicator.
- Template: `.env.staging.example`
- Compose override: `compose.staging.yml`

## Production

- Live customer and ISP tenant traffic.
- URLs: `https://mother.fastnetpay.co.ke` and `https://*.fastnetpay.co.ke`
- Live payments, routers, SMS and VPN credentials.
- Debug disabled.
- Template: `.env.production.example`
- Compose: `docker-compose.prod.yml` plus optional `compose.production.yml`

## Isolation Rules

Never share between staging and production:

- Database
- Redis database/namespace unless strongly isolated
- Payment secrets
- Jovi-Pay/MPESA callback secrets
- VPN private keys
- Router credentials
- Tenant uploads
- Session cookie name

Environment names should appear in logs and deployment metadata so incidents can be traced quickly.
