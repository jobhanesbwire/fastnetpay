# FASTNETPAY Captive Portal

## Package

The installable theme package is in `fastnetpay-captive-portal/`.

```bash
cd fastnetpay-captive-portal
zip -r ../fastnetpay-captive-portal.zip theme mikrotik-hotspot changelog.txt install.txt license.txt README.md
```

Upload the ZIP through PHPNuxBill Plugin Manager, then select `fastnetpay-captive` in admin settings.

## What It Changes

- Customer dashboard: cleaner FASTNETPAY welcome and quick package/payment links.
- Package selection: mobile-first plan cards using FASTNETPAY green, gold, and light grey.
- Gateway selection: cleaner checkout summary and M-Pesa STK Push label.
- Core routes, forms, payment actions, and package activation logic are left unchanged.

## MikroTik Redirect

For MikroTik hotspot/captive portal use, edit the files in:

```text
fastnetpay-captive-portal/mikrotik-hotspot/
```

Replace this placeholder in `login.html`, `status.html`, and `logout.html`:

```text
https://fastnetpay.example.com/index.php?_route=order/package
```

with your public FASTNETPAY URL, for example:

```text
https://wifi.yourdomain.com/index.php?_route=order/package
```

Then upload the MikroTik files to the active hotspot HTML directory on the router. Keep these router files separate from PHPNuxBill theme files.

## M-Pesa Flow

1. Customer opens MikroTik captive portal.
2. The captive portal links to FASTNETPAY package selection.
3. Customer chooses a package and selects M-Pesa STK Push.
4. FASTNETPAY sends the STK Push.
5. Safaricom callback confirms payment.
6. PHPNuxBill activates the selected package through its existing package activation flow.

## Admin Editability

The M-Pesa gateway settings include payment-page fields for portal title, welcome message, logo URL, support phone, primary color, secondary color, and footer text. These values affect the M-Pesa payment page. Broader captive portal content can be adjusted in the theme templates and CSS.

## Brand Colors

- Primary: `#41a146`
- Secondary: `#f9c02b`
- Background: `#f1f1f1`
