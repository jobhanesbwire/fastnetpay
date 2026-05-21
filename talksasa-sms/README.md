# FASTNETPAY TALKSASA SMS Gateway

Installable PHPNuxBill plugin for sending FASTNETPAY SMS notifications through TALKSASA.

## ZIP Packaging

```bash
cd talksasa-sms
zip -r ../talksasa-sms.zip plugin changelog.txt install.txt license.txt README.md
```

Upload the ZIP through PHPNuxBill Plugin Manager. PHPNuxBill moves files inside `plugin/` into the system plugin folder.

## Configuration

Open `TALKSASA SMS` in the admin menu and configure only:

- API TOKEN
- API ENDPOINT
- SENDER_ID

Default endpoint:

```text
https://bulksms.talksasa.com/api/v3/sms/send
```

Saving the settings sets `sms_gateway` and `sms_url` to `talksasa`, so existing PHPNuxBill SMS calls use this plugin.

## Phone Numbers

The plugin supports one number or comma-separated numbers. Kenyan numbers are normalized:

- `07XXXXXXXX` to `2547XXXXXXXX`
- `01XXXXXXXX` to `2541XXXXXXXX`
- `+254XXXXXXXXX` to `254XXXXXXXXX`

Already valid international numbers are preserved.
