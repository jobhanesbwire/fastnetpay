{include file="sections/header.tpl"}

<div class="fnp-saas-page">
    <div class="row">
        <div class="col-md-4 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Unmatched Payments</span>
                <b>{$unmatched|count}</b>
                <small>Need SuperAdmin review</small>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>SaaS Payments</span>
                <b>{$invoice_payments|count}</b>
                <small>Recent invoice settlement records</small>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Customer Payments</span>
                <b>{$customer_payments|count}</b>
                <small>Tenant customer payment callbacks</small>
            </div>
        </div>
    </div>

    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-random"></i> Unmatched Payments</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover fnp-compact-table">
                <thead>
                    <tr>
                        <th>Received</th>
                        <th>Source</th>
                        <th>Reference</th>
                        <th>Code</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Assign</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $unmatched as $row}
                        <tr>
                            <td>{$row.created_at|escape}</td>
                            <td>{$row.source|escape}</td>
                            <td><code>{$row.account_reference|escape}</code></td>
                            <td>{$row.transaction_code|escape}</td>
                            <td>{$row.phone|escape}</td>
                            <td>Ksh {$row.amount|string_format:"%.2f"}</td>
                            <td><small>{$row.reason|escape}</small></td>
                            <td>
                                {if !$row.resolved_at}
                                    <form method="post" action="{Text::url('saas/reconcile-unmatched-post')}" class="fnp-inline-action">
                                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                        <input type="hidden" name="unmatched_id" value="{$row.id}">
                                        <select class="form-control input-sm mb10" name="invoice_id">
                                            {foreach $invoices as $invoice}
                                                <option value="{$invoice.id}">{$invoice.invoice_number|escape} · Ksh {$invoice.total_due|string_format:"%.2f"}</option>
                                            {/foreach}
                                        </select>
                                        <button class="btn btn-success btn-sm" type="submit"><i class="fa fa-link"></i> Reconcile</button>
                                    </form>
                                {else}
                                    <span class="label label-success">Resolved</span>
                                {/if}
                            </td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="8" class="text-center text-muted">No unmatched payments.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list"></i> Recent SaaS Invoice Payments</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-striped fnp-compact-table">
                <thead><tr><th>Date</th><th>Tenant</th><th>Invoice</th><th>Reference</th><th>Code</th><th>Provider</th><th>Status</th><th>Amount</th></tr></thead>
                <tbody>
                    {foreach $invoice_payments as $payment}
                        <tr>
                            <td>{$payment.created_at|escape}</td>
                            <td>#{$payment.tenant_id}</td>
                            <td><a href="{Text::url('saas/invoice/')}{$payment.invoice_id}">#{$payment.invoice_id}</a></td>
                            <td><code>{$payment.account_reference|escape}</code></td>
                            <td>{$payment.transaction_code|escape}</td>
                            <td>{$payment.payment_provider|escape}</td>
                            <td><span class="label label-{if $payment.status eq 'success'}success{elseif $payment.status eq 'failed'}danger{else}warning{/if}">{$payment.matched_status|escape}</span></td>
                            <td>Ksh {$payment.amount|string_format:"%.2f"}</td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="8" class="text-center text-muted">No invoice payments recorded yet.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-wifi"></i> Recent Tenant Customer Payments</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-striped fnp-compact-table">
                <thead><tr><th>Date</th><th>Tenant</th><th>Router</th><th>Package</th><th>Reference</th><th>Code</th><th>Status</th><th>Activation</th><th>Amount</th></tr></thead>
                <tbody>
                    {foreach $customer_payments as $payment}
                        <tr>
                            <td>{$payment.created_at|escape}</td>
                            <td>#{$payment.tenant_id}</td>
                            <td>#{$payment.router_id}</td>
                            <td>#{$payment.package_id}</td>
                            <td><code>{$payment.account_reference|escape}</code></td>
                            <td>{$payment.transaction_code|escape}</td>
                            <td>{$payment.status|escape}</td>
                            <td><span class="label label-{if $payment.activation_status eq 'activated'}success{elseif $payment.activation_status eq 'failed'}danger{else}warning{/if}">{$payment.activation_status|escape}</span></td>
                            <td>Ksh {$payment.amount|string_format:"%.2f"}</td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="9" class="text-center text-muted">No tenant customer payments recorded here yet.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
