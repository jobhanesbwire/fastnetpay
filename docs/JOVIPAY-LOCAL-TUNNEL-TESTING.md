# Jovi-Pay Local Tunnel Testing

FASTNETPAY can run locally at `http://localhost:8088`, but Safaricom/Jovi-Pay callbacks need a public HTTPS URL. Use a temporary tunnel while testing.

## Cloudflare Tunnel

Install `cloudflared`, then run:

```bash
cloudflared tunnel --url http://localhost:8088
```

Copy the generated HTTPS URL and append the FASTNETPAY callback route:

```text
https://abc.trycloudflare.com/?_route=api/jovipay/callback
```

Save it in:

```text
Payments -> Jovi-Pay Integration -> Local Tunnel URL
```

Set callback mode to `Local tunnel testing`.

## Ngrok

```bash
ngrok http 8088
```

Use:

```text
https://your-ngrok-domain.ngrok-free.app/?_route=api/jovipay/callback
```

## Jovi-Pay Prefix Forwarding

In Jovi-Pay, register:

```text
Prefix: WIFI_
Callback URL: https://your-tunnel.example/?_route=api/jovipay/callback
```

If you configured a callback secret in FASTNETPAY, configure the same secret in Jovi-Pay and send either:

```text
X-JoviPay-Signature: sha256=<hmac_sha256_raw_json>
```

or:

```text
X-JoviPay-Secret: <shared_secret>
```

## Local Test Checklist

1. Start FASTNETPAY Docker on `http://localhost:8088`.
2. Start the tunnel.
3. Save the tunnel callback in FASTNETPAY.
4. Register the `WIFI_` prefix in Jovi-Pay.
5. Connect to the MikroTik SSID.
6. Select a package on the captive portal.
7. Enter a Safaricom test/real phone number.
8. Confirm the STK prompt.
9. Watch `Payments -> Jovi-Pay Transactions`.
10. Confirm the hotspot user is logged in after callback activation.

## Common Errors

- Tunnel shows 404: confirm the URL includes `/?_route=api/jovipay/callback`.
- Callback timeout: confirm Docker app is reachable on port `8088`.
- Signature rejected: confirm the callback secret exactly matches.
- Payment stays pending: confirm Jovi-Pay is forwarding the callback for the `WIFI_` prefix.
- User paid but not connected: use `Already Paid?` on the captive portal with the M-Pesa receipt code.

## Moving To VPS

When FASTNETPAY runs on a VPS, stop using the tunnel and set:

```text
Callback mode: Production VPS HTTPS
Production Callback URL: https://your-domain/?_route=api/jovipay/callback
```

Then update the Jovi-Pay prefix callback URL to the VPS URL.
