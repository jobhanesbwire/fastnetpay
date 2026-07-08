{include file="sections/header.tpl"}

<div class="row fnp-saas-page">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-building"></i> SaaS Tenants / ISPs</h3>
                <div class="box-tools">
                    <a href="{Text::url('saas/add')}" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Add Tenant</a>
                    <a href="{Text::url('saas/domains')}" class="btn btn-default btn-sm"><i class="fa fa-globe"></i> Domains</a>
                    <a href="{Text::url('saas/audit')}" class="btn btn-default btn-sm"><i class="fa fa-shield"></i> Audit Logs</a>
                </div>
            </div>
            <div class="box-body">
                <div class="alert alert-info">
                    <strong>Wildcard DNS:</strong> point <code>*.{$base_domain}</code> to the FASTNETPAY server. The main domain remains SuperAdmin mode while tenant subdomains resolve into tenant mode.
                </div>

                <div class="row">
                    {foreach $tenants as $row}
                        {assign var=tenant value=$row.tenant}
                        <div class="col-md-6 col-lg-4">
                            <div class="fnp-saas-card">
                                <div class="fnp-saas-card-head">
                                    <span class="fnp-saas-logo" style="background: {$tenant.primary_color|default:'#41a146'}"></span>
                                    <div>
                                        <h4>{$tenant.name|escape}</h4>
                                        <p><code>{$tenant.subdomain|escape}.{$base_domain}</code></p>
                                    </div>
                                    <span class="label label-{if $tenant.status eq 'active'}success{elseif $tenant.status eq 'suspended'}danger{else}warning{/if}">{$tenant.status|escape}</span>
                                </div>
                                <div class="fnp-saas-stats">
                                    <span><b>{$row.routers}</b> Routers</span>
                                    <span><b>{$row.clients}</b> Clients</span>
                                    <span><b>{$row.plans}</b> Plans</span>
                                    <span><b>{$row.payments}</b> Payments</span>
                                    <span><b>{$row.admins}</b> Admins</span>
                                </div>
                                <div class="fnp-saas-meta">
                                    <span><i class="fa fa-credit-card"></i> {$tenant.subscription_plan|escape} / {$tenant.subscription_status|escape}</span>
                                    <span><i class="fa fa-envelope"></i> {$tenant.contact_email|default:'No contact email'|escape}</span>
                                </div>
                                <div class="fnp-saas-actions">
                                    <a href="{Text::url('saas/edit/', $tenant.id)}" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> Manage</a>
                                    {if $tenant.status eq 'suspended'}
                                        <a href="{Text::url('saas/activate/', $tenant.id)}" class="btn btn-success btn-sm"><i class="fa fa-play"></i> Activate</a>
                                    {else}
                                        <a href="{Text::url('saas/suspend/', $tenant.id)}" class="btn btn-warning btn-sm" onclick="return ask(this, 'Suspend this tenant?')"><i class="fa fa-pause"></i> Suspend</a>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
