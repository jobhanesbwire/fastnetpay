{include file="sections/header.tpl"}

<div class="fnp-provision-page">
    <div class="fnp-provision-hero">
        <div>
            <span class="fnp-provision-eyebrow"><i class="fa fa-history"></i> Provisioning Logs</span>
            <h2>{if $router}{$router['name']|escape}{else}All Router Provisioning Runs{/if}</h2>
            <p>Audit trail for FASTNETPAY router backups, generated command groups, execution status, and provisioning errors.</p>
        </div>
        <div class="fnp-provision-hero-actions">
            <a href="{Text::url('routers/list')}" class="btn btn-default"><i class="fa fa-list"></i> Routers</a>
            <a href="{Text::url('routers/provision/', $router_id)}" class="btn btn-primary"><i class="fa fa-magic"></i> Run Wizard</a>
        </div>
    </div>

    <div class="fnp-provision-card">
        <div class="table-responsive fnp-provision-log-table">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Run</th>
                        <th>Profile</th>
                        <th>RouterOS</th>
                        <th>Status</th>
                        <th>Backup</th>
                        <th>Started</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $runs_with_steps as $item}
                        {assign var=run value=$item.run}
                        <tr>
                            <td>#{$run['id']}</td>
                            <td>{$run['deployment_profile']|escape}</td>
                            <td>{$run['routeros_version']|escape}</td>
                            <td><span class="fnp-provision-status is-{$run['status']|escape}">{$run['status']|escape}</span></td>
                            <td>{if $run['backup_file']}{$run['backup_file']|escape}{else}<span class="text-muted">Not saved</span>{/if}</td>
                            <td>{$run['started_at']|escape}</td>
                            <td>{$run['completed_at']|escape}</td>
                        </tr>
                        <tr class="fnp-provision-step-row">
                            <td colspan="7">
                                <div class="fnp-provision-log-steps">
                                    {foreach $item.steps as $step}
                                        <div class="fnp-provision-log-step">
                                            <span class="fnp-provision-status is-{$step['status']|escape}">{$step['status']|escape}</span>
                                            <strong>{$step['step_name']|escape}</strong>
                                            {if $step['error_message']}
                                                <small class="text-danger">{$step['error_message']|escape}</small>
                                            {else}
                                                <small>{$step['completed_at']|escape}</small>
                                            {/if}
                                        </div>
                                    {foreachelse}
                                        <span class="text-muted">No step records for this run.</span>
                                    {/foreach}
                                </div>
                            </td>
                        </tr>
                    {foreachelse}
                        <tr>
                            <td colspan="7">
                                <div class="fnp-provision-empty">No provisioning runs have been logged yet.</div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
