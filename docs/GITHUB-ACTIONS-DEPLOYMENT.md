# GitHub Actions Deployment

Workflow template:

```text
docs/production-image.workflow.yml
```

The workflow:

- Runs on `main`, tags, and manual dispatch.
- Builds `Dockerfile.prod`.
- Pushes `ghcr.io/<owner>/fastnetpay:<sha>` and `ghcr.io/<owner>/fastnetpay:main`.
- Optionally calls `PORTAINER_WEBHOOK_URL`.

To enable it, copy the template to `.github/workflows/production-image.yml` from an account/token that has GitHub `workflow` permission. Some OAuth credentials cannot create workflow files.

Manual release:

```bash
git tag -a v1.0.0-production -m "FASTNETPAY production baseline"
git push origin main
git push origin v1.0.0-production
```

Rollback:

Use Portainer to redeploy the previous image tag, or run the rollback commands in `docs/PRODUCTION-ROLLBACK.md`.
