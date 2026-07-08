# Tenant Settings Scope

Tenant settings are separated from global application settings.

## Tenant-Safe Settings

Tenant admins can update only their own tenant profile and presentation settings:

- Business/display name
- Logo URL
- Primary and secondary colors
- Support phone and email
- Support WhatsApp
- Welcome message
- Portal terms text
- Portal/footer text
- Currency and timezone display
- Invoice footer
- Notification preferences

These values are saved to the `tenants` and `tenant_settings` tables.

## Global-Only Settings

Tenant users must not edit:

- App-wide name/branding
- Payment gateway secrets
- Jovi-Pay/MPESA callback secrets
- SMS vendor tokens
- Plugin manager
- Database backup/restore
- Global maintenance mode
- Global cron/worker controls
- Global system/security settings

Tenant requests to global settings routes are blocked and audited.
