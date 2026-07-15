# Portainer Operations

Portainer CE is intended for private production operations only.

Rules:

- Pin the image version, for example `portainer/portainer-ce:2.43.0`.
- Do not expose port `9000` publicly.
- Bind Portainer to `127.0.0.1:9443` or a VPN-only address.
- Access it with an SSH tunnel:

```bash
ssh -L 9443:127.0.0.1:9443 fastnetpay@212.95.35.229
```

Then open:

```text
https://127.0.0.1:9443
```

Keep the Portainer admin password in the server secrets folder, never in Git.
