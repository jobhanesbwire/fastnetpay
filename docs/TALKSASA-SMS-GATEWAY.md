# TALKSASA SMS Gateway

## Package

The installable plugin package is in `talksasa-sms/`.

```bash
cd talksasa-sms
zip -r ../talksasa-sms.zip plugin changelog.txt install.txt license.txt README.md
```

Upload the ZIP through PHPNuxBill Plugin Manager.

## Admin Settings

Open `TALKSASA SMS` in the admin menu and configure:

- API TOKEN
- API ENDPOINT
- SENDER_ID

Default endpoint:

```text
https://bulksms.talksasa.com/api/v3/sms/send
```

Saving the plugin sets PHPNuxBill SMS routing to `talksasa` while keeping the existing voucher, payment, customer notification, expiry reminder, and admin-triggered SMS call sites intact.

## Request Format

The plugin sends JSON with:

```json
{
  "recipient": "2547XXXXXXXX",
  "sender_id": "SENDER_ID",
  "type": "plain",
  "message": "Message text"
}
```

Multiple recipients are sent as comma-separated numbers in the same `recipient` field.

## Phone Numbers

- `07XXXXXXXX` becomes `2547XXXXXXXX`
- `01XXXXXXXX` becomes `2541XXXXXXXX`
- `+254XXXXXXXXX` becomes `254XXXXXXXXX`
- Existing valid international numbers are preserved

## Security

- The API TOKEN is masked in the admin UI after saving.
- The token is not exposed in frontend source.
- Network and API errors are logged without the token.
- cURL uses timeouts and SSL verification.

## Troubleshooting

- Invalid phone number: use a valid Kenyan mobile number or a valid international number.
- Authentication error: verify API TOKEN and SENDER_ID.
- Endpoint error: confirm the endpoint URL and HTTPS availability.
- SMS not sent from PHPNuxBill: confirm `sms_gateway` or `sms_url` is set to `talksasa`.
