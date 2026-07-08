{include file="sections/header.tpl"}

<div class="fnp-saas-page">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-file-text-o"></i> {$invoice.invoice_number|escape}</h3>
            <div class="box-tools pull-right">
                <a href="{Text::url('saas/billing')}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="box-body">
            <div class="row fnp-invoice-head">
                <div class="col-md-6">
                    <h4>{$tenant.name|escape}</h4>
                    <p class="text-muted">Tenant #{$invoice.tenant_id} · {$tenant.slug|escape}</p>
                </div>
                <div class="col-md-6 text-right">
                    <span class="label label-{if $invoice.status eq 'paid'}success{elseif $invoice.status eq 'overdue'}danger{else}warning{/if} fnp-invoice-status">{$invoice.status|escape}</span>
                    <h3>Ksh {$invoice.total_due|string_format:"%.2f"}</h3>
                    <p class="text-muted">Due {$invoice.due_date|escape} · Grace until {$invoice.grace_until|escape}</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped fnp-compact-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $items as $item}
                            <tr>
                                <td>{$item.item_type|escape}</td>
                                <td>{$item.description|escape}</td>
                                <td>{$item.quantity|string_format:"%.0f"}</td>
                                <td>Ksh {$item.unit_price|string_format:"%.2f"}</td>
                                <td class="text-right"><strong>Ksh {$item.amount|string_format:"%.2f"}</strong></td>
                            </tr>
                        {/foreach}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">Total Due</th>
                            <th class="text-right">Ksh {$invoice.total_due|string_format:"%.2f"}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="box-footer fnp-actions-row">
            {if $invoice.status neq 'paid'}
                <form method="post" action="{Text::url('saas/invoice-paid/')}{$invoice.id}">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button class="btn btn-success" type="submit"><i class="fa fa-check"></i> Mark Paid & Restore Tenant</button>
                </form>
            {/if}
            <a href="{Text::url('saas/billing')}" class="btn btn-default">Close</a>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
