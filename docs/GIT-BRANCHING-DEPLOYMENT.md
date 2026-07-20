# FASTNETPAY Git Branching And Deployment

## Branch Model

- `feature/*`: normal development work.
- `develop`: integrated development branch.
- `staging`: deployable staging branch.
- `main`: production branch.
- `hotfix/*`: urgent production fixes.

## Flow

1. Create a feature branch from `develop`.
2. Open a pull request into `develop`.
3. CI runs PHP syntax checks, Composer validation, Docker Compose validation and image build.
4. Merge `develop` into `staging`.
5. Staging deploys automatically.
6. Test tenant login, payments, callbacks, routers, VPN, cron and dashboards.
7. Open a pull request from `staging` to `main`.
8. Require GitHub production environment approval.
9. Merge to `main`.
10. Production deploy workflow runs and triggers Portainer if the webhook secret is configured.

## Branch Protection

Configure GitHub protection manually for `main` and `staging`:

- Require pull requests.
- Require passing checks.
- Block force pushes.
- Require linear history where practical.
- Require deployment approval for the production environment.
- Restrict production deployment to `main`.

## Workflows Added

- `.github/workflows/ci.yml`
- `.github/workflows/staging-deploy.yml`
- `.github/workflows/production-deploy.yml`

The workflows do not contain secrets. Configure these GitHub secrets:

- `STAGING_PORTAINER_WEBHOOK_URL`
- `STAGING_HEALTH_URL`
- `PRODUCTION_PORTAINER_WEBHOOK_URL`
- `PRODUCTION_HEALTH_URL`

Prefer immutable images:

- `fastnetpay:{commit-sha}`
- `fastnetpay:staging-{commit-sha}`
- `fastnetpay:prod-{commit-sha}`
- `fastnetpay:vX.Y.Z`

Avoid relying on `latest` as a deployment target.
