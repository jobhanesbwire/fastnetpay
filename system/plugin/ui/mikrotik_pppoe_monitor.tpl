{include file="sections/header.tpl"}

<div class="fnp-monitor-page" data-monitor-mode="pppoe" data-router-id="{$router}" data-base-url="{$_url}" data-csrf="{$csrf_token}">
    <div class="fnp-monitor-head">
        <div>
            <span class="fnp-report-kicker"><i class="fa fa-plug"></i> MikroTik PPPoE</span>
            <h2>PPPoE Monitor</h2>
            <p>Track active PPPoE sessions, traffic, uptime, profiles, and disconnect live users when needed.</p>
        </div>
        <div class="fnp-monitor-actions">
            <button type="button" class="btn btn-default btn-sm" data-monitor-refresh><i class="fa fa-refresh"></i> Refresh</button>
            <label class="fnp-autorefresh"><input type="checkbox" data-auto-refresh checked> Auto refresh</label>
        </div>
    </div>

    <div class="fnp-router-tabs">
        {foreach $routers as $r}
            <a href="{Text::url('plugin/mikrotik_monitor_pppoe/', $r['id'])}"
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

    <div class="fnp-monitor-grid fnp-monitor-grid-4">
        <div class="fnp-monitor-card is-green"><span>Online PPPoE</span><strong data-stat="online">0</strong><small>Active sessions</small><i class="fa fa-check-circle"></i></div>
        <div class="fnp-monitor-card"><span>Offline PPPoE</span><strong data-stat="offline">0</strong><small>Known secrets</small><i class="fa fa-power-off"></i></div>
        <div class="fnp-monitor-card is-blue"><span>Total PPPoE</span><strong data-stat="total">0</strong><small>Router/local records</small><i class="fa fa-users"></i></div>
        <div class="fnp-monitor-card is-danger"><span>Disabled</span><strong data-stat="disabled">0</strong><small>Disabled secrets</small><i class="fa fa-ban"></i></div>
    </div>

    <div class="box box-primary box-solid fnp-monitor-panel">
        <div class="box-header">
            <h3 class="box-title"><i class="fa fa-list"></i> PPPoE Sessions</h3>
        </div>
        <div class="box-body">
            <div class="fnp-monitor-toolbar">
                <input type="search" class="form-control" data-monitor-search placeholder="Search PPPoE user, IP, caller ID, profile">
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
                            <th data-sort="username">User</th>
                            <th data-sort="address">IP Address</th>
                            <th data-sort="uptime">Uptime</th>
                            <th data-sort="service">Service</th>
                            <th data-sort="profile">Profile</th>
                            <th data-sort="caller_id">Caller ID</th>
                            <th data-sort="rx">Data In</th>
                            <th data-sort="tx">Data Out</th>
                            <th data-sort="total">Total Usage</th>
                            <th data-sort="status">Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody data-monitor-rows>
                        <tr><td colspan="11" class="text-center text-muted fnp-empty-row">Loading PPPoE sessions...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {include file="mikrotik_session_modal.tpl"}
</div>

{include file="sections/footer.tpl"}
