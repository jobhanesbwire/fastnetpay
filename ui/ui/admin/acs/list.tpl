{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div>
            <span class="fnp-report-kicker"><i class="ion ion-settings"></i> FASTNETPAY ACS</span>
            <h2>ACS Management</h2>
            <p>Track customer CPEs, serial numbers, MAC addresses, and provisioning status.</p>
        </div>
        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#acsDeviceModal">
            <i class="fa fa-plus"></i> Add ACS Device
        </button>
    </div>

    <div class="box box-primary box-solid">
        <div class="box-body">
            <form method="get" class="fnp-clients-filter">
                <input type="hidden" name="_route" value="acs">
                <div class="form-group fnp-clients-search">
                    <label>Search</label>
                    <input type="text" class="form-control" name="name" value="{$search}" placeholder="Search by customer, device, MAC, serial">
                </div>
                <div class="form-group fnp-clients-filter-actions">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
                    <a href="{Text::url('acs')}" class="btn btn-default">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-primary box-solid fnp-clients-table-box">
        <div class="box-header">
            <h3 class="box-title">ACS Devices</h3>
        </div>
        <div class="box-body no-padding">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-condensed">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Device Name</th>
                            <th>MAC Address</th>
                            <th>Serial Number</th>
                            <th>Status</th>
                            <th>Registered Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $devices as $device}
                            {assign customer value=$customers[$device['customer_id']]}
                            <tr>
                                <td>
                                    {if $customer}
                                        <a href="{Text::url('customers/view/', $customer['id'])}">{$customer['username']}</a>
                                        <span class="fnp-table-subtext">{$customer['fullname']}</span>
                                    {else}
                                        <span class="text-muted">Unassigned</span>
                                    {/if}
                                </td>
                                <td>{$device['device_name']}</td>
                                <td><span class="fnp-mono">{$device['mac_address']}</span></td>
                                <td><span class="fnp-mono">{$device['serial_number']}</span></td>
                                <td><span class="fnp-status-badge {if $device['status'] eq 'online'}is-online{else}is-offline{/if}"><i></i>{$device['status']|capitalize}</span></td>
                                <td>{$device['registered_at']}</td>
                                <td class="fnp-table-actions">
                                    <button class="btn btn-info btn-xs fnp-acs-edit" data-toggle="modal" data-target="#acsDeviceModal"
                                        data-id="{$device['id']}" data-customer="{$device['customer_id']}" data-name="{$device['device_name']|escape}"
                                        data-mac="{$device['mac_address']|escape}" data-serial="{$device['serial_number']|escape}"
                                        data-status="{$device['status']|escape}" data-notes="{$device['notes']|escape}">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <a href="{Text::url('acs/delete/', $device['id'], '&csrf_token=', $csrf_token)}" class="btn btn-danger btn-xs" onclick="return ask(this, 'Delete ACS device?')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        {foreachelse}
                            <tr><td colspan="7" class="text-center text-muted fnp-empty-row">No ACS devices registered.</td></tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
        <div class="box-footer">{include file="pagination.tpl"}</div>
    </div>
</div>

<div class="modal fade" id="acsDeviceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form class="modal-content" method="post" action="{Text::url('acs/save')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <input type="hidden" name="id" id="acs_id" value="">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">ACS Device</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Customer</label>
                    <select name="customer_id" id="acs_customer_id" class="form-control">
                        <option value="0">Unassigned</option>
                        {foreach $all_customers as $customer}
                            <option value="{$customer['id']}">{$customer['username']} - {$customer['fullname']}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-group"><label>Device Name</label><input class="form-control" name="device_name" id="acs_device_name" required></div>
                <div class="form-group"><label>MAC Address</label><input class="form-control" name="mac_address" id="acs_mac_address" placeholder="AA:BB:CC:DD:EE:FF"></div>
                <div class="form-group"><label>Serial Number</label><input class="form-control" name="serial_number" id="acs_serial_number"></div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="status" id="acs_status">
                        <option value="pending">Pending</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>
                <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" id="acs_notes" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success"><i class="fa fa-save"></i> Save Device</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('.fnp-acs-edit').on('click', function () {
        $('#acs_id').val($(this).data('id') || '');
        $('#acs_customer_id').val($(this).data('customer') || 0);
        $('#acs_device_name').val($(this).data('name') || '');
        $('#acs_mac_address').val($(this).data('mac') || '');
        $('#acs_serial_number').val($(this).data('serial') || '');
        $('#acs_status').val($(this).data('status') || 'pending');
        $('#acs_notes').val($(this).data('notes') || '');
    });
    $('#acsDeviceModal').on('hidden.bs.modal', function () {
        $('#acs_id').val('');
        this.querySelector('form').reset();
    });
});
</script>

{include file="sections/footer.tpl"}
