# Tenant Domain Setup

Main domain:

```text
fastnetpay.co.ke
```

Tenant examples:

```text
isp1.fastnetpay.co.ke
isp2.fastnetpay.co.ke
```

DNS:

```text
fastnetpay.co.ke      A      VPS_PUBLIC_IP
*.fastnetpay.co.ke    A      VPS_PUBLIC_IP
```

Nginx/Apache should accept the main domain and wildcard subdomains on the same FASTNETPAY app. Use a wildcard TLS certificate for `*.fastnetpay.co.ke`, plus the root certificate for `fastnetpay.co.ke`.

Custom domains:

1. Add the custom domain on the tenant record.
2. Ask the ISP to point a CNAME or A record to the FASTNETPAY server.
3. Issue a per-domain SSL certificate.
4. Mark the domain active in `tenant_domains` after validation.

Never use tenant subdomain alone as the only security control. FASTNETPAY also stores `tenant_id` on tenant-owned records and should enforce tenant scope in queries.
