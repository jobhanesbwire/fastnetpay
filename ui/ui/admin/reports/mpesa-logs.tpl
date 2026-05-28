{include file="sections/header.tpl"}

<div class="fnp-report-page fnp-mpesa-logs">
    <div class="fnp-report-head">
        <div>
            <span class="fnp-report-kicker"><i class="fa fa-mobile"></i> M-Pesa STK Push</span>
            <h2>Mpesa Logs</h2>
            <p>Track payment attempts, confirmations, receipt numbers, customers, packages, routers, and activation invoices.</p>
        </div>
        <a href="{Text::url('paymentgateway/mpesastkpush')}" class="btn btn-default btn-sm">
            <i class="fa fa-cog"></i> Gateway Settings
        </a>
    </div>

    <div class="fnp-report-summary">
        <div class="fnp-report-stat">
            <span>Total Attempts</span>
            <strong>{$summary.total}</strong>
        </div>
        <div class="fnp-report-stat is-success">
            <span>Paid</span>
            <strong>{$summary.paid}</strong>
        </div>
        <div class="fnp-report-stat is-warning">
            <span>Pending</span>
            <strong>{$summary.pending}</strong>
        </div>
        <div class="fnp-report-stat is-danger">
            <span>Failed / Canceled</span>
            <strong>{$summary.failed_total}</strong>
        </div>
        <div class="fnp-report-stat is-money">
            <span>Paid Amount</span>
            <strong>{Lang::moneyFormat($summary.paid_total)}</strong>
        </div>
    </div>

    <div class="box box-primary box-solid">
        <div class="box-header">
            <h3 class="box-title">Filter M-Pesa Logs</h3>
        </div>
        <div class="box-body">
            <form method="get" class="fnp-report-filter">
                <input type="hidden" name="_route" value="reports/mpesa-logs">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="sd" value="{$sd}" class="form-control">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="ed" value="{$ed}" class="form-control">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="all" {if $status eq 'all'}selected{/if}>All statuses</option>
                        <option value="2" {if $status eq '2'}selected{/if}>Paid</option>
                        <option value="1" {if $status eq '1'}selected{/if}>Pending</option>
                        <option value="3" {if $status eq '3'}selected{/if}>Failed</option>
                        <option value="4" {if $status eq '4'}selected{/if}>Canceled</option>
                    </select>
                </div>
                <div class="form-group fnp-report-search">
                    <label>Search</label>
                    <input type="text" name="q" value="{Lang::htmlspecialchars($q)}" class="form-control"
                        placeholder="Customer, phone, receipt, checkout ID, invoice, plan, router">
                </div>
                <div class="form-group fnp-report-actions">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Apply</button>
                    <a href="{Text::url('reports/mpesa-logs')}" class="btn btn-default"><i class="fa fa-refresh"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-primary box-solid">
        <div class="box-header">
            <h3 class="box-title">M-Pesa Payment Activity</h3>
        </div>
        <div class="box-body no-padding">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-condensed fnp-mpesa-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Receipt</th>
                            <th>Checkout Request</th>
                            <th>Created</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $logs as $log}
                            <tr>
                                <td>
                                    <a href="{Text::url('customers/viewu/')}{$log.username}" class="text-bold">
                                        {Lang::htmlspecialchars($log.username)}
                                    </a>
                                    <span class="fnp-table-subtext">{Lang::htmlspecialchars($log.routers)}</span>
                                </td>
                                <td>{if $log.phone}{Lang::htmlspecialchars($log.phone)}{else}<span class="text-muted">-</span>{/if}</td>
                                <td>
                                    {Lang::htmlspecialchars($log.plan_name)}
                                    {if $log.invoice}
                                        <span class="fnp-table-subtext">Invoice: {Lang::htmlspecialchars($log.invoice)}</span>
                                    {/if}
                                </td>
                                <td class="text-right">{Lang::moneyFormat($log.amount)}</td>
                                <td>{if $log.receipt}{Lang::htmlspecialchars($log.receipt)}{else}<span class="text-muted">-</span>{/if}</td>
                                <td>
                                    <span class="fnp-mono">{if $log.checkout_request_id}{Lang::htmlspecialchars($log.checkout_request_id)}{else}-{/if}</span>
                                    {if $log.merchant_request_id}
                                        <span class="fnp-table-subtext">Merchant: {Lang::htmlspecialchars($log.merchant_request_id)}</span>
                                    {/if}
                                </td>
                                <td>{if $log.created_date}{Lang::dateTimeFormat($log.created_date)}{/if}</td>
                                <td>
                                    {if $log.paid_date}
                                        {Lang::dateTimeFormat($log.paid_date)}
                                    {elseif $log.transaction_date}
                                        {Lang::dateTimeFormat($log.transaction_date)}
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>
                                <td>
                                    <span class="label label-{$log.status_class}">{$log.status_label}</span>
                                    {if $log.result_desc}
                                        <span class="fnp-table-subtext">{Lang::htmlspecialchars($log.result_desc)}</span>
                                    {/if}
                                </td>
                                <td class="fnp-table-actions">
                                    <a href="{Text::url('paymentgateway/audit-view/')}{$log.id}" class="btn btn-info btn-xs">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    {if $log.invoice}
                                        <a href="{Text::url('reports/activation&q=')}{$log.invoice}" class="btn btn-default btn-xs">
                                            <i class="fa fa-file-text-o"></i> Invoice
                                        </a>
                                    {/if}
                                    {if $log.payment_link && $log.status == 1}
                                        <a href="{$log.payment_link}" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-xs">
                                            <i class="fa fa-external-link"></i> Pay Link
                                        </a>
                                    {/if}
                                </td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="10" class="text-center text-muted fnp-empty-row">
                                    No M-Pesa STK Push logs found for this filter.
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
        <div class="box-footer">
            {include file="pagination.tpl"}
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
