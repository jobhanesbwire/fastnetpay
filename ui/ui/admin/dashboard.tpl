{include file="sections/header.tpl"}

{function showWidget pos=0}
    {foreach $widgets as $w}
        {if $w['position'] == $pos}
            {$w['content']}
        {/if}
    {/foreach}
{/function}

{assign dtipe value="dashboard_`$tipeUser`"}

<div class="fastnetpay-dashboard">
    <section class="fnp-dashboard-hero">
        <div class="fnp-dashboard-hero-copy">
            <span class="fnp-dashboard-kicker"><i class="fa fa-wifi"></i> FASTNETPAY</span>
            <h1>Network Overview</h1>
            <p>{Lang::dateFormat($start_date)} - {Lang::dateFormat($current_date)}</p>
        </div>
        <div class="fnp-dashboard-actions">
            {if !in_array($_admin['user_type'],['Report'])}
                <a href="{Text::url('customers/add')}" class="fnp-dashboard-action">
                    <i class="fa fa-user-plus"></i>
                    <span>{Lang::T('Add Customer')}</span>
                </a>
                <a href="{Text::url('plan/recharge')}" class="fnp-dashboard-action">
                    <i class="fa fa-bolt"></i>
                    <span>{Lang::T('Recharge Customer')}</span>
                </a>
            {/if}
            <a href="{Text::url('reports')}" class="fnp-dashboard-action">
                <i class="fa fa-line-chart"></i>
                <span>{Lang::T('Reports')}</span>
            </a>
            <a href="{Text::url('dashboard&refresh')}" class="fnp-dashboard-action">
                <i class="fa fa-refresh"></i>
                <span>{Lang::T('Refresh')}</span>
            </a>
        </div>
        </section>

        {if isset($expiry_health) && !$expiry_health.ok}
            <div class="fnp-dashboard-expiry-alert">
                <i class="fa fa-exclamation-triangle"></i>
                <div>
                    <strong>Cron/Expiry Worker not running</strong>
                    <span>{$expiry_health.message|escape} Last success: {if $expiry_health.last_success}{$expiry_health.last_success|escape}{else}never{/if}.</span>
                </div>
                <a href="{Text::url('expiry/status')}" class="btn btn-warning btn-sm">Check Worker</a>
            </div>
        {/if}

        {if isset($saas_analytics)}
            <section class="fnp-saas-dashboard-strip">
                <div class="fnp-saas-strip-head">
                    <div>
                        <span class="fnp-dashboard-kicker"><i class="fa fa-sitemap"></i> SaaS Control</span>
                        <h2>Tenant Billing & Operations</h2>
                    </div>
                    <a href="{Text::url('saas/billing')}" class="btn btn-success btn-sm"><i class="fa fa-credit-card"></i> Manage Billing</a>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="fnp-saas-card fnp-saas-kpi">
                            <span>Tenants</span>
                            <b>{$saas_analytics.tenants.total}</b>
                            <small>{$saas_analytics.tenants.active} active · {$saas_analytics.tenants.suspended} suspended</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="fnp-saas-card fnp-saas-kpi">
                            <span>Router Health</span>
                            <b>{$saas_analytics.routers.online}/{$saas_analytics.routers.total}</b>
                            <small>{$saas_analytics.routers.offline} offline router(s)</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="fnp-saas-card fnp-saas-kpi">
                            <span>Active ISP Users</span>
                            <b>{$saas_analytics.clients.total}</b>
                            <small>{$saas_analytics.clients.hotspot} hotspot · {$saas_analytics.clients.pppoe} PPPoE</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="fnp-saas-card fnp-saas-kpi">
                            <span>Expected SaaS Revenue</span>
                            <b>Ksh {$saas_analytics.financial.expected|string_format:"%.2f"}</b>
                            <small>Ksh {$saas_analytics.financial.overdue|string_format:"%.2f"} overdue</small>
                        </div>
                    </div>
                </div>
            </section>
        {elseif isset($saas_analytics_error)}
            <div class="alert alert-warning">SaaS analytics could not load: {$saas_analytics_error|escape}</div>
        {/if}

        {if isset($ops_analytics)}
            <section class="fnp-saas-dashboard-strip">
                <div class="fnp-saas-strip-head">
                    <div>
                        <span class="fnp-dashboard-kicker"><i class="fa fa-briefcase"></i> Operations</span>
                        <h2>Counter, Support & Device Signals</h2>
                    </div>
                    <a href="{Text::url('pos/dashboard')}" class="btn btn-success btn-sm"><i class="fa fa-shopping-cart"></i> POS</a>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="fnp-saas-card fnp-saas-kpi">
                            <span>POS Today</span>
                            <b>{Lang::moneyFormat($ops_analytics.pos_today)}</b>
                            <small>{Lang::moneyFormat($ops_analytics.pos_month)} this month</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="fnp-saas-card fnp-saas-kpi">
                            <span>Open Tickets</span>
                            <b>{$ops_analytics.open_tickets}</b>
                            <small>{$ops_analytics.urgent_tickets} urgent ticket(s)</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="fnp-saas-card fnp-saas-kpi">
                            <span>ACS Devices</span>
                            <b>{$ops_analytics.acs_devices}</b>
                            <small>customer CPE registry</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="fnp-saas-card fnp-saas-kpi">
                            <span>PPPoE Wallets</span>
                            <b>{Lang::moneyFormat($ops_analytics.pppoe_balance)}</b>
                            <small>total customer balance</small>
                        </div>
                    </div>
                </div>
            </section>
        {/if}

        {assign rows explode(".", $_c[$dtipe])}
    {assign pos 1}
    {foreach $rows as $cols}
        {if $cols == 12}
            <div class="row fnp-dashboard-row">
                <div class="col-md-12 fnp-dashboard-col">
                    {showWidget widgets=$widgets pos=$pos}
                </div>
            </div>
            {assign pos value=$pos+1}
        {else}
            {assign colss explode(",", $cols)}
            <div class="row fnp-dashboard-row">
                {foreach $colss as $c}
                    <div class="col-md-{$c} fnp-dashboard-col">
                        {showWidget widgets=$widgets pos=$pos}
                    </div>
                    {assign pos value=$pos+1}
                {/foreach}
            </div>
        {/if}
    {/foreach}
</div>

{if $_app_stage != 'Live' && $_c['new_version_notify'] != 'disable' && !($_tenant_mode eq 'tenant' && $_admin['user_type'] neq 'SuperAdmin')}
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            $.getJSON("./version.json?" + Math.random(), function(data) {
                var localVersion = data.version;
                $('#version').html('Version: ' + localVersion);
                $.getJSON(
                    "https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?" +
                    Math
                    .random(),
                    function(data) {
                        var latestVersion = data.version;
                        if (localVersion !== latestVersion) {
                            $('#version').html('Latest Version: ' + latestVersion);
                            if (getCookie(latestVersion) != 'done') {
                                Swal.fire({
                                    icon: 'info',
                                    title: "New Version Available\nVersion: " + latestVersion,
                                    toast: true,
                                    position: 'bottom-right',
                                    showConfirmButton: true,
                                    showCloseButton: true,
                                    timer: 30000,
                                    confirmButtonText: '<a href="{Text::url('community')}#latestVersion" style="color: white;">Update Now</a>',
                                    timerProgressBar: true,
                                    didOpen: (toast) => {
                                        toast.addEventListener('mouseenter', Swal.stopTimer)
                                        toast.addEventListener('mouseleave', Swal
                                            .resumeTimer)
                                    }
                                });
                                setCookie(latestVersion, 'done', 7);
                            }
                        }
                    });
            });

        });
    </script>
{/if}

{include file="sections/footer.tpl"}
