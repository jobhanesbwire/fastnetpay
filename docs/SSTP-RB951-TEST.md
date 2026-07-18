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
/tool fetch url="https://letsencrypt.org/certs/isrgrootx1.pem" dst-path=fastnetpay-isrgrootx1.pem mode=https check-certificate=no keep-result=yes
/certificate import file-name=fastnetpay-isrgrootx1.pem passphrase=""
/certificate set [find common-name="ISRG Root X1"] trusted=yes
/interface sstp-client add name=sstp-fastnetpay connect-to=sstp.fastnetpay.co.ke:4443 user=ROUTER_SPECIFIC_USERNAME password=ROUTER_SPECIFIC_PASSWORD profile=default-encryption verify-server-certificate=yes add-default-route=no disabled=no comment="FASTNETPAY SSTP management tunnel"
/ip address add address=10.100.1.1/32 network=10.100.0.1 interface=sstp-fastnetpay comment="FASTNETPAY stable router VPN API IP"
/ip firewall filter add chain=input src-address=10.100.0.1 action=accept place-before=0 comment="FASTNETPAY accept VPN management input"
/interface list member add list=LAN interface=sstp-fastnetpay comment="FASTNETPAY VPN management tunnel"
```

Do not replace the router's default route. The SSTP tunnel is for FASTNETPAY management/API traffic only. RB951/RouterOS v6 rejects the newer `port=4443` one-liner form, so use `connect-to=sstp.fastnetpay.co.ke:4443`.

Current production status:

- DNS target: `sstp.fastnetpay.co.ke` should resolve directly to `212.95.35.229`.
- Recommended public port: `4443/tcp`.
- RB951 local API test succeeded on `192.168.88.1`.
- Router backups were created before any SSTP test commands:
  - `/export file=before_fastnetpay_sstp`
  - `/system backup save name=before_fastnetpay_sstp`
- SSTP server installation is handled by `scripts/production/install-sstp-accel-ppp.sh`, which builds `accel-ppp` and creates `fastnetpay-sstp.service`.
- The production test RB951 succeeded after importing `ISRG Root X1`, setting `verify-server-certificate=yes`, and pinning `10.100.1.1/32` on `sstp-fastnetpay`.
- The VPS route hook must be installed so `/etc/ppp/chap-secrets` fourth-column addresses route to the live PPP interface.

Production test result:

- The tunnel authenticated as the router-specific SSTP account.
- The VPS reached `10.100.1.1` by ICMP.
- The FASTNETPAY PHP container opened TCP `10.100.1.1:8728`.

Minimum server requirements:

- Unique username/password per router.
- Fixed or traceable VPN IP per router.
- Dedicated RSA TLS certificate for `sstp.fastnetpay.co.ke`. The RB951/RouterOS v6 test path fails against the ECDSA wildcard certificate.
- No `host-name=` SNI requirement in the `accel-ppp` `[sstp]` section because RouterOS v6 does not send SSTP client SNI.
- Authentication failure rate limiting.
- Log rotation.
- Service enabled at boot.

After the server is ready, record the RB951 in FASTNETPAY using its SSTP VPN IP, not the public WAN IP.

See also: `docs/SSTP-PRODUCTION-SERVER.md`.
