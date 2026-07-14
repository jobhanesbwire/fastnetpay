{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div>
            <span class="fnp-report-kicker"><i class="fa fa-ticket"></i> FASTNETPAY Support</span>
            <h2>Support Tickets</h2>
            <p>Track customer issues, assignments, priorities, and support resolution status.</p>
        </div>
        <a href="{Text::url('tickets/add')}" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> New Ticket</a>
    </div>

    <div class="fnp-report-summary">
        <div class="fnp-report-stat"><span>Total</span><strong>{$summary.total}</strong></div>
        <div class="fnp-report-stat is-success"><span>Open</span><strong>{$summary.open}</strong></div>
        <div class="fnp-report-stat is-warning"><span>In Progress</span><strong>{$summary.in_progress}</strong></div>
        <div class="fnp-report-stat"><span>Waiting Customer</span><strong>{$summary.waiting_customer}</strong></div>
        <div class="fnp-report-stat is-danger"><span>Urgent</span><strong>{$summary.urgent}</strong></div>
    </div>

    <div class="box box-primary box-solid">
        <div class="box-body">
            <form method="get" class="fnp-clients-filter">
                <input type="hidden" name="_route" value="tickets/list">
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="status">
                        <option value="">All</option>
                        {foreach ['open','in_progress','waiting_customer','resolved','closed'] as $st}
                            <option value="{$st}" {if $status eq $st}selected{/if}>{$st|replace:'_':' '|capitalize}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select class="form-control" name="priority">
                        <option value="">All</option>
                        {foreach ['urgent','high','medium','low'] as $pr}
                            <option value="{$pr}" {if $priority eq $pr}selected{/if}>{$pr|capitalize}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-group">
                    <label>Assigned To</label>
                    <select class="form-control" name="assigned_to">
                        <option value="">All</option>
                        <option value="unassigned" {if $assigned_to eq 'unassigned'}selected{/if}>Unassigned</option>
                        {foreach $admins as $adm}
                            <option value="{$adm['id']}" {if $assigned_to eq $adm['id']}selected{/if}>{$adm['fullname']}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-group fnp-clients-search">
                    <label>Search</label>
                    <input type="text" class="form-control" name="search" value="{$search}" placeholder="Search tickets...">
                </div>
                <div class="form-group fnp-clients-filter-actions">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                    <a href="{Text::url('tickets/list')}" class="btn btn-default">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-primary box-solid fnp-clients-table-box">
        <div class="box-body no-padding table-responsive">
            <table class="table table-bordered table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ticket Number</th>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $tickets as $ticket}
                        {assign customer value=$customers[$ticket['customer_id']]}
                        {assign assigned value=$admins_map[$ticket['assigned_to']]}
                        <tr>
                            <td>{$ticket['id']}</td>
                            <td><span class="fnp-mono">{$ticket['ticket_number']}</span></td>
                            <td><a href="{Text::url('tickets/view/', $ticket['id'])}">{$ticket['subject']}</a></td>
                            <td>{if $customer}<a href="{Text::url('customers/view/', $customer['id'])}">{$customer['username']}</a>{else}<span class="text-muted">None</span>{/if}</td>
                            <td><span class="label label-{if $ticket['priority'] eq 'urgent'}danger{elseif $ticket['priority'] eq 'high'}warning{elseif $ticket['priority'] eq 'low'}success{else}info{/if}">{$ticket['priority']|capitalize}</span></td>
                            <td><span class="fnp-status-badge {if in_array($ticket['status'], ['open','in_progress'])}is-online{else}is-offline{/if}"><i></i>{$ticket['status']|replace:'_':' '|capitalize}</span></td>
                            <td>{if $assigned}{$assigned['fullname']}{else}<span class="text-muted">Unassigned</span>{/if}</td>
                            <td>{$ticket['created_at']}</td>
                            <td>{$ticket['updated_at']}</td>
                            <td><a class="btn btn-info btn-xs" href="{Text::url('tickets/view/', $ticket['id'])}"><i class="fa fa-eye"></i></a></td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="10" class="text-center text-muted fnp-empty-row">No tickets found.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        <div class="box-footer">{include file="pagination.tpl"}</div>
    </div>
</div>

{include file="sections/footer.tpl"}
