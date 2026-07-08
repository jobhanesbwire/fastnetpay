{include file="sections/header.tpl"}

<div class="fnp-expiry-page">
    <div class="fnp-provision-hero">
        <div>
            <span class="fnp-provision-eyebrow"><i class="fa fa-clock-o"></i> FASTNETPAY Expiry Automation</span>
            <h2>Expired Client Auto-Disconnect</h2>
            <p>Confirms cron health, disconnects expired Hotspot and PPPoE sessions, and records clear audit logs.</p>
        </div>
        <form method="post" action="{Text::url('expiry/run')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <button type="submit" class="btn btn-primary"><i class="fa fa-play"></i> Run Expiry Check Now</button>
        </form>
    </div>

    <div class="fnp-provision-alert {if $health.ok}is-ready{else}is-warning{/if}">
        <i class="fa {if $health.ok}fa-check-circle{else}fa-exclamation-triangle{/if}"></i>
        <div>
            <strong>{$health.message|escape}</strong>
            <span>Last success: {if $health.last_success}{$health.last_success|escape}{else}Never recorded{/if}. Cron file: {if $health.cron_file_time}{$health.cron_file_time|escape}{else}missing{/if}.</span>
        </div>
    </div>

    <div class="fnp-expiry-grid">
        <section class="fnp-provision-card">
            <h3>Recent Runs</h3>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Found</th>
                            <th>Disconnected</th>
                            <th>Failed</th>
                            <th>Started</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $runs as $run}
                            <tr>
                                <td>{$run.id}</td>
                                <td>{$run.run_type|escape}</td>
                                <td><span class="fnp-provision-status is-{$run.status|escape}">{$run.status|escape}</span></td>
                                <td>{$run.expired_found}</td>
                                <td>{$run.disconnected_count}</td>
                                <td>{$run.failed_count}</td>
                                <td>{$run.started_at|escape}</td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="7" class="text-muted">No expiry worker runs recorded yet.</td></tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </section>

        <section class="fnp-provision-card">
            <h3>Disconnect Logs</h3>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Router</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $logs as $log}
                            <tr>
                                <td>{$log.created_at|escape}</td>
                                <td>{$log.username|escape}</td>
                                <td>{$log.router_name|escape}</td>
                                <td>{$log.service_type|escape}</td>
                                <td><span class="fnp-provision-status is-{$log.status|escape}">{$log.status|escape}</span></td>
                                <td>{$log.message|escape}</td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="6" class="text-muted">No disconnect logs yet.</td></tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

{include file="sections/footer.tpl"}
