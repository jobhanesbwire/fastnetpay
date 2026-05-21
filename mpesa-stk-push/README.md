# FASTNETPAY M-Pesa STK Push Gateway

Installable PHPNuxBill payment gateway plugin for Safaricom Daraja M-Pesa STK Push.

## ZIP Packaging

From the project root:

```bash
cd mpesa-stk-push
zip -r ../mpesa-stk-push.zip paymentgateway changelog.txt install.txt license.txt README.md
```

Upload `mpesa-stk-push.zip` through the PHPNuxBill Plugin Manager. The Plugin Manager moves files inside `paymentgateway/` into the PHPNuxBill gateway directory.

## Required Credentials

- Safaricom Daraja environment: `sandbox` or `live`
- Paybill or Till number
- Consumer Key
- Consumer Secret
- Online Passkey
- Public callback URL
- MikroTik walled garden domains for Safaricom/Daraja payment access

Default callback route:

```text
https://your-domain.example/index.php?_route=callback/mpesastkpush
```

Use HTTPS in production. Localhost callbacks will not be reachable by Safaricom unless you expose them through a secure tunnel.

## Customer Flow

1. Customer selects a package in the FASTNETPAY customer/captive portal.
2. Customer selects `M-Pesa STK Push` (`mpesastkpush`) as the gateway.
3. FASTNETPAY opens a mobile-first M-Pesa payment page.
4. Customer enters a Safaricom phone number.
5. FASTNETPAY sends the STK Push request to Daraja.
6. Customer enters the M-Pesa PIN on their phone.
7. Safaricom sends a callback to FASTNETPAY.
8. FASTNETPAY marks the transaction paid and activates the package through `Package::rechargeUser()`.

## Troubleshooting

- Invalid phone number: use `07XXXXXXXX`, `01XXXXXXXX`, `2547XXXXXXXX`, or `2541XXXXXXXX`.
- Wrong shortcode or passkey: STK Push may fail before the customer receives a prompt. Confirm the shortcode type and Daraja passkey.
- Callback not reachable: production callbacks must be public HTTPS URLs and allowed by your firewall/proxy.
- User cancels STK: the callback marks the transaction failed and does not activate a package.
- Payment succeeds but package is not activated: check PHPNuxBill logs, transaction audit, customer status, router status, and `Package::rechargeUser()` errors.
- Duplicate callback: the plugin checks existing paid status and does not double-credit a transaction.

## Sensitive Data

Consumer Secret and Passkey are saved through PHPNuxBill settings and are not printed into frontend templates. Do not paste real production secrets into screenshots, issue trackers, or public repositories.
