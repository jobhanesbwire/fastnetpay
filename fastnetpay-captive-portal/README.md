# FASTNETPAY Captive Portal Theme

Installable FASTNETPAY customer/captive portal theme package for PHPNuxBill, with a separate MikroTik hotspot HTML starter folder.

## ZIP Packaging

```bash
cd fastnetpay-captive-portal
zip -r ../fastnetpay-captive-portal.zip theme mikrotik-hotspot changelog.txt install.txt license.txt README.md
```

Upload the ZIP through PHPNuxBill Plugin Manager. PHPNuxBill moves files inside `theme/` into `ui/themes/`.

## Activation

After upload, open admin settings and choose `fastnetpay-captive` as the theme. The theme overrides the customer dashboard, package selection, and gateway selection pages while preserving existing PHPNuxBill order/payment routes.

## MikroTik

The `mikrotik-hotspot/` folder is not installed into PHPNuxBill automatically. Edit the FASTNETPAY domain placeholders, then upload those files to your MikroTik hotspot profile using WinBox, WebFig, or FTP.
