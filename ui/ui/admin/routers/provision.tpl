{include file="sections/header.tpl"}

<div class="fnp-provision-page" data-base-url="{$_url}" data-router-id="{$router_id}">
    <div class="fnp-provision-hero">
        <div>
            <span class="fnp-provision-eyebrow"><i class="fa fa-magic"></i> FASTNETPAY Router Provisioning Wizard</span>
            <h2>{if $router}{$router['name']}{else}Provision a New MikroTik Router{/if}</h2>
            <p>Preview, backup, then safely configure Hotspot, PPPoE, payment access, captive portal, security, and FASTNETPAY integration.</p>
        </div>
        <div class="fnp-provision-hero-actions">
            <a href="{Text::url('routers/list')}" class="btn btn-default"><i class="fa fa-list"></i> Routers</a>
            {if $router_id}
                <a href="{Text::url('routers/provision-logs/', $router_id)}" class="btn btn-primary"><i class="fa fa-history"></i> View Logs</a>
            {/if}
        </div>
    </div>

    <div class="fnp-provision-alert {if $mpesa.ready}is-ready{else}is-warning{/if}">
        <i class="fa {if $mpesa.ready}fa-check-circle{else}fa-exclamation-triangle{/if}"></i>
        <div>
            <strong>MPESA STK Push readiness:</strong>
            {if $mpesa.ready}
                Ready for automatic package activation. Environment: {$mpesa.environment|escape}, Shortcode: {$mpesa.shortcode|escape}
            {else}
                Needs attention before production activation: {$mpesa_missing|escape}
            {/if}
        </div>
    </div>

    <form id="fnpProvisionForm" class="fnp-provision-shell">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <aside class="fnp-provision-steps">
            <button type="button" class="fnp-provision-step-button active" data-target="step-connection"><span>1</span> Connection</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-profile"><span>2</span> Profile</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-interfaces"><span>3</span> Interfaces</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-ip"><span>4</span> IP & DHCP</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-packages"><span>5</span> Packages</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-portal"><span>6</span> Portal</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-payment"><span>7</span> Payment</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-security"><span>8</span> Security</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-preview"><span>9</span> Preview</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-backup"><span>10</span> Backup</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-apply"><span>11</span> Apply</button>
            <button type="button" class="fnp-provision-step-button" data-target="step-final"><span>12</span> Final Test</button>
        </aside>

        <main class="fnp-provision-main">
            <section id="step-connection" class="fnp-provision-step-panel active">
                <div class="fnp-provision-card">
                    <div class="fnp-provision-card-head">
                        <div>
                            <h3>Router Connection</h3>
                            <p>Tests the saved PHPNuxBill router connection first, then creates or verifies the dedicated FASTNETPAY API user when possible.</p>
                        </div>
                        <button type="button" id="fnpProvisionDetect" class="btn btn-primary"><i class="fa fa-plug"></i> Test Connection</button>
                    </div>
                    <div class="fnp-provision-alert is-ready">
                        <i class="fa fa-shield"></i>
                        <div>
                            <strong>Secure API user:</strong>
                            FASTNETPAY prefers <code>fastnet-api-usr</code>, but keeps the default PHPNuxBill router connection path available so existing MikroTik features keep working.
                        </div>
                    </div>
                    <div class="fnp-provision-grid">
                        <label>Router Name
                            <input type="text" name="router_name" class="form-control" value="{$settings.router_name|escape}" placeholder="Main Tower">
                        </label>
                        <label>Router IP / Host
                            <input type="text" name="host" class="form-control" value="{$settings.host|escape}" placeholder="192.168.88.1">
                        </label>
                        <label>API Port
                            <input type="number" name="api_port" class="form-control" value="{$settings.api_port|escape}" min="1" max="65535">
                        </label>
                        <label>API-SSL Port
                            <input type="number" name="api_ssl_port" class="form-control" value="{$settings.api_ssl_port|escape}" min="1" max="65535">
                        </label>
                        <label>Bootstrap/Admin Username
                            <input type="text" name="username" class="form-control" value="{$settings.username|escape}" autocomplete="off">
                        </label>
                        <label>Bootstrap/Admin Password
                            <input type="password" name="password" class="form-control" value="{$settings.password|escape}" autocomplete="new-password">
                        </label>
                        <label>FASTNETPAY API Username
                            <input type="text" name="api_username" class="form-control" value="{$settings.api_username|escape}" readonly>
                        </label>
                        <label>FASTNETPAY API Password
                            <input type="password" name="api_password" class="form-control" value="{$settings.api_password|escape}" autocomplete="new-password" placeholder="Strong password for fastnet-api-usr">
                        </label>
                    </div>
                    <label class="fnp-provision-check">
                        <input type="checkbox" name="ensure_api_user" value="yes" {if $settings.ensure_api_user neq 'no'}checked{/if}>
                        Create or repair <code>fastnet-api-usr</code> if it does not already exist
                    </label>
                    <label class="fnp-provision-check">
                        <input type="checkbox" name="prefer_ssl" value="yes" {if $settings.prefer_ssl eq 'yes'}checked{/if}>
                        Prefer API-SSL when the router supports it
                    </label>
                    <div id="fnpProvisionDetectResult" class="fnp-provision-detect"></div>
                    <div class="fnp-provision-callout">
                        <strong>Reset router or remote production router?</strong>
                        If this router was reset, production cannot reach it until the MikroTik first dials home over SSTP. Generate the bootstrap, paste it once in Winbox Terminal, then test connection again using the VPN IP.
                    </div>
                    <div class="fnp-provision-toolbar">
                        <button type="button" id="fnpProvisionBootstrap" class="btn btn-warning"><i class="fa fa-terminal"></i> Generate Reset Router Bootstrap</button>
                        <button type="button" id="fnpProvisionBootstrapCopy" class="btn btn-default"><i class="fa fa-copy"></i> Copy Bootstrap</button>
                    </div>
                    <div id="fnpProvisionBootstrapResult" class="fnp-provision-warnings"></div>
                    <pre id="fnpProvisionBootstrapScript" class="fnp-provision-script">For a reset router, fill SSTP/API settings, click Generate Reset Router Bootstrap, then paste the script into MikroTik Terminal.</pre>
                </div>
            </section>

            <section id="step-profile" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <h3>Deployment Profile</h3>
                    <div class="fnp-provision-radio-grid">
                        <label><input type="radio" name="deployment_profile" value="hotspot" {if $settings.deployment_profile eq 'hotspot'}checked{/if}> <span><b>Hotspot Only</b><small>Captive portal, packages, MPESA flow.</small></span></label>
                        <label><input type="radio" name="deployment_profile" value="pppoe" {if $settings.deployment_profile eq 'pppoe'}checked{/if}> <span><b>PPPoE Only</b><small>PPP profiles and access server.</small></span></label>
                        <label><input type="radio" name="deployment_profile" value="mixed" {if $settings.deployment_profile eq 'mixed'}checked{/if}> <span><b>Hotspot + PPPoE</b><small>Mixed ISP deployment.</small></span></label>
                        <label><input type="radio" name="deployment_profile" value="base" {if $settings.deployment_profile eq 'base'}checked{/if}> <span><b>Base ISP Setup Only</b><small>Identity, DNS, NAT, interface lists.</small></span></label>
                        <label><input type="radio" name="deployment_profile" value="security" {if $settings.deployment_profile eq 'security'}checked{/if}> <span><b>Security Hardening Only</b><small>API restrictions, abuse lists, firewall safety.</small></span></label>
                    </div>
                    <label>Deployment Template
                        <select name="deployment_template" class="form-control">
                            {foreach $templates as $template}
                                <option value="{$template['name']|escape}" {if $settings.deployment_template eq $template['name']}selected{/if}>{$template['name']|escape} - {$template['type']|escape}</option>
                            {/foreach}
                        </select>
                    </label>
                </div>
            </section>

            <section id="step-interfaces" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <h3>Interface Mapping</h3>
                    <p class="fnp-provision-muted">Use the detection result to choose the correct WAN, LAN, Hotspot, PPPoE, and management interfaces. Existing bridges are supported.</p>
                    <div class="fnp-provision-grid">
                        <label>WAN Interface <input type="text" name="wan_interface" class="form-control" value="{$settings.wan_interface|escape}" placeholder="ether1" list="fnpProvisionInterfaceOptions"></label>
                        <label>LAN Bridge / Interface <input type="text" name="lan_interface" class="form-control" value="{$settings.lan_interface|escape}" placeholder="fastnetpay-bridge" list="fnpProvisionInterfaceOptions"></label>
                        <label>Hotspot Interface <input type="text" name="hotspot_interface" class="form-control" value="{$settings.hotspot_interface|escape}" placeholder="fastnetpay-bridge" list="fnpProvisionInterfaceOptions"></label>
                        <label>PPPoE Interface <input type="text" name="pppoe_interface" class="form-control" value="{$settings.pppoe_interface|escape}" placeholder="fastnetpay-bridge" list="fnpProvisionInterfaceOptions"></label>
                            <label>Management Port: Safe admin access only, one device allowed <input type="text" name="management_interface" class="form-control" value="{$settings.management_interface|escape}" placeholder="ether4" list="fnpProvisionInterfaceOptions"><small class="fnp-provision-muted">Recommended: <code>ether4</code>. This port stays out of the customer bridge, keeps internet for admin work, and can be bound to one trusted device.</small></label>
                            <label>Management Device IP <input type="text" name="management_ip" class="form-control" value="{$settings.management_ip|escape}" placeholder="192.168.88.10"><small class="fnp-provision-muted">Used for a controlled single-device lease when you bind a MAC.</small></label>
                            <label>Trusted Management MAC <input type="text" name="management_allowed_mac" class="form-control" value="{$settings.management_allowed_mac|escape}" placeholder="AA:BB:CC:DD:EE:FF"><small class="fnp-provision-muted">Optional, but recommended. When set, unknown devices on the management port are blocked where RouterOS bridge filtering supports it.</small></label>
                            <label>Max Management Devices <input type="number" name="management_max_devices" class="form-control" value="{$settings.management_max_devices|escape}" min="1" max="4"><small class="fnp-provision-muted">The wizard warns if more devices are detected.</small></label>
                        </div>
                        <input type="hidden" name="management_bind_mac" value="no">
                        <label class="fnp-provision-check">
                            <input type="checkbox" name="management_bind_mac" value="yes" {if $settings.management_bind_mac eq 'yes'}checked{/if}>
                            Bind management access to the trusted MAC when supplied
                        </label>
                        <datalist id="fnpProvisionInterfaceOptions">
                        <option value="ether1">
                        <option value="ether2">
                        <option value="ether3">
                        <option value="ether4">
                        <option value="fastnetpay-bridge">
                    </datalist>
                    <div id="fnpProvisionInterfaces" class="fnp-provision-interface-list"></div>
                </div>
            </section>

            <section id="step-ip" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <h3>IP, DHCP & Pool Setup</h3>
                    <div class="fnp-provision-grid">
                        <label>LAN Gateway IP/CIDR <input type="text" name="lan_gateway" class="form-control" value="{$settings.lan_gateway|escape}" placeholder="10.100.90.1/24"><small class="fnp-provision-muted">For SSTP/WireGuard sites use a 10.100.x.x customer subnet such as <code>10.100.90.1/24</code>. Local-only lab routers can still use <code>192.168.90.1/24</code>.</small></label>
                        <label>Hotspot IP Pool <input type="text" name="hotspot_pool" class="form-control" value="{$settings.hotspot_pool|escape}" placeholder="10.100.90.50-10.100.90.250"></label>
                        <label>PPPoE IP Pool <input type="text" name="pppoe_pool" class="form-control" value="{$settings.pppoe_pool|escape}" placeholder="100.64.10.10-100.64.10.250"></label>
                        <label>DHCP Range <input type="text" name="dhcp_range" class="form-control" value="{$settings.dhcp_range|escape}" placeholder="10.100.90.50-10.100.90.250"></label>
                        <label>DNS Servers <input type="text" name="dns_servers" class="form-control" value="{$settings.dns_servers|escape}" placeholder="1.1.1.1,8.8.8.8"></label>
                        <label>Local Portal DNS Name <input type="text" name="dns_name" class="form-control" value="{$settings.dns_name|escape}" placeholder="portal.fastnetpay.test"></label>
                        <label>DHCP Lease Time <input type="text" name="dhcp_lease_time" class="form-control" value="{$settings.dhcp_lease_time|escape}" placeholder="12h"></label>
                    </div>
                </div>
            </section>

            <section id="step-packages" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <h3>Packages / Profile Mapping</h3>
                    <p class="fnp-provision-muted">Select FASTNETPAY packages to generate RouterOS Hotspot user profiles and PPPoE profiles. Leave empty to include all enabled Hotspot/PPPoE plans.</p>
                    <div class="fnp-provision-plan-list">
                        {foreach $plans as $plan}
                            <label class="fnp-provision-plan">
                                <input type="checkbox" name="plan_ids[]" value="{$plan.id}">
                                <span>
                                    <b>{$plan.name_plan|escape}</b>
                                    <small>{$plan.type|escape} · {$plan.name_bw|escape} · {$plan.validity|escape} {$plan.validity_unit|escape}</small>
                                </span>
                                <em>{Lang::moneyFormat($plan.price)}</em>
                            </label>
                        {foreachelse}
                            <div class="fnp-provision-empty">No Hotspot or PPPoE packages found. The preview will include sample RouterOS profiles only.</div>
                        {/foreach}
                    </div>
                    <label class="fnp-provision-check">
                        <input type="checkbox" name="create_sample_user" value="yes" {if $settings.create_sample_user eq 'yes'}checked{/if}>
                        Create one sample PPPoE test user during provisioning
                    </label>
                </div>
            </section>

            <section id="step-portal" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <div class="fnp-provision-card-head">
                        <div>
                            <h3>Captive Portal Setup</h3>
                            <p>Pushes the MikroTik-hosted FASTNETPAY portal files without rerunning full provisioning.</p>
                        </div>
                        <button type="button" id="fnpProvisionRefreshPortal" class="btn btn-primary" {if !$router_id}disabled{/if}><i class="fa fa-refresh"></i> Refresh Captive Portal Files</button>
                    </div>
                    <div class="fnp-provision-radio-grid">
                        <label><input type="radio" name="portal_mode" value="static" {if $settings.portal_mode eq 'static'}checked{/if}> <span><b>MikroTik Hosted Portal</b><small>Router stores lightweight FASTNETPAY files and calls customer-safe payment APIs.</small></span></label>
                        <label><input type="radio" name="portal_mode" value="fastnetpay" {if $settings.portal_mode eq 'fastnetpay'}checked{/if}> <span><b>FASTNETPAY Hosted Portal</b><small>MikroTik redirects users to FASTNETPAY package/payment pages.</small></span></label>
                    </div>
                    <p class="fnp-provision-muted">Customer API: <code>{$app_url}/?_route=api/hotspot</code>. The generated MikroTik files do not redirect users to the admin dashboard.</p>
                    <div id="fnpProvisionPortalRefreshResult" class="fnp-provision-run-result"></div>
                </div>
            </section>

            <section id="step-payment" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <h3>Payment Settings</h3>
                    <div class="fnp-provision-grid">
                        <label>Default Payment Gateway
                            <select name="payment_gateway" class="form-control">
                                <option value="mpesastkpush" selected>MPESA STK Push</option>
                            </select>
                        </label>
                        <label>Shortcode / Paybill Label <input type="text" name="shortcode_label" class="form-control" value="{$settings.shortcode_label|escape}"></label>
                        <label>Account Reference Prefix <input type="text" name="account_reference_prefix" class="form-control" value="{$settings.account_reference_prefix|escape}"></label>
                        <label>Callback URL <input type="url" name="callback_url" class="form-control" value="{$settings.callback_url|escape}"></label>
                        <label>Support Phone <input type="text" name="support_phone" class="form-control" value="{$settings.support_phone|escape}"></label>
                        <label>WhatsApp Support Link <input type="text" name="support_whatsapp" class="form-control" value="{$settings.support_whatsapp|escape}" placeholder="https://wa.me/2547..."></label>
                    </div>
                </div>
            </section>

                <section id="step-security" class="fnp-provision-step-panel">
                    <div class="fnp-provision-card">
                        <h3>Security Setup</h3>
                        <div class="fnp-provision-callout">
                            <strong>Recommended for beginners:</strong> keep Default Local Setup while testing on-site. Use WireGuard or SSTP when this router will be managed remotely through VPN.
                        </div>
                        <h4 class="fnp-provision-subtitle">Connection Mode</h4>
                        <div class="fnp-provision-radio-grid">
                            <label><input type="radio" name="connection_mode" value="local" {if $settings.connection_mode eq 'local'}checked{/if}> <span><b>Default Local Setup</b><small>Use LAN/local router IP. No VPN required.</small></span></label>
                            <label><input type="radio" name="connection_mode" value="wireguard" {if $settings.connection_mode eq 'wireguard'}checked{/if}> <span><b>WireGuard VPN</b><small>Best for RouterOS v7 remote sites.</small></span></label>
                            <label><input type="radio" name="connection_mode" value="sstp" {if $settings.connection_mode eq 'sstp'}checked{/if}> <span><b>SSTP VPN</b><small>Fallback for older RouterOS v6 routers.</small></span></label>
                        </div>
                        <div class="fnp-provision-grid">
                            <label>FASTNETPAY/VPN Server IP <input type="text" name="vpn_server_ip" class="form-control" value="{$settings.vpn_server_ip|escape}" placeholder="10.100.0.1"></label>
                            <label>Router VPN IP <input type="text" name="vpn_router_ip" class="form-control" value="{$settings.vpn_router_ip|escape}" placeholder="10.100.1.1"></label>
                            <label>WireGuard Listen Port <input type="number" name="vpn_port" class="form-control" value="{$settings.vpn_port|escape}" min="1" max="65535"></label>
                            <label>WireGuard Endpoint Host <input type="text" name="wireguard_endpoint" class="form-control" value="{$settings.wireguard_endpoint|escape}" placeholder="vpn.fastnetpay.co.ke"><small class="fnp-provision-muted">Use DNS-only. Cloudflare proxied records cannot carry WireGuard UDP.</small></label>
                            <label>Secure Management URL <input type="url" name="secure_management_url" class="form-control" value="{$settings.secure_management_url|escape}" placeholder="https://fastnetpay.co.ke:3054"></label>
                            <label>WireGuard Router Private Key <input type="password" name="wireguard_private_key" class="form-control" value="{$settings.wireguard_private_key|escape}" autocomplete="new-password"></label>
                            <label>WireGuard FASTNETPAY Public Key <input type="text" name="wireguard_public_key" class="form-control" value="{$settings.wireguard_public_key|escape}"></label>
                            <label>SSTP Server <input type="text" name="sstp_server" class="form-control" value="{$settings.sstp_server|escape}" placeholder="sstp.fastnetpay.co.ke:4443"><small class="fnp-provision-muted">RouterOS v6 uses <code>host:port</code> in the server field. Keep this DNS-only, not Cloudflare proxied.</small></label>
                            <label>SSTP Username <input type="text" name="sstp_username" class="form-control" value="{$settings.sstp_username|escape}" autocomplete="off"></label>
                            <label>SSTP Password <input type="password" name="sstp_password" class="form-control" value="{$settings.sstp_password|escape}" autocomplete="new-password"></label>
                            <label>Future Tenant ID <input type="number" name="tenant_id" class="form-control" value="{$settings.tenant_id|escape}" min="0"><small class="fnp-provision-muted">Leave 0 in current single-tenant FASTNETPAY.</small></label>
                            <label>Future Site ID <input type="number" name="site_id" class="form-control" value="{$settings.site_id|escape}" min="0"></label>
                            <label>Router Group / Site Name <input type="text" name="router_group" class="form-control" value="{$settings.router_group|escape}" placeholder="Narok Main Site"></label>
                        </div>
                        <input type="hidden" name="api_restrict_mode" value="local">
                        <label class="fnp-provision-check">
                            <input type="checkbox" name="api_restrict_mode" value="vpn" {if $settings.api_restrict_mode eq 'vpn'}checked{/if}>
                            Restrict RouterOS API to the VPN server IP after VPN is tested
                        </label>
                        <h4 class="fnp-provision-subtitle">Firewall Profile</h4>
                        <div class="fnp-provision-radio-grid">
                        <label><input type="radio" name="security_level" value="basic" {if $settings.security_level eq 'basic'}checked{/if}> <span><b>Basic</b><small>Safer API access and invalid packet drops.</small></span></label>
                        <label><input type="radio" name="security_level" value="recommended" {if $settings.security_level eq 'recommended'}checked{/if}> <span><b>Recommended</b><small>DDoS/DoS guardrails, DNS abuse protection, port scan lists.</small></span></label>
                        <label><input type="radio" name="security_level" value="strict" {if $settings.security_level eq 'strict'}checked{/if}> <span><b>Strict ISP Mode</b><small>Restricts management services to FASTNETPAY server IP.</small></span></label>
                    </div>
                    <div class="fnp-provision-grid">
                        <label>Router-Facing FASTNETPAY Server IP <input type="text" name="fastnetpay_server_ip" class="form-control" value="{$settings.fastnetpay_server_ip|escape}" placeholder="10.100.0.1"><small class="fnp-provision-muted">For SSTP/WireGuard use <code>10.100.0.1</code>. Do not enter the Cloudflare/public IP here; the customer portal still uses <code>mother.fastnetpay.co.ke</code>.</small></label>
                        <label>RADIUS Shared Secret <input type="text" name="radius_secret" class="form-control" value="{$settings.radius_secret|escape}" autocomplete="off"></label>
                    </div>
                    <label>Custom Walled Garden Domains
                        <textarea name="custom_walled_garden" class="form-control" rows="4" placeholder="one domain per line">{$settings.custom_walled_garden|escape}</textarea>
                    </label>
                </div>
            </section>

            <section id="step-preview" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <div class="fnp-provision-card-head">
                        <div>
                            <h3>Preview Generated Configuration</h3>
                            <p>Review every RouterOS command before touching the router. Commands are grouped and commented with FASTNETPAY.</p>
                        </div>
                        <button type="button" id="fnpProvisionPreview" class="btn btn-primary"><i class="fa fa-code"></i> Preview Script</button>
                    </div>
                    <div id="fnpProvisionWarnings" class="fnp-provision-warnings"></div>
                    <pre id="fnpProvisionScript" class="fnp-provision-script">Click Preview Script to generate RouterOS commands.</pre>
                    <div class="fnp-provision-toolbar">
                        <button type="button" id="fnpProvisionCopy" class="btn btn-default"><i class="fa fa-copy"></i> Copy Commands</button>
                        <button type="button" id="fnpProvisionDownload" class="btn btn-default"><i class="fa fa-download"></i> Download Script</button>
                    </div>
                </div>
            </section>

            <section id="step-backup" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <h3>Backup Before Provisioning</h3>
                    <p>Automatic apply starts with <code>/export file=before_fastnetpay_...</code> and <code>/system backup save name=before_fastnetpay_...</code>. Backups remain in the MikroTik Files list for manual download.</p>
                    <div class="fnp-provision-callout">The wizard never resets the router, wipes existing config, removes users, or disables management before preview and backup.</div>
                    <label class="fnp-provision-check">
                        <input type="checkbox" name="allow_backup_override" value="yes" {if $settings.allow_backup_override eq 'yes'}checked{/if}>
                        Allow apply if automatic backup fails because I already have a manual MikroTik backup
                    </label>
                </div>
            </section>

            <section id="step-apply" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <div class="fnp-provision-card-head">
                        <div>
                            <h3>Apply Provisioning</h3>
                            <p>Applies grouped RouterOS scripts in safe batches and logs every step.</p>
                        </div>
                        <button type="button" id="fnpProvisionRun" class="btn btn-success" {if !$router_id}disabled{/if}><i class="fa fa-rocket"></i> Apply Automatically</button>
                    </div>
                    {if !$router_id}
                        <div class="fnp-provision-alert is-warning"><i class="fa fa-info-circle"></i> Save this router first, then run automatic apply from the router list/edit page. Draft mode can still preview and download scripts.</div>
                    {/if}
                    <div id="fnpProvisionApplyResult" class="fnp-provision-run-result"></div>
                </div>
            </section>

            <section id="step-final" class="fnp-provision-step-panel">
                <div class="fnp-provision-card">
                    <div class="fnp-provision-card-head">
                        <div>
                            <h3>Final Live Results</h3>
                            <p>Reads the router after provisioning and shows what is actually configured.</p>
                        </div>
                        <button type="button" id="fnpProvisionFinalTest" class="btn btn-primary" {if !$router_id}disabled{/if}><i class="fa fa-check-circle"></i> Run Live Test</button>
                    </div>
                    <div id="fnpProvisionFinalResult" class="fnp-provision-final-result">
                        <div class="fnp-provision-alert is-warning"><i class="fa fa-info-circle"></i> Apply provisioning or click Run Live Test to see API, bridge, Hotspot, portal, PPPoE, NAT, MPESA, and package status.</div>
                    </div>
                    <p class="fnp-provision-muted">After applying, connect a test client, open the captive portal, buy a package through MPESA STK Push, and confirm FASTNETPAY activates the user on MikroTik.</p>
                </div>
            </section>

            <div class="fnp-provision-footer-actions">
                <button type="button" id="fnpProvisionPrev" class="btn btn-default"><i class="fa fa-arrow-left"></i> Previous</button>
                <button type="button" id="fnpProvisionNext" class="btn btn-primary">Next <i class="fa fa-arrow-right"></i></button>
            </div>
        </main>
    </form>
</div>

{include file="sections/footer.tpl"}
