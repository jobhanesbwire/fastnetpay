# FASTNETPAY M-Pesa STK Push Gateway

This document describes the installable `mpesa-stk-push` payment gateway plugin for FASTNETPAY/PHPNuxBill.

## Files

- `mpesa-stk-push/paymentgateway/mpesastkpush.php` contains the PHPNuxBill gateway functions.
- `mpesa-stk-push/paymentgateway/ui/mpesastkpush.tpl` contains the admin configuration UI.
- `mpesa-stk-push/install.txt` contains upload/install notes.
- `mpesa-stk-push/README.md` contains packaging and operator instructions.

## Packaging

```bash
cd mpesa-stk-push
zip -r ../mpesa-stk-push.zip paymentgateway changelog.txt install.txt license.txt README.md
```

Upload the ZIP through PHPNuxBill Plugin Manager. The uploaded ZIP must have `paymentgateway/` at the ZIP root.

## Configuration

After upload:

1. Open `Payment Gateway`.
2. Open `M-Pesa STK Push` (`mpesastkpush`).
3. Save Daraja credentials and payment page branding.
4. Return to `Payment Gateway`.
5. Tick `M-Pesa STK Push` (`mpesastkpush`) as an active gateway.

Required settings:

- Environment: sandbox or live
- Shortcode type: Paybill or Till
- Shortcode / Till number
- Consumer Key
- Consumer Secret
- Online Passkey
- Callback URL
- MikroTik walled garden domains for Safaricom/Daraja access

Production notes:

- Use HTTPS for callbacks.
- Use a non-root database user.
- Change default admin credentials immediately.
- Keep Daraja secrets out of frontend source, logs, tickets, and screenshots.

## Callback URL

Default route:

```text
https://your-domain.example/index.php?_route=callback/mpesastkpush
```

If canonical URLs are enabled in PHPNuxBill, the route may be:

```text
https://your-domain.example/callback/mpesastkpush
```

Safaricom must be able to reach this URL from the public internet.

## Transaction Storage

The plugin uses the existing `tbl_payment_gateway` table:

- `gateway_trx_id`: Daraja CheckoutRequestID
- `pg_url_payment`: tokenized customer payment page URL
- `pg_request`: safe request metadata, token hash, phone, checkout ID, status notes
- `pg_paid_response`: safe callback/status metadata
- `status`: `1` pending, `2` paid, `3` failed, `4` cancelled
- `trx_invoice`: invoice produced by `Package::rechargeUser()`

No extra database table is required for current PHPNuxBill builds. Older installations must have the above columns before enabling the gateway.

## Captive Portal and MikroTik Flow

FASTNETPAY/PHPNuxBill remains responsible for package activation. The plugin does not write MikroTik router files directly.

Recommended hotspot flow:

1. MikroTik captive portal sends unpaid users to the FASTNETPAY customer package page.
2. Customer chooses a package and pays with M-Pesa STK Push.
3. Safaricom callback confirms payment.
4. FASTNETPAY calls `Package::rechargeUser()` to activate the selected PPPoE/Hotspot package.
5. Customer can log in or continue access according to the existing PHPNuxBill/MikroTik integration.

Example redirect target:

```text
https://your-domain.example/index.php?_route=order/package
```

## Failure Handling

- Invalid phone numbers are rejected before STK Push.
- Failed, cancelled, timed-out, or rejected STK responses mark the transaction failed.
- Failed transactions do not activate packages.
- Successful callbacks are idempotent; duplicate callbacks do not activate a package twice.
- If payment is confirmed but activation fails, the payment metadata is retained and the transaction remains reviewable in PHPNuxBill logs/audit.

## FASTNETPAY Colors

- Primary: `#41a146`
- Secondary: `#f9c02b`
- Page background: `#f1f1f1`
