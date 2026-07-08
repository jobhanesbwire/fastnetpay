{include file="sections/header.tpl"}

<div class="box box-primary fnp-saas-page">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-shield"></i> SaaS Audit Logs</h3>
        <div class="box-tools">
            <a href="{Text::url('saas/tenants')}" class="btn btn-default btn-sm">Back to Tenants</a>
        </div>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Tenant</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Resource</th>
                        <th>IP</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $logs as $log}
                        <tr>
                            <td>{$log.created_at|escape}</td>
                            <td>{$log.tenant_id|escape}</td>
                            <td>{$log.admin_id|escape}</td>
                            <td><code>{$log.action|escape}</code></td>
                            <td>{$log.resource_type|escape} {$log.resource_id|escape}</td>
                            <td>{$log.ip|escape}</td>
                            <td>{$log.message|escape}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
