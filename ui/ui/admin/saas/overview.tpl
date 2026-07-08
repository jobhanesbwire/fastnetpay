{include file="sections/header.tpl"}

<div class="fnp-saas-page fnp-saas-overview">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Tenants</span>
                <b>{$analytics.tenants.total}</b>
                <small>{$analytics.tenants.active} active · {$analytics.tenants.suspended} suspended</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Routers</span>
                <b>{$analytics.routers.total}</b>
                <small>{$analytics.routers.online} online · {$analytics.routers.offline} offline</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Active Clients</span>
                <b>{$analytics.clients.total}</b>
                <small>{$analytics.clients.hotspot} hotspot · {$analytics.clients.pppoe} PPPoE</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Unpaid SaaS</span>
                <b>Ksh {$analytics.financial.unpaid|string_format:"%.2f"}</b>
                <small>Ksh {$analytics.financial.overdue|string_format:"%.2f"} overdue</small>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">
                {if $view_mode eq 'admins'}Tenant Administrators
                {elseif $view_mode eq 'routers'}Tenant Router Overview
                {elseif $view_mode eq 'payments'}Tenant Payment Overview
                {elseif $view_mode eq 'health'}Tenant Health / Status
                {else}SaaS Overview{/if}
            </h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover fnp-compact-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Status</th>
                        <th>Admins</th>
                        <th>Routers</th>
                        <th>Clients</th>
                        <th>Plans</th>
                        <th>Payment Gateways</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $tenants as $row}
                        <tr>
                            <td>
                                <strong>{$row.tenant.name|escape}</strong><br>
                                <small>{$row.tenant.slug|escape} · {$row.tenant.subdomain|escape}</small>
                            </td>
                            <td><span class="label label-{if $row.tenant.status eq 'active'}success{elseif $row.tenant.status eq 'suspended'}danger{else}warning{/if}">{$row.tenant.status|escape}</span></td>
                            <td>{$row.admins}</td>
                            <td>{$row.routers}</td>
                            <td>{$row.clients}</td>
                            <td>{$row.plans}</td>
                            <td>{$row.payments}</td>
                            <td class="text-right">
                                <a class="btn btn-info btn-sm" href="{Text::url('saas/edit/')}{$row.tenant.id}"><i class="fa fa-pencil"></i></a>
                                <a class="btn btn-warning btn-sm" href="{Text::url('saas/billing')}"><i class="fa fa-credit-card"></i></a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-line-chart"></i> Top Tenants By Active Users</h3>
        </div>
        <div class="box-body">
            <div class="row">
                {foreach $analytics.top_tenants as $tenantRow}
                    <div class="col-md-3 col-sm-6">
                        <div class="fnp-saas-card fnp-mini-tenant-card">
                            <strong>{$tenantRow.tenant.name|escape}</strong>
                            <span>{$tenantRow.users} users</span>
                            <small>{$tenantRow.hotspot} hotspot · {$tenantRow.pppoe} PPPoE</small>
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
