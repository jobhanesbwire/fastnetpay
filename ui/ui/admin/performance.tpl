{include file="sections/header.tpl"}

<div class="fnp-saas-page">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Samples 24h</span>
                <b>{$summary.samples}</b>
                <small>Development profiler records</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Average Route</span>
                <b>{$summary.avg_ms|string_format:"%.0f"}ms</b>
                <small>Max {$summary.max_ms|string_format:"%.0f"}ms</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Average Queries</span>
                <b>{$summary.avg_queries|string_format:"%.1f"}</b>
                <small>Max {$summary.max_queries}</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Cron Health</span>
                <b>{$cron_health.expiry_worker|escape}</b>
                <small>{$cron_health.last_run|escape}</small>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-tachometer"></i> Slow Routes 24h</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover fnp-compact-table">
                <thead><tr><th>Route</th><th>Hits</th><th>Avg Time</th><th>Max Time</th><th>Avg Queries</th></tr></thead>
                <tbody>
                    {foreach $summary.slow_routes as $row}
                        <tr>
                            <td><code>{$row.route|escape}</code></td>
                            <td>{$row.hits}</td>
                            <td>{$row.avg_ms|string_format:"%.0f"}ms</td>
                            <td>{$row.max_ms|string_format:"%.0f"}ms</td>
                            <td>{$row.avg_queries|string_format:"%.1f"}</td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="5" class="text-center text-muted">No profiler samples yet. Visit pages in development mode to collect data.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-database"></i> Schema Cache</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-striped fnp-compact-table">
                        <thead><tr><th>Area</th><th>Version</th><th>Last Checked</th></tr></thead>
                        <tbody>
                            {foreach $cache_status as $row}
                                <tr><td>{$row.key|escape}</td><td><code>{$row.version|escape}</code></td><td>{$row.checked_at|escape}</td></tr>
                            {/foreach}
                        </tbody>
                    </table>
                    <p class="help-block">Use <code>&refresh_schema=1</code> once after manual database changes to force schema checks.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list"></i> Index Health</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-striped fnp-compact-table">
                        <thead><tr><th>Table</th><th>Index</th><th>Status</th></tr></thead>
                        <tbody>
                            {foreach $index_warnings as $row}
                                <tr>
                                    <td>{$row.table|escape}</td>
                                    <td><code>{$row.index|escape}</code></td>
                                    <td><span class="label label-{if $row.exists}success{else}danger{/if}">{if $row.exists}OK{else}Missing{/if}</span></td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-history"></i> Recent Route Samples</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-hover fnp-compact-table">
                <thead>
                    <tr>
                        <th>Time</th><th>Route</th><th>Status</th><th>Total</th><th>Queries</th><th>Slow</th><th>Memory</th><th>Files</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $samples as $sample}
                        <tr>
                            <td>{$sample.created_at|escape}</td>
                            <td><code>{$sample.route|escape}</code></td>
                            <td>{$sample.status_code}</td>
                            <td>{$sample.total_ms|string_format:"%.0f"}ms</td>
                            <td>{$sample.query_count}</td>
                            <td>{$sample.slow_query_count}</td>
                            <td>{$sample.memory_mb|string_format:"%.1f"}MB</td>
                            <td>{$sample.included_files}</td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="8" class="text-center text-muted">No samples yet.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-file-code-o"></i> Asset Size Notes</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-striped fnp-compact-table">
                <thead><tr><th>Asset</th><th>Path</th><th>Size</th></tr></thead>
                <tbody>
                    {foreach $asset_notes as $asset}
                        <tr><td>{$asset.label|escape}</td><td><code>{$asset.path|escape}</code></td><td>{$asset.kb|string_format:"%.1f"} KB</td></tr>
                    {/foreach}
                </tbody>
            </table>
            <p class="help-block">Chart.js is no longer loaded globally; reports load it on-demand and dashboards/monitor pages keep it available.</p>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
