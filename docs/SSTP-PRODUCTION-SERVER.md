# SSTP Production Server

FASTNETPAY uses WireGuard for RouterOS v7 routers and SSTP for RouterOS v6 routers such as the tested RB951.

SSTP is provided by `accel-ppp` on the VPS because RouterOS v6 does not support WireGuard.

## Access Requirement

The VPS must be reachable by SSH or provider console before this can be installed. If port `22/tcp` is refused, use the VPS provider console to restore SSH first.

## DNS

Keep this DNS record **DNS-only** in Cloudflare:

```text
sstp.fastnetpay.co.ke A 212.95.35.229
```

Do not proxy SSTP through Cloudflare orange-cloud DNS.

## Install

From the VPS console or an authorized SSH session:

```bash
cd /opt/fastnetpay/app
git pull origin main
SSTP_HOST=sstp.fastnetpay.co.ke \
SSTP_PORT=4443 \
SSTP_LOCAL_IP=10.100.0.1 \
SSTP_POOL=10.100.200.10-254 \
bash scripts/production/install-sstp-accel-ppp.sh
```

The installer:

- creates or verifies a dedicated RSA Let's Encrypt certificate named `sstp.fastnetpay.co.ke-rsa` when Cloudflare DNS credentials exist at `/root/.secrets/cloudflare.ini`;
- builds `accel-ppp` from source;
- writes `/etc/accel-ppp/accel-ppp.conf`;
- enables `fastnetpay-sstp.service`;
- opens `4443/tcp` in UFW when UFW is active;
- enables IPv4 forwarding;
- prepares `/etc/ppp/chap-secrets` with mode `600`;
- configures log rotation.

## Add A Router

Create one unique account per router:

```bash
cd /opt/fastnetpay/app
sudo bash scripts/production/add-sstp-router.sh fastnet-rb951-001 10.100.1.1
```

The helper prints the RouterOS command with a generated password. Store that password in FASTNETPAY router credentials or a secure password manager.

## Reset Router Bootstrap

If a MikroTik is reset, production FASTNETPAY cannot reach it at `10.100.x.x` because the SSTP client has been erased. This is normal. A remote production router must first dial home to the VPS before the web app can run full provisioning.

Use the Router Provisioning Wizard:

1. Open the router provisioning page.
2. Keep connection mode as `SSTP VPN`.
3. Confirm `SSTP Server`, `SSTP Username`, `SSTP Password`, `Router VPN IP`, and `FASTNETPAY API Password`.
4. Click **Generate Reset Router Bootstrap**.
5. Paste the generated script once into the reset MikroTik Winbox Terminal.
6. Wait for `/ping 10.100.0.1` to succeed.
7. Test connection in FASTNETPAY using the router VPN IP, then run full provisioning.

This bootstrap only enables RouterOS API, creates/repairs `fastnet-api-usr`, creates `sstp-fastnetpay`, adds the stable router VPN IP, and allows FASTNETPAY management traffic. Full Hotspot, PPPoE, captive portal, payment, queue, and security setup still happens in the normal provisioning run.

Example command shape:

```routeros
/interface list add name=WAN comment="FASTNETPAY WAN interfaces"
/interface list member add list=WAN interface=ether1 comment="FASTNETPAY bootstrap WAN"
/ip dhcp-client add interface=ether1 add-default-route=yes use-peer-dns=yes disabled=no comment="FASTNETPAY bootstrap WAN DHCP"
/ip dns set servers="1.1.1.1,8.8.8.8" allow-remote-requests=yes
:if ([:len [/interface sstp-client find name="sstp-fastnetpay"]] = 0) do={/interface sstp-client add name="sstp-fastnetpay" connect-to="sstp.fastnetpay.co.ke:4443" user="fastnet-rb951-001" password="GENERATED_PASSWORD" profile="default-encryption" verify-server-certificate=no add-default-route=no disabled=yes comment="FASTNETPAY SSTP management tunnel"}
/interface sstp-client set [find name="sstp-fastnetpay"] connect-to="sstp.fastnetpay.co.ke:4443"
/interface sstp-client set [find name="sstp-fastnetpay"] user="fastnet-rb951-001"
/interface sstp-client set [find name="sstp-fastnetpay"] password="GENERATED_PASSWORD"
/interface sstp-client set [find name="sstp-fastnetpay"] profile="default-encryption" verify-server-certificate=no
/interface sstp-client set [find name="sstp-fastnetpay"] add-default-route=no disabled=no comment="FASTNETPAY SSTP management tunnel"
/ip address add address="10.100.1.1/32" network="10.100.0.1" interface=sstp-fastnetpay comment="FASTNETPAY stable router VPN API IP"
/ip firewall filter add chain=input src-address=10.100.0.1 action=accept place-before=0 comment="FASTNETPAY accept VPN management input"
/interface list member add list=LAN interface=sstp-fastnetpay comment="FASTNETPAY VPN management tunnel"
```

Do not set `add-default-route=yes`; the tunnel is for FASTNETPAY management only. On RouterOS v6, put the custom port in `connect-to=host:port`; do not use a separate `port=4443` argument on RB951.

The reset bootstrap intentionally uses `verify-server-certificate=no` and skips `/tool fetch` CA import. Fresh RouterOS v6 devices can hang on the CA download before the VPN exists, which leaves the terminal half-pasted and prevents `sstp-fastnetpay` from being created. After the router is stable, certificate verification can be tightened from local management.

The initial `sstp-client add` includes `connect-to`, user, password, profile, and certificate mode. RB951/RouterOS v6 prompts interactively for `connect-to` if those values are omitted, which can swallow the next pasted line and cancel the script.

For a fully reset router, connect internet to `ether1` before pasting the bootstrap. The wizard starts a DHCP client on the selected WAN interface so the router can resolve and dial `sstp.fastnetpay.co.ke` before full provisioning runs.

FASTNETPAY keeps `/etc/ppp/chap-secrets` in the PPP-compatible four-column form:

```text
router-username  *  router-password  stable-router-vpn-ip
```

The SSTP installer creates `/etc/ppp/ip-up.d/fastnetpay-sstp-routes` and `/etc/ppp/ip-down.d/fastnetpay-sstp-routes`. These hooks read the fourth column and route that stable `10.100.x.x/32` address to the live PPP interface when the router connects. This lets the FASTNETPAY app register RB951 as `10.100.1.1` even when the PPP server also assigns an internal pool address.

The `accel-ppp` SSTP server must not require TLS SNI for RB951/RouterOS v6 clients. RouterOS v6 does not send SSTP client SNI, so the production template intentionally omits `host-name=` from the `[sstp]` section.

The SSTP certificate should be RSA, not ECDSA. The tested RB951 on RouterOS `6.49.19` fails TLS handshakes against the ECDSA wildcard certificate. Use a dedicated RSA certificate for `sstp.fastnetpay.co.ke`:

```bash
certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials /root/.secrets/cloudflare.ini \
  --key-type rsa \
  --rsa-key-size 2048 \
  --cert-name sstp.fastnetpay.co.ke-rsa \
  -d sstp.fastnetpay.co.ke \
  --non-interactive \
  --agree-tos \
  -m admin@fastnetpay.co.ke
```

The SSTP template also pins the server to TLS 1.2 with `ssl-ciphers=DEFAULT:@SECLEVEL=1` for RouterOS v6 compatibility. This setting is only for the SSTP service on `4443/tcp`; it does not weaken the Nginx/browser HTTPS configuration.

## Validation

On the VPS:

```bash
systemctl status fastnetpay-sstp.service
ss -ltnp | grep ':4443'
journalctl -u fastnetpay-sstp.service -n 100 --no-pager
tail -f /var/log/accel-ppp/accel-ppp.log
```

From the RB951:

```routeros
/interface sstp-client print detail where name=sstp-fastnetpay
/ip address print where interface=sstp-fastnetpay
/ping 10.100.0.1
```

Then register the router in FASTNETPAY using its VPN IP, for example `10.100.1.1`.

Once the router is registered by VPN IP, do not rerun SSTP client creation through that same VPN session. FASTNETPAY should verify the existing `sstp-fastnetpay` tunnel and continue provisioning Hotspot/PPPoE without disabling or recreating the tunnel. To rotate SSTP credentials, connect through the local management port first, update the SSTP account, then switch the router record back to the VPN IP after the tunnel is stable.

From the VPS host:

```bash
ping 10.100.1.1
nc -vz 10.100.1.1 8728
```

From the FASTNETPAY PHP container:

```bash
cd /opt/fastnetpay/app
docker compose --env-file .env.production -f docker-compose.prod.yml exec -T fastnetpay_app \
  php -r '$s=@fsockopen("10.100.1.1",8728,$e,$m,5); echo $s ? "tcp-ok\n" : "tcp-fail $e $m\n"; if($s) fclose($s);'
```

## Security Rules

- Use a unique SSTP username/password per router.
- Keep `sstp.fastnetpay.co.ke` DNS-only.
- Keep API/Winbox/SSH restricted to local management and VPN IPs.
- Do not expose MikroTik API publicly.
- Rotate router SSTP credentials when a router changes owner/site.

## References

- `accel-ppp` SSTP configuration documents `port`, TLS certificate, key file, and SNI options. FASTNETPAY does not enable SSTP `host-name=` because RouterOS v6 clients do not send SNI.
- MikroTik RouterOS SSTP documentation confirms RouterOS supports SSTP over TLS for PPP tunnels and shows custom ports in the printed `connect-to=host:port` form.
