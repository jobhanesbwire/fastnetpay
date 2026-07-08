# FASTNETPAY Jovi-Pay Integration

FASTNETPAY can use Jovi-Pay as the M-Pesa payment orchestrator for MikroTik captive portal users. This keeps Daraja/STK credentials inside Jovi-Pay while FASTNETPAY focuses on packages, customers, activation, voucher login, PPPoE/hotspot state, and audit logs.

## How It Works

1. A hotspot user opens the FASTNETPAY captive portal.
2. The user selects a package and enters an M-Pesa phone number.
3. FASTNETPAY creates a pending local payment and generates an account reference such as:

   ```text
   WIFI_1_3_AB12CD34EF_1780000000
   ```

4. FASTNETPAY sends the STK Push request using the configured Jovi-Pay STK API endpoint when Jovi-Pay provides one. If that endpoint is missing/unavailable, FASTNETPAY can fall back to the existing direct `MPESA STK Push` gateway credentials while still using the generated `WIFI_...` reference.
5. Jovi-Pay receives the Safaricom C2B confirmation for matching `WIFI_` transactions and forwards it to FASTNETPAY. Direct STK callbacks can also hit the same FASTNETPAY callback endpoint and reconcile by `CheckoutRequestID`.
6. FASTNETPAY validates the callback, marks the transaction paid, and activates the selected internet package using the existing PHPNuxBill package recharge flow.
7. The captive portal polls FASTNETPAY and logs the user into MikroTik when activation succeeds.

If Jovi-Pay is disabled, the existing direct `MPESA STK Push` gateway remains the fallback for captive portal payments.

Important: the Jovi-Pay app at `https://lipa.jovi-tec.com` currently exposes the C2B forwarding/mini-app system. A POST endpoint at `/api/stk-push` was not available during local testing. Keep `Jovi-Pay STK Push Endpoint` set only to a real POST endpoint issued by Jovi-Pay; otherwise configure the normal FASTNETPAY `MPESA STK Push` gateway credentials so FASTNETPAY can initiate STK directly and let Jovi-Pay forward the final C2B confirmation.

## Admin Configuration

Open:

```text
Payments -> Jovi-Pay Integration
http://localhost:8088/?_route=jovipay/settings
```

Configure:

- Enable Jovi-Pay integration
- Jovi-Pay API Base URL
- Jovi-Pay STK Push Endpoint
- API token/secret
- Account prefix, default `WIFI_`
- Callback mode: local tunnel or production
- Callback secret/signature key
- Optional Mini-App ID from Jovi-Pay
- Optional Jovi-Pay allowed IPs
- Gateway label, support phone, WhatsApp link
- Payment timeout and polling interval

Secrets are masked in the admin UI and encrypted before storage when OpenSSL is available.

## Callback URL

PHPNuxBill routes through the `_route` query parameter. Use this callback URL unless you add a reverse proxy rewrite:

```text
https://your-domain.example/?_route=api/jovipay/callback
```

For local Docker testing, use a public HTTPS tunnel:

```text
https://abc.trycloudflare.com/?_route=api/jovipay/callback
```

Register this callback URL in Jovi-Pay for the `WIFI_` prefix.

## Callback Security

FASTNETPAY verifies the current Jovi-Pay security headers:

- `X-Jovi-App-ID`: registered mini-app id
- `X-Jovi-Event-ID`: unique Jovi-Pay event/transaction id
- `X-Jovi-Timestamp`: unix seconds
- `X-Jovi-Signature`: `hash_hmac('sha256', payloadJson . timestamp, callback_secret)`

If `Mini-App ID` is set in FASTNETPAY, `X-Jovi-App-ID` or `mini_app.id` must match it.

For backward compatibility during transition, FASTNETPAY also accepts the older `X-JoviPay-Signature`, `X-Fastnetpay-Signature`, and `X-JoviPay-Secret` styles. Prefer the current `X-Jovi-*` headers in production.

## Accepted Payloads

FASTNETPAY accepts the normalized Jovi-Pay envelope:

```json
{
  "event": "mpesa.c2b.confirmed",
  "event_id": "42",
  "mini_app": {
    "id": 1,
    "app_code": "SMART_RENT",
    "app_name": "Smart Rent"
  },
  "transaction": {
    "id": 42,
    "trans_id": "SFE1A2B3C4",
    "trans_time": "20260604120000",
    "trans_amount": "1500.00",
    "business_short_code": "600000",
    "bill_ref_number": "WIFI_1_3_SESSION_TIME",
    "msisdn": "254708374149"
  },
  "raw_payload": {}
}
```

It also accepts raw Safaricom/Daraja C2B-style fields such as:

```json
{
  "TransID": "SFE1A2B3C4",
  "TransTime": "20260604120000",
  "TransAmount": "1500",
  "BillRefNumber": "WIFI_1_3_SESSION_TIME",
  "MSISDN": "254708374149"
}
```

FASTNETPAY extracts the account reference from `transaction.bill_ref_number`, `BillRefNumber`, `AccountReference`, `account_reference`, or equivalent fields.

## Expected Callback Response

When FASTNETPAY settles or safely acknowledges the event, it returns:

```json
{
  "status": "success",
  "message": "Payment settled successfully",
  "reference": "WIFI_1_3_SESSION_TIME"
}
```

When Jovi-Pay should retry because FASTNETPAY could not settle it, it returns:

```json
{
  "status": "failed",
  "message": "Account not found"
}
```

Production recommendations:

- Use HTTPS only.
- Configure a callback secret.
- Add fixed Jovi-Pay source IPs when available.
- Keep tunnel IP allowlists empty during local tests because tunnel source IPs may rotate.

## Captive Portal Payment

The MikroTik-hosted FASTNETPAY portal now has:

- M-Pesa package purchase tab
- Voucher login tab
- Already paid/reconnect tab

The portal calls only customer-safe endpoints:

```text
/?_route=api/hotspot/packages
/?_route=api/hotspot/pay
/?_route=api/hotspot/payment-status
/?_route=api/hotspot/voucher-login
/?_route=api/hotspot/reconnect
```

No Jovi-Pay token, Daraja secret, or callback secret is exposed in the MikroTik portal files.

## Reconnect With M-Pesa Code

If a user paid but activation did not complete on the phone, they can open:

```text
Already Paid? -> Reconnect Internet
```

FASTNETPAY searches `jovipay_transactions` by M-Pesa receipt number, verifies the package is active, logs the reconnection attempt, and returns the hotspot username/password for automatic MikroTik login.

## Database Tables

The integration creates these tables safely if missing:

- `jovipay_settings`
- `jovipay_transactions`
- `reconnection_attempts`

Jovi-Pay transactions are linked to `tbl_payment_gateway` so existing package activation and reporting logic can remain intact.

## Troubleshooting

- `Jovi-Pay API token is not configured`: Save the token on the settings page.
- `The route api/stk-push could not be found`: Jovi-Pay has not exposed that STK endpoint. Either enter the correct Jovi-Pay POST STK endpoint or configure the normal FASTNETPAY `MPESA STK Push` gateway credentials for direct Daraja initiation.
- `Jovi-Pay STK request timed out`: FASTNETPAY now keeps the transaction pending because the upstream request may still be accepted. The later C2B/STK callback remains the source of truth.
- `Prefix is not registered`: Confirm Jovi-Pay forwards the same prefix configured in FASTNETPAY.
- `Invalid Jovi-Pay callback signature`: Confirm both systems use the same callback secret and HMAC method.
- `Transaction not found yet`: The callback may not have arrived. Wait a few seconds and retry reconnect.
- `Paid amount does not match package price`: Confirm the package price and Jovi-Pay amount are the same.
- `Payment received, but package activation failed`: Check router/package/customer records and `Payments -> Jovi-Pay Transactions`.

## Production VPS Mode

When FASTNETPAY moves to a VPS:

1. Put FASTNETPAY behind HTTPS.
2. Set callback mode to `Production VPS HTTPS`.
3. Set production callback URL to:

   ```text
   https://fastnetpay.co.ke/?_route=api/jovipay/callback
   ```

4. Register that URL in Jovi-Pay.
5. Restrict callbacks by Jovi-Pay source IP if fixed IPs are available.
