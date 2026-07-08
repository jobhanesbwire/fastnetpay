{include file="sections/header.tpl"}

<div class="box box-primary fnp-saas-page">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-globe"></i> Tenant Domains</h3>
        <div class="box-tools">
            <a href="{Text::url('saas/tenants')}" class="btn btn-default btn-sm">Back to Tenants</a>
        </div>
    </div>
    <div class="box-body">
        <div class="alert alert-warning">
            Configure wildcard DNS <code>*.fastnetpay.co.ke</code> and a wildcard SSL certificate for tenant subdomains. Custom domains require their own DNS and SSL validation.
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Tenant ID</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>SSL</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $domains as $domain}
                        <tr>
                            <td><code>{$domain.domain|escape}</code></td>
                            <td>{$domain.tenant_id}</td>
                            <td>{$domain.domain_type|escape}</td>
                            <td><span class="label label-{if $domain.status eq 'active'}success{elseif $domain.status eq 'failed'}danger{else}warning{/if}">{$domain.status|escape}</span></td>
                            <td>{$domain.ssl_status|default:'pending'|escape}</td>
                            <td>{$domain.updated_at|escape}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
