# M-Pesa End-To-End Production Test

Controlled test flow:

1. Connect to a FASTNETPAY-provisioned hotspot.
2. Open the MikroTik-hosted captive portal.
3. Select a low-value package.
4. Enter a Kenyan phone number.
5. Confirm FASTNETPAY creates a pending transaction.
6. Trigger STK through Daraja/Jovi-Pay.
7. Approve the prompt.
8. Confirm the HTTPS callback reaches FASTNETPAY.
9. Confirm the transaction becomes successful.
10. Confirm the selected hotspot/PPPoE profile is activated.
11. Confirm the user gains internet.
12. Replay the same callback and confirm it does not activate twice.

Failure cases to test:

- user cancels
- timeout
- wrong amount
- duplicate callback
- unknown account prefix
- unmatched receipt
- callback arrives after the captive portal browser is closed

Keep callback endpoints independent from PHP browser sessions.

Production endpoint readiness:

- Jovi-Pay callback route reaches FASTNETPAY and rejects invalid JSON/signatures.
- Hotspot package API reaches FASTNETPAY and rejects missing/invalid router portal tokens.
- Callback/API hosts are HTTPS and Cloudflare-proxied.
- API responses are marked `no-store`.

Not completed automatically:

- A real low-value STK approval was not executed during infrastructure verification because it requires a live phone approval and production payment credentials/limits.

Before go-live:

1. Confirm tenant M-Pesa/Jovi-Pay credentials are saved.
2. Confirm Jovi-Pay forwards the correct account prefix to FASTNETPAY.
3. Run one low-value package purchase from a MikroTik captive portal.
4. Confirm transaction status, package activation, and duplicate-callback idempotency.
