# SSTP RB951 Test

SSTP is the recommended fallback for the tested MikroTik RB951 running RouterOS `6.49.19 (long-term)`.

Required DNS:

```text
sstp.fastnetpay.co.ke A 212.95.35.229 DNS-only
```

Recommended port:

```text
4443/tcp
```

Before testing on the router:

```routeros
/export file=before_fastnetpay_sstp
/system backup save name=before_fastnetpay_sstp
```

Template MikroTik command:

```routeros
/interface sstp-client add name=sstp-fastnetpay connect-to=sstp.fastnetpay.co.ke port=4443 user=ROUTER_SPECIFIC_USERNAME password=ROUTER_SPECIFIC_PASSWORD profile=default-encryption verify-server-certificate=yes add-default-route=no disabled=no
```

Do not replace the router's default route. The SSTP tunnel is for FASTNETPAY management/API traffic only.

Current production status:

- DNS target: `sstp.fastnetpay.co.ke` should resolve directly to `212.95.35.229`.
- Recommended public port: `4443/tcp`.
- RB951 local API test succeeded on `192.168.88.1`.
- Router backups were created before any SSTP test commands:
  - `/export file=before_fastnetpay_sstp`
  - `/system backup save name=before_fastnetpay_sstp`
- SSTP server installation is handled by `scripts/production/install-sstp-accel-ppp.sh`, which builds `accel-ppp` and creates `fastnetpay-sstp.service`.

Blocked item:

Do not push SSTP client commands until the server is installed, listening on `4443/tcp`, and the router has a unique account/IP from `scripts/production/add-sstp-router.sh`.

Minimum server requirements:

- Unique username/password per router.
- Fixed or traceable VPN IP per router.
- Strong TLS certificate.
- Authentication failure rate limiting.
- Log rotation.
- Service enabled at boot.

After the server is ready, record the RB951 in FASTNETPAY using its SSTP VPN IP, not the public WAN IP.

See also: `docs/SSTP-PRODUCTION-SERVER.md`.
