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
                    <p class="text-muted">Paid Ksh {$amount_paid|string_format:"%.2f"} · Balance Ksh {$balance_due|string_format:"%.2f"}</p>
                    <p class="text-muted">Due {$invoice.due_date|escape} · Grace until {$invoice.grace_until|escape}</p>
                    <p><code>{$account_reference|escape}</code></p>
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

            <h4>Payment History</h4>
            <div class="table-responsive">
                <table class="table table-hover fnp-compact-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Provider</th>
                            <th>Reference</th>
                            <th>Transaction</th>
                            <th>Status</th>
                            <th class="text-right">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $payments as $payment}
                            <tr>
                                <td>{$payment.created_at|escape}</td>
                                <td>{$payment.payment_provider|escape}</td>
                                <td><code>{$payment.account_reference|escape}</code></td>
                                <td>{$payment.transaction_code|escape}</td>
                                <td><span class="label label-{if $payment.status eq 'success'}success{elseif $payment.status eq 'failed'}danger{else}warning{/if}">{$payment.matched_status|escape}</span></td>
                                <td class="text-right">Ksh {$payment.amount|string_format:"%.2f"}</td>
                                <td class="text-right">
                                    {if $payment.status neq 'void'}
                                        <form method="post" action="{Text::url('saas/invoice-payment-void')}" class="fnp-inline-action">
                                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                            <input type="hidden" name="invoice_id" value="{$invoice.id}">
                                            <input type="hidden" name="payment_id" value="{$payment.id}">
                                            <input type="text" class="form-control input-sm mb10" name="void_note" placeholder="Refund/void note" required>
                                            <button class="btn btn-warning btn-sm" type="submit"><i class="fa fa-undo"></i> Void Note</button>
                                        </form>
                                    {/if}
                                </td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="7" class="text-center text-muted">No payments recorded for this invoice yet.</td></tr>
                        {/foreach}
                    </tbody>
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
