# SaaS Performance Fixes

## Tenant Resolution

Tenant resolution already cached the resolved tenant per request. The new optimization avoids doing schema migration checks during every tenant resolution cycle.

Local tenant support remains:

- `localhost`
- `sega.localhost`
- `isp1.localhost`
- `?_tenant=slug`
- `?tenant=slug`

## SaaS Billing

SaaS billing now uses:

- versioned schema cache
- request-level setting cache
- cached payment settings
- snapshot-based dashboard expected revenue
- snapshot-based top tenant list where available

Full invoice preview still runs on the billing page because that page is expected to calculate billing details.

## Tenant Payment Settings

Mother-system pages no longer query tenant customer payment/SMS settings. Tenant-specific overrides are loaded only when the request is actually a tenant portal request.

## Customer Payment Gateway Compatibility

Jovi-Pay settings are cached per tenant during a request. SuperAdmin tenant gateway assignments still overlay the public Jovi-Pay settings used by the captive portal.
