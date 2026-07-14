{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div>
            <span class="fnp-report-kicker"><i class="fa fa-ticket"></i> {$ticket['ticket_number']}</span>
            <h2>{$ticket['subject']}</h2>
            <p>Created {$ticket['created_at']} · Last updated {$ticket['updated_at']}</p>
        </div>
        <a href="{Text::url('tickets/list')}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to List</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary box-solid">
                <div class="box-header"><h3 class="box-title">Ticket Details</h3></div>
                <div class="box-body">
                    <p>{$ticket['description']|nl2br}</p>
                    <hr>
                    <h4>Activity</h4>
                    {foreach $comments as $comment}
                        <div class="well well-sm">
                            <small class="text-muted">{$comment['created_at']} · Admin #{$comment['admin_id']}</small>
                            <p>{$comment['comment']|nl2br}</p>
                        </div>
                    {foreachelse}
                        <p class="text-muted">No comments yet.</p>
                    {/foreach}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-primary box-solid">
                <form method="post" action="{Text::url('tickets/update-post')}">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <input type="hidden" name="id" value="{$ticket['id']}">
                    <div class="box-header"><h3 class="box-title">Update Ticket</h3></div>
                    <div class="box-body">
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="status">
                                {foreach ['open','in_progress','waiting_customer','resolved','closed'] as $st}
                                    <option value="{$st}" {if $ticket['status'] eq $st}selected{/if}>{$st|replace:'_':' '|capitalize}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select class="form-control" name="priority">
                                {foreach ['low','medium','high','urgent'] as $pr}
                                    <option value="{$pr}" {if $ticket['priority'] eq $pr}selected{/if}>{$pr|capitalize}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assigned To</label>
                            <select class="form-control" name="assigned_to">
                                <option value="0">Unassigned</option>
                                {foreach $admins as $adm}<option value="{$adm['id']}" {if $ticket['assigned_to'] eq $adm['id']}selected{/if}>{$adm['fullname']}</option>{/foreach}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Add Comment</label>
                            <textarea class="form-control" name="comment" rows="4"></textarea>
                        </div>
                        <p class="text-muted">
                            Customer: {if $customer}<a href="{Text::url('customers/view/', $customer['id'])}">{$customer['username']}</a>{else}None{/if}<br>
                            Created by: {if $creator}{$creator['fullname']}{else}System{/if}
                        </p>
                    </div>
                    <div class="box-footer">
                        <button class="btn btn-success btn-block"><i class="fa fa-save"></i> Update Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
