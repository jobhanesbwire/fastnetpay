{include file="sections/header.tpl"}

<div class="fnp-monitor-page" data-monitor-mode="hotspot" data-router-id="{$router}" data-base-url="{$_url}" data-csrf="{$csrf_token}">
    <div class="fnp-monitor-head">
        <div>
            <span class="fnp-report-kicker"><i class="fa fa-wifi"></i> MikroTik Hotspot</span>
            <h2>Hotspot Monitor</h2>
            <p>Monitor hotspot users, sessions, traffic, profiles, servers, and customer status across routers.</p>
        </div>
        <div class="fnp-monitor-actions">
            <button type="button" class="btn btn-default btn-sm" data-monitor-refresh><i class="fa fa-refresh"></i> Refresh</button>
            <label class="fnp-autorefresh"><input type="checkbox" data-auto-refresh checked> Auto refresh</label>
        </div>
    </div>

    <div class="fnp-router-tabs">
        {foreach $routers as $r}
            <a href="{Text::url('plugin/mikrotik_monitor_hotspot/', $r['id'])}"
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

    <div class="fnp-monitor-grid fnp-monitor-grid-5">
        <div class="fnp-monitor-card is-green"><span>Online Users</span><strong data-stat="online">0</strong><small>Active sessions</small><i class="fa fa-check-circle"></i></div>
        <div class="fnp-monitor-card"><span>Offline Users</span><strong data-stat="offline">0</strong><small>Known users</small><i class="fa fa-power-off"></i></div>
        <div class="fnp-monitor-card is-blue"><span>Total Users</span><strong data-stat="total">0</strong><small>Router/local records</small><i class="fa fa-users"></i></div>
        <div class="fnp-monitor-card is-gold"><span>Total Traffic</span><strong data-stat="total_traffic">0 B</strong><small>Current sessions</small><i class="fa fa-exchange"></i></div>
        <div class="fnp-monitor-card"><span>Hotspot Servers</span><strong data-stat="servers">0</strong><small>Router servers</small><i class="fa fa-bullseye"></i></div>
    </div>

    <div class="box box-primary box-solid fnp-monitor-panel">
        <div class="box-header">
            <h3 class="box-title"><i class="fa fa-line-chart"></i> Live Traffic</h3>
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

    <div class="box box-primary box-solid fnp-monitor-panel">
        <div class="box-header">
            <h3 class="box-title"><i class="fa fa-list"></i> Hotspot Users</h3>
        </div>
        <div class="box-body">
            <div class="fnp-monitor-toolbar">
                <input type="search" class="form-control" data-monitor-search placeholder="Search hotspot user, IP, MAC, profile, server">
                <select class="form-control" data-status-filter>
                    <option value="all">All statuses</option>
                    <option value="online">Online</option>
                    <option value="offline">Offline</option>
                    <option value="disabled">Disabled</option>
                </select>
                <select class="form-control" data-profile-filter><option value="all">All profiles</option></select>
                <button type="button" class="btn btn-default" data-export-csv><i class="fa fa-download"></i> CSV</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-condensed fnp-monitor-table" data-monitor-table>
                    <thead>
                        <tr>
                            <th data-sort="index">#</th>
                            <th data-sort="username">User</th>
                            <th data-sort="address">IP Address</th>
                            <th data-sort="mac">MAC Address</th>
                            <th data-sort="uptime">Uptime</th>
                            <th data-sort="rx">Data In</th>
                            <th data-sort="tx">Data Out</th>
                            <th data-sort="total">Total Usage</th>
                            <th data-sort="profile">Profile</th>
                            <th data-sort="server">Server</th>
                            <th data-sort="status">Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody data-monitor-rows>
                        <tr><td colspan="12" class="text-center text-muted fnp-empty-row">Loading hotspot users...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {include file="mikrotik_session_modal.tpl"}
</div>

{include file="sections/footer.tpl"}
