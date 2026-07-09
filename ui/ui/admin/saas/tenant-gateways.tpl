{include file="sections/header.tpl"}

<div class="fnp-saas-page">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-bank"></i> Tenant Payment Gateways</h3>
            <div class="box-tools pull-right">
                <a href="{Text::url('saas/tenant-gateway-add')}" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Add Gateway</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover fnp-compact-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Gateway</th>
                        <th>Type</th>
                        <th>Shortcode / Account</th>
                        <th>Prefix</th>
                        <th>Status</th>
                        <th>Secrets</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $gateways as $gateway}
                        <tr>
                            <td><strong>{$gateway.tenant_name|escape}</strong><br><small>Tenant #{$gateway.tenant_id}</small></td>
                            <td>{$gateway.gateway_name|escape}<br><small>{$gateway.payment_label|escape}</small></td>
                            <td>{$gateway.gateway_type|escape}</td>
                            <td>
                                {if $gateway.paybill_number}Paybill {$gateway.paybill_number|escape}{/if}
                                {if $gateway.till_number}Till {$gateway.till_number|escape}{/if}
                                {if $gateway.shortcode && !$gateway.paybill_number && !$gateway.till_number}{$gateway.shortcode|escape}{/if}
                                <br><small>{$gateway.settlement_account_name|escape}</small>
                            </td>
                            <td><code>{$gateway.account_prefix|escape}</code></td>
                            <td>
                                <span class="label label-{if $gateway.is_enabled}success{else}default{/if}">{if $gateway.is_enabled}Enabled{else}Disabled{/if}</span>
                                {if $gateway.is_default}<span class="label label-warning">Default</span>{/if}
                            </td>
                            <td><small>{$gateway.credentials_masked|escape}</small></td>
                            <td class="text-right">
                                <a href="{Text::url('saas/tenant-gateway-edit/')}{$gateway.id}" class="btn btn-info btn-sm"><i class="fa fa-pencil"></i></a>
                            </td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="8" class="text-center text-muted">No tenant payment gateways configured yet.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
