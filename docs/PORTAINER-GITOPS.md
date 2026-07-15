# Portainer GitOps

Recommended deployment flow:

1. Push verified code to `main`.
2. GitHub Actions builds `Dockerfile.prod`.
3. GitHub Actions pushes the image to GHCR.
4. Portainer receives a webhook and recreates the app stack.

GitHub secrets:

- `PORTAINER_WEBHOOK_URL`: optional webhook URL from Portainer.

Production stack still reads runtime secrets from `.env.production` on the VPS. Do not move database passwords, app secrets, M-Pesa secrets, SMS tokens, or router credentials into GitHub Actions.
