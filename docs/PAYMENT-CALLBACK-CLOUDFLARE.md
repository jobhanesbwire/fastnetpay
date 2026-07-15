# Payment Callback Cloudflare Rules

Payment and captive portal API endpoints are HTTPS traffic and can remain Cloudflare-proxied.

Recommended callback hosts:

- `api.fastnetpay.co.ke`
- `callback.fastnetpay.co.ke`
- `pay.fastnetpay.co.ke`

Nginx proxies only callback/API routes on those hosts and returns 404 for ordinary admin/browser pages.

Bypass cache for:

```text
/api/mpesa/*
/api/jovipay/*
/api/saas/mpesa/*
/api/payments/*
/api/hotspot/pay
/api/hotspot/payment-status
/api/hotspot/reconnect
/callback/*
/confirmation/*
/validation/*
```

Cloudflare rules:

- bypass cache
- do not cache POST responses
- preserve JSON bodies and headers
- do not apply JavaScript/CAPTCHA challenges to Safaricom/Jovi-Pay callbacks
- keep request-rate limits sensible rather than aggressive
- avoid transformations that alter callback bodies

FASTNETPAY requires Jovi-Pay signature validation, payment idempotency, duplicate receipt protection, amount validation, account-prefix validation, and callback processing independent of browser sessions.

Current production Nginx behavior:

- `api.fastnetpay.co.ke`, `callback.fastnetpay.co.ke`, and `pay.fastnetpay.co.ke` pass only approved API/callback/payment routes to FASTNETPAY.
- Admin routes return Nginx `404` on these hostnames.
- Responses include `Cache-Control: no-store` and `Pragma: no-cache`.
- `wg.fastnetpay.co.ke` and `sstp.fastnetpay.co.ke` are reserved static pages for HTTP and must not be used for app/admin traffic.

Callback route examples:

```text
https://callback.fastnetpay.co.ke/?_route=api/jovipay/callback
https://api.fastnetpay.co.ke/?_route=api/hotspot/packages
https://api.fastnetpay.co.ke/?_route=api/hotspot/pay
https://api.fastnetpay.co.ke/?_route=api/hotspot/payment-status
https://mother.fastnetpay.co.ke/?_route=callback/mpesastkpush
```

Cloudflare API note:

The current Cloudflare token validated for DNS, but did not have enough permission to create Cache Rules or change SSL mode through the API. Apply the bypass/cache/challenge rules manually in Cloudflare, or create a separate limited token with the required ruleset permissions.
