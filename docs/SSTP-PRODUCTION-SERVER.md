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

- verifies the Let's Encrypt wildcard certificate exists;
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

Example command shape:

```routeros
/interface sstp-client add name=sstp-fastnetpay connect-to=sstp.fastnetpay.co.ke port=4443 user="fastnet-rb951-001" password="GENERATED_PASSWORD" profile=default-encryption verify-server-certificate=yes add-default-route=no disabled=no
```

Do not set `add-default-route=yes`; the tunnel is for FASTNETPAY management only.

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

## Security Rules

- Use a unique SSTP username/password per router.
- Keep `sstp.fastnetpay.co.ke` DNS-only.
- Keep API/Winbox/SSH restricted to local management and VPN IPs.
- Do not expose MikroTik API publicly.
- Rotate router SSTP credentials when a router changes owner/site.

## References

- `accel-ppp` SSTP configuration documents `port`, TLS certificate, key file, and SNI options.
- MikroTik RouterOS SSTP documentation confirms RouterOS supports SSTP over TLS for PPP tunnels.
