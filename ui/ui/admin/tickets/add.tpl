{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div>
            <span class="fnp-report-kicker"><i class="fa fa-plus"></i> FASTNETPAY Support</span>
            <h2>Create New Ticket</h2>
            <p>Log a customer issue and assign it to the right support person.</p>
        </div>
        <a href="{Text::url('tickets/list')}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to List</a>
    </div>

    <div class="box box-primary box-solid">
        <form method="post" action="{Text::url('tickets/add-post')}" class="form-horizontal">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <div class="box-body">
                <h4>Basic Information</h4>
                <div class="form-group">
                    <label class="col-md-3 control-label">Subject *</label>
                    <div class="col-md-7"><input class="form-control" name="subject" required placeholder="Brief description of the issue"></div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">Description *</label>
                    <div class="col-md-7"><textarea class="form-control" name="description" rows="6" required placeholder="Detailed description of the issue, steps to reproduce, etc."></textarea></div>
                </div>
                <h4>Priority and Assignment</h4>
                <div class="form-group">
                    <label class="col-md-3 control-label">Priority *</label>
                    <div class="col-md-7">
                        <select class="form-control" name="priority">
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">Assign To</label>
                    <div class="col-md-7">
                        <select class="form-control" name="assigned_to">
                            <option value="0">Unassigned</option>
                            {foreach $admins as $adm}<option value="{$adm['id']}">{$adm['fullname']}</option>{/foreach}
                        </select>
                    </div>
                </div>
                <h4>Customer Information</h4>
                <div class="form-group">
                    <label class="col-md-3 control-label">Related Customer</label>
                    <div class="col-md-7">
                        <select class="form-control select2" name="customer_id" style="width:100%">
                            <option value="0">No customer</option>
                            {foreach $customers_list as $customer}
                                <option value="{$customer['id']}">{$customer['username']} - {$customer['fullname']}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </div>
            <div class="box-footer text-right">
                <button class="btn btn-success"><i class="fa fa-save"></i> Create Ticket</button>
            </div>
        </form>
    </div>
</div>

{include file="sections/footer.tpl"}
