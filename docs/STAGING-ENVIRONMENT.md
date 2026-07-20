# FASTNETPAY Staging Environment

## Purpose

Staging is where production-like changes are verified without touching live tenants, live routers or live payments.

## Setup

1. Provision a staging VPS or an isolated stack on the current VPS.
2. Copy `.env.staging.example` to `.env.staging`.
3. Fill in staging-only secrets.
4. Use staging-only payment and SMS credentials.
5. Point `staging.fastnetpay.co.ke` to the staging origin.

Deploy with:

```bash
FASTNETPAY_ENV_FILE=.env.staging docker compose -f docker-compose.prod.yml -f compose.staging.yml up -d --build
```

## Safety

Staging must not:

- Send live STK prompts.
- Send production SMS.
- Reach production routers.
- Use production VPN private keys.
- Use production callback secrets.
- Share sessions with production.

## Testing Checklist

- SuperAdmin login.
- Tenant login and tenant isolation.
- Dashboard route speed.
- Clients page.
- Router sync using a test router.
- MPESA/Jovi-Pay sandbox callback.
- Cron/scheduler.
- Redis sessions if enabling multiple app containers.
- Backup and restore rehearsal.
