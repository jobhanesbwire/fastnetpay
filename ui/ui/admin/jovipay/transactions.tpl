{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-list"></i> Jovi-Pay Transactions</h3>
                <div class="box-tools">
                    <a href="{Text::url('jovipay/settings')}" class="btn btn-sm btn-default"><i class="fa fa-cog"></i> Settings</a>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-2 col-xs-6">
                        <div class="small-box bg-yellow">
                            <div class="inner"><h3>{$summary.pending}</h3><p>Pending</p></div>
                            <div class="icon"><i class="fa fa-clock-o"></i></div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-xs-6">
                        <div class="small-box bg-green">
                            <div class="inner"><h3>{$summary.success}</h3><p>Successful</p></div>
                            <div class="icon"><i class="fa fa-check"></i></div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-xs-6">
                        <div class="small-box bg-red">
                            <div class="inner"><h3>{$summary.failed}</h3><p>Failed</p></div>
                            <div class="icon"><i class="fa fa-times"></i></div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-xs-6">
                        <div class="small-box bg-aqua">
                            <div class="inner"><h3>{$summary.reconnected}</h3><p>Reconnected</p></div>
                            <div class="icon"><i class="fa fa-refresh"></i></div>
                        </div>
                    </div>
                    <div class="col-sm-2 col-xs-6">
                        <div class="small-box bg-gray">
                            <div class="inner"><h3>{$summary.unmatched}</h3><p>Unmatched</p></div>
                            <div class="icon"><i class="fa fa-question"></i></div>
                        </div>
                    </div>
                </div>

                <form method="get" class="form-inline" style="margin-bottom:15px">
                    <input type="hidden" name="_route" value="jovipay/transactions">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="all" {if $status eq 'all'}selected{/if}>All</option>
                            <option value="pending" {if $status eq 'pending'}selected{/if}>Pending</option>
                            <option value="success" {if $status eq 'success'}selected{/if}>Successful</option>
                            <option value="failed" {if $status eq 'failed'}selected{/if}>Failed</option>
                            <option value="ignored" {if $status eq 'ignored'}selected{/if}>Ignored Prefix</option>
                            <option value="unmatched" {if $status eq 'unmatched'}selected{/if}>Unmatched</option>
                            <option value="reconnected" {if $status eq 'reconnected'}selected{/if}>Reconnected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="q" class="form-control" value="{$q|escape}" placeholder="Receipt, phone, reference, MAC">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                    <a href="{Text::url('jovipay/transactions')}" class="btn btn-default">Reset</a>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Created</th>
                                <th>Reference</th>
                                <th>Receipt</th>
                                <th>Phone</th>
                                <th>Amount</th>
                                <th>Router</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Activation</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $transactions as $tx}
                                <tr>
                                    <td>{$tx.id}</td>
                                    <td>{$tx.created_at|escape}</td>
                                    <td><code>{$tx.account_reference|escape}</code></td>
                                    <td>{if $tx.mpesa_receipt_number}<strong>{$tx.mpesa_receipt_number|escape}</strong>{else}<span class="text-muted">Pending</span>{/if}</td>
                                    <td>{$tx.phone|escape}</td>
                                    <td>KES {$tx.amount|escape}</td>
                                    <td>{$tx.router_id|escape}</td>
                                    <td>{$tx.package_id|escape}</td>
                                    <td>
                                        <span class="label {if $tx.status eq 'success'}label-success{elseif $tx.status eq 'failed'}label-danger{elseif $tx.status eq 'pending'}label-warning{elseif $tx.status eq 'ignored'}label-default{else}label-info{/if}">
                                            {$tx.status|escape}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="label {if $tx.activation_status eq 'activated'}label-success{elseif $tx.activation_status eq 'failed'}label-danger{else}label-warning{/if}">
                                            {$tx.activation_status|escape}
                                        </span>
                                    </td>
                                </tr>
                            {foreachelse}
                                <tr>
                                    <td colspan="10" class="text-muted">No Jovi-Pay transactions found.</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
