# FASTNETPAY

FASTNETPAY is a PHPNuxBill-based WiFi, PPPoE, and Hotspot billing platform prepared for local Docker development and future SaaS/multi-tenant work. The upstream PHPNuxBill license and attribution are preserved.

## Local Docker Setup

Start the local stack:

```bash
docker compose up -d --build
```

Open the app:

- FASTNETPAY: http://localhost:8088
- phpMyAdmin: http://localhost:8089

Local database settings:

- DB host inside Docker: `fastnetpay_db`
- DB name: `fast_pay_net`
- DB user: `root`
- DB password: `root1234`
- MySQL local port: `3309`

This root database user is for local development only. Production should use a dedicated non-root MySQL user, strong secrets, `APP_STAGE=Live`, and `APP_DISPLAY_ERRORS=0`.

## Local Setup Notes

- `config.php` is generated for this local Docker setup and is ignored by Git because it contains local credentials.
- `config.sample.php` and `.env.example` show how to move credentials into environment variables later.
- The Docker app imports `install/phpnuxbill.sql` into MySQL only when the `fastnetpay_db_data` volume is empty.
- Default admin credentials from PHPNuxBill are `admin` / `admin`. Change them immediately after first login.
- Do not delete install scripts automatically. After a real production install, restrict or remove `install/` only as part of a deliberate deployment hardening step.

## Troubleshooting

View logs:

```bash
docker logs fastnetpay_app
docker logs fastnetpay_db
```

Enter the app container:

```bash
docker exec -it fastnetpay_app bash
```

Reset the local database:

```bash
docker compose down -v
docker compose up -d --build
```

Validate the compose file:

```bash
docker compose config
```

## Project Structure Notes

- Main public entry point: `index.php`
- Admin entry point: `admin/index.php`
- Config template: `config.sample.php`
- Local config: `config.php`
- Composer manifests: `composer.json` and `system/composer.json`
- Writable runtime folders: `system/cache`, `system/uploads`, `ui/cache`, `qrcode/cache`, and `system/vendor/mpdf/mpdf/tmp`

# PHPNuxBill - PHP Mikrotik Billing

![PHPNuxBill](install/img/logo.png)

## Feature

- Voucher Generator and Print
- [Freeradius](https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius)
- Self registration
- User Balance
- Auto Renewal Package using Balance
- Multi Router Mikrotik
- Hotspot & PPPOE
- Easy Installation
- Multi Language
- Payment Gateway
- SMS validation for login
- Whatsapp Notification to Consumer
- Telegram Notification for Admin

See [How it Works / Cara Kerja](https://github.com/hotspotbilling/phpnuxbill/wiki/How-It-Works---Cara-kerja)

## Payment Gateway And Plugin

- [Payment Gateway List](https://github.com/orgs/hotspotbilling/repositories?q=payment+gateway)
- [Plugin List](https://github.com/orgs/hotspotbilling/repositories?q=plugin)

You can download payment gateway and Plugin from Plugin Manager

## System Requirements

Most current web servers with PHP & MySQL installed will be capable of running PHPNuxBill

Minimum Requirements

- Linux or Windows OS
- Minimum PHP Version 8.2
- Both PDO & MySQLi Support
- PHP-GD2 Image Library
- PHP-CURL
- PHP-ZIP
- PHP-Mbstring
- MySQL Version 4.1.x and above

can be Installed in Raspberry Pi Device.

The problem with windows is hard to set cronjob, better Linux

## Changelog

[CHANGELOG.md](CHANGELOG.md)

## Installation

[Installation instructions](https://github.com/hotspotbilling/phpnuxbill/wiki)

## Freeradius

Support [Freeradius with Database](https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius)

## Community Support

- [Github Discussion](https://github.com/hotspotbilling/phpnuxbill/discussions)
- [Telegram Group](https://t.me/phpmixbill)

## Technical Support

This Software is Free and Open Source, Without any Warranty.

Even if the software is free, but Technical Support is not,
Technical Support Start from Rp 500.000 or $50

If you chat me for any technical support,
you need to pay,

ask anything for free in the [discussion](/hotspotbilling/phpnuxbill/discussions) page or [Telegram Group](https://t.me/phpnuxbill)

Contact me at [Telegram](https://t.me/ibnux)

## License

GNU General Public License version 2 or later

see [LICENSE](LICENSE) file


## Donate to ibnux

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://paypal.me/ibnux)

BCA: 5410454825

Mandiri: 163-000-1855-793

a.n Ibnu Maksum

## SPONSORS

- [mixradius.com](https://mixradius.com/) Paid Services Billing Radius
- [mlink.id](https://mlink.id)
- [https://github.com/sonyinside](https://github.com/sonyinside)

## Thanks
We appreciate all people who are participating in this project.

<a href="https://github.com/hotspotbilling/phpnuxbill/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=hotspotbilling/phpnuxbill" />
</a>
