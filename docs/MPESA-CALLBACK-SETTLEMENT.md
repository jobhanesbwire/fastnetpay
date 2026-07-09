# M-Pesa Callback Settlement

FASTNETPAY accepts SaaS payment callbacks at:

```text
/?_route=api/saas/mpesa/callback
```

The endpoint accepts normalized Jovi-Pay envelopes and direct Daraja-style C2B fields.

## Jovi-Pay Signature

If a callback secret is configured, FASTNETPAY verifies:

```text
hash_hmac('sha256', payloadJson + timestamp, secret)
```

using these headers:

- `X-Jovi-Timestamp`
- `X-Jovi-Signature`
- optional `X-Jovi-App-ID`

Invalid signatures are rejected and logged as unmatched/rejected metadata without exposing secrets.

## Expected Response

Successful settlement returns:

```json
{
  "status": "success",
  "message": "Payment settled successfully and tenant access restored if needed.",
  "reference": "FASTNETPAY_SEGA"
}
```

Unmatched or invalid payments return `status: failed` with a safe reason. Jovi-Pay may retry failures depending on its configuration.

## Duplicate Safety

`saas_invoice_payments.transaction_code` is unique. Duplicate callbacks are acknowledged without double-crediting an invoice.
