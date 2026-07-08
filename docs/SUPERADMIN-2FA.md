# SuperAdmin SMS 2FA

FASTNETPAY supports SMS OTP readiness for SuperAdmin accounts.

## Requirements

- SMS gateway must be configured first.
- TALKSASA is supported through the existing SMS integration.
- The SuperAdmin user must have a phone number.

## Enable

Open:

`SaaS Management -> SuperAdmin 2FA`

Enable 2FA for the required SuperAdmin user.

## Login Flow

1. SuperAdmin enters username and password.
2. FASTNETPAY sends a 6-digit OTP by SMS.
3. SuperAdmin enters OTP.
4. Session is created only after OTP succeeds.

OTP expires after 10 minutes and allows limited attempts. OTP sends, failures, and successful verification are written to SaaS audit logs.

## Production Notes

- Keep SuperAdmin phone numbers current.
- Restrict SuperAdmin access by IP/VPN when possible.
- Keep SMS gateway credentials tenant-safe and never expose SMS tokens in templates.
