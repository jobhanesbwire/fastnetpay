{include file="sections/header.tpl"}

<div class="fnp-monitor-page" data-monitor-mode="dashboard" data-router-id="{$router}" data-base-url="{$_url}" data-csrf="{$csrf_token}">
    <div class="fnp-monitor-head">
        <div>
            <span class="fnp-report-kicker"><i class="ion ion-wifi"></i> MikroTik Operations</span>
            <h2>Mikrotik Dashboard</h2>
            <p>Router health, live sessions, traffic, and FASTNETPAY client counts in one operations view.</p>
        </div>
        <div class="fnp-monitor-actions">
            <button type="button" class="btn btn-default btn-sm" data-monitor-refresh>
                <i class="fa fa-refresh"></i> Refresh
            </button>
            <a href="{Text::url('routers')}" class="btn btn-primary btn-sm">
                <i class="fa fa-server"></i> Routers
            </a>
        </div>
    </div>

    <div class="fnp-router-tabs">
        {foreach $routers as $r}
            <a href="{Text::url('plugin/mikrotik_monitor_ui/', $r['id'])}"
                class="fnp-router-tab {if $r['id'] == $router}is-active{/if}">
                <i class="ion ion-wifi"></i>
                <span>{Lang::htmlspecialchars($r['name'])}</span>
                <b class="{if $r['status'] eq 'Online'}is-online{else}is-offline{/if}"></b>
            </a>
        {foreachelse}
            <span class="fnp-router-tab is-empty">No enabled routers configured</span>
        {/foreach}
    </div>

    <div class="fnp-monitor-alert" data-monitor-error style="display:none;"></div>

    <div class="fnp-monitor-grid fnp-monitor-grid-6">
        <div class="fnp-monitor-card">
            <span>Routers Online</span>
            <strong data-stat="online_router_count">0</strong>
            <small data-stat="router_count">0 total routers</small>
            <i class="fa fa-server"></i>
        </div>
        <div class="fnp-monitor-card is-green">
            <span>Active Hotspot</span>
            <strong data-stat="active_hotspot">0</strong>
            <small data-stat="local_hotspot_clients">0 local clients</small>
            <i class="fa fa-wifi"></i>
        </div>
        <div class="fnp-monitor-card is-gold">
            <span>Active PPPoE</span>
            <strong data-stat="active_pppoe">0</strong>
            <small data-stat="local_pppoe_clients">0 local clients</small>
            <i class="fa fa-plug"></i>
        </div>
        <div class="fnp-monitor-card is-blue">
            <span>Total Traffic</span>
            <strong data-stat="total_traffic">0 B</strong>
            <small>Across interfaces</small>
            <i class="fa fa-exchange"></i>
        </div>
        <div class="fnp-monitor-card">
            <span>Hotspot Servers</span>
            <strong data-stat="hotspot_servers">0</strong>
            <small data-stat="total_hotspot">0 hotspot users</small>
            <i class="fa fa-bullseye"></i>
        </div>
        <div class="fnp-monitor-card">
            <span>Router CPU</span>
            <strong data-stat="cpu_load">0%</strong>
            <small data-stat="uptime">uptime unavailable</small>
            <i class="fa fa-microchip"></i>
        </div>
    </div>

    <div class="row fnp-monitor-row">
        <div class="col-md-8">
            <div class="box box-primary box-solid fnp-monitor-panel">
                <div class="box-header">
                    <h3 class="box-title"><i class="fa fa-line-chart"></i> Live Interface Traffic</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="fnp-monitor-toolbar">
                        <select class="form-control" data-interface-select></select>
                        <span class="fnp-live-chip">TX <b data-traffic-tx>0 B</b></span>
                        <span class="fnp-live-chip">RX <b data-traffic-rx>0 B</b></span>
                    </div>
                    <div class="fnp-monitor-chart-wrap">
                        <canvas data-traffic-chart></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-primary box-solid fnp-monitor-panel">
                <div class="box-header">
                    <h3 class="box-title"><i class="fa fa-list"></i> Interfaces</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="fnp-interface-list" data-interface-list>
                        <div class="fnp-empty-row">Loading interfaces...</div>
                    </div>
                </div>
            </div>
            <div class="box box-primary box-solid fnp-monitor-panel">
                <div class="box-header">
                    <h3 class="box-title"><i class="fa fa-info-circle"></i> Router Info</h3>
                </div>
                <div class="box-body">
                    <dl class="fnp-router-info">
                        <dt>Version</dt><dd data-stat="version">N/A</dd>
                        <dt>Free Memory</dt><dd data-stat="free_memory">N/A</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
