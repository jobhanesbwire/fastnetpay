{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div>
            <span class="fnp-report-kicker"><i class="fa fa-users"></i> FASTNETPAY Clients</span>
            <h2>{if $client_page eq 'pppoe'}PPPoE Clients{elseif $client_page eq 'hotspot'}Hotspot Clients{else}Clients{/if}</h2>
            <p>{if $client_page eq 'pppoe'}Manage PPPoE customers, service state, profiles, contacts, and recharge actions from one focused view.{elseif $client_page eq 'hotspot'}Manage hotspot customers, captive portal users, contacts, and recharge actions from one focused view.{else}Manage hotspot, PPPoE, and other FASTNETPAY clients with compact ISP operations controls.{/if}</p>
        </div>
        <div class="fnp-clients-head-actions">
            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                <a class="btn btn-default btn-sm" title="Export CSV"
                    href="{Text::url('customers/csv&token=', $csrf_token)}"
                    onclick="return ask(this, '{Lang::T("This will export to CSV")}?')">
                    <i class="glyphicon glyphicon-download"></i> CSV
                </a>
            {/if}
            <a href="{Text::url('customers/add')}" class="btn btn-success btn-sm" title="{Lang::T('Add')}">
                <i class="fa fa-user-plus"></i> Add Client
            </a>
        </div>
    </div>

    {if $client_page eq 'pppoe'}
        <div class="fnp-report-summary fnp-client-summary">
            <div class="fnp-report-stat">
                <span>Total PPPoE</span>
                <strong data-fnp-count="{$pppoe_summary.total}">{$pppoe_summary.total}</strong>
            </div>
            <div class="fnp-report-stat is-success">
                <span>Not Expired</span>
                <strong data-fnp-count="{$pppoe_summary.not_expired}">{$pppoe_summary.not_expired}</strong>
            </div>
            <div class="fnp-report-stat is-warning">
                <span>Expired</span>
                <strong data-fnp-count="{$pppoe_summary.expired}">{$pppoe_summary.expired}</strong>
            </div>
            <div class="fnp-report-stat is-danger">
                <span>Inactive</span>
                <strong data-fnp-count="{$pppoe_summary.inactive}">{$pppoe_summary.inactive}</strong>
            </div>
        </div>
    {else}
        <div class="fnp-report-summary fnp-client-summary">
            <div class="fnp-report-stat is-success">
                <span>Hotspot Users</span>
                <strong data-fnp-count="{$client_summary.hotspot}">{$client_summary.hotspot}</strong>
            </div>
            <div class="fnp-report-stat is-warning">
                <span>PPPoE Users</span>
                <strong data-fnp-count="{$client_summary.pppoe}">{$client_summary.pppoe}</strong>
            </div>
            <div class="fnp-report-stat">
                <span>Total Users</span>
                <strong data-fnp-count="{$client_summary.total}">{$client_summary.total}</strong>
            </div>
        </div>
    {/if}

    <div class="box box-primary box-solid fnp-clients-filter-box">
        <div class="box-body">
            <form id="site-search" method="post" action="{Text::url($client_route)}" class="fnp-clients-filter">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <div class="form-group">
                    <label>{Lang::T('Order ')}</label>
                    <select class="form-control" id="order" name="order">
                        <option value="username" {if $order eq 'username'}selected{/if}>{Lang::T('Username')}</option>
                        <option value="fullname" {if $order eq 'fullname'}selected{/if}>{Lang::T('First Name')}</option>
                        <option value="lastname" {if $order eq 'lastname'}selected{/if}>{Lang::T('Last Name')}</option>
                        <option value="created_at" {if $order eq 'created_at'}selected{/if}>{Lang::T('Created Date')}</option>
                        <option value="balance" {if $order eq 'balance'}selected{/if}>{Lang::T('Balance')}</option>
                        <option value="status" {if $order eq 'status'}selected{/if}>{Lang::T('Status')}</option>
                        <option value="service_type" {if $order eq 'service_type'}selected{/if}>{Lang::T('Service Type')}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Direction</label>
                    <select class="form-control" id="orderby" name="orderby">
                        <option value="asc" {if $orderby eq 'asc'}selected{/if}>{Lang::T('Ascending')}</option>
                        <option value="desc" {if $orderby eq 'desc'}selected{/if}>{Lang::T('Descending')}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{Lang::T('Status')}</label>
                    <select class="form-control" id="filter" name="filter">
                        {foreach $statuses as $status}
                            <option value="{$status}" {if $filter eq $status}selected{/if}>{Lang::T($status)}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-group fnp-clients-search">
                    <label>{Lang::T('Search')}</label>
                    <input type="text" name="search" class="form-control"
                        placeholder="{if $client_page eq 'pppoe'}Search PPPoE username, IP, name, phone, email{else}{Lang::T('Search')} clients, contacts, service details{/if}"
                        value="{Lang::htmlspecialchars($search)}">
                </div>
                <div class="form-group fnp-clients-filter-actions">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> {Lang::T('Search')}</button>
                    <button class="btn btn-info" type="submit" name="export" value="csv">
                        <i class="glyphicon glyphicon-download"></i> CSV
                    </button>
                </div>
            </form>

            {if $client_page eq 'pppoe'}
                <div class="fnp-quick-filters">
                    <a href="{Text::url('customers/pppoe')}" class="label label-default">PPPoE</a>
                    <a href="{Text::url('customers/pppoe&filter=Active')}" class="label label-success">Active</a>
                    <a href="{Text::url('customers/pppoe&filter=Inactive')}" class="label label-default">Inactive</a>
                    <a href="{Text::url('customers/pppoe&filter=Suspended')}" class="label label-warning">Suspended</a>
                    <a href="{Text::url('customers/pppoe&filter=Disabled')}" class="label label-danger">Disabled</a>
                </div>
            {elseif $client_page eq 'hotspot'}
                <div class="fnp-quick-filters">
                    <a href="{Text::url('customers/hotspot')}" class="label label-default">Hotspot</a>
                    <a href="{Text::url('customers/hotspot&filter=Active')}" class="label label-success">Active</a>
                    <a href="{Text::url('customers/hotspot&filter=Inactive')}" class="label label-default">Inactive</a>
                    <a href="{Text::url('customers/hotspot&filter=Suspended')}" class="label label-warning">Suspended</a>
                    <a href="{Text::url('customers/hotspot&filter=Disabled')}" class="label label-danger">Disabled</a>
                </div>
            {/if}
        </div>
    </div>

    <div class="box box-primary box-solid fnp-clients-table-box">
        <div class="box-header">
            <h3 class="box-title">{if $client_page eq 'pppoe'}PPPoE Client Directory{elseif $client_page eq 'hotspot'}Hotspot Client Directory{else}Client Directory{/if}</h3>
        </div>
        <div class="box-body no-padding">
            <div class="table-responsive table_mobile">
                <table id="customerTable" class="table table-bordered table-striped table-hover table-condensed fnp-clients-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all" aria-label="Select all clients"></th>
                            <th>{Lang::T('Username')}</th>
                            <th>{Lang::T('Account Type')}</th>
                            <th>{Lang::T('Full Name')}</th>
                            <th>{Lang::T('Balance')}</th>
                            <th>{Lang::T('Contact')}</th>
                            <th>{Lang::T('Package')}</th>
                            <th>{Lang::T('Service Type')}</th>
                            <th>PPPoE</th>
                            <th>{Lang::T('Status')}</th>
                            <th>{Lang::T('Created On')}</th>
                            <th>{Lang::T('Manage')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $d as $ds}
                            <tr {if $ds['status'] !='Active'}class="danger"{/if}>
                                <td><input type="checkbox" name="customer_ids[]" value="{$ds['id']}" aria-label="Select {$ds['username']}"></td>
                                <td>
                                    <a href="{Text::url('customers/view/', $ds['id'])}" class="fnp-client-name">
                                        {Lang::htmlspecialchars($ds['username'])}
                                    </a>
                                </td>
                                <td><span class="fnp-soft-badge">{Lang::htmlspecialchars($ds['account_type'])}</span></td>
                                <td>
                                    <a href="{Text::url('customers/view/', $ds['id'])}" class="text-bold">
                                        {Lang::htmlspecialchars($ds['fullname'])}
                                    </a>
                                </td>
                                <td>{Lang::moneyFormat($ds['balance'])}</td>
                                <td>
                                    <div class="fnp-contact-line">
                                        {if $ds['phonenumber']}
                                            <a href="tel:{$ds['phonenumber']}" class="fnp-contact-pill" title="{$ds['phonenumber']}" data-toggle="tooltip">
                                                <i class="glyphicon glyphicon-earphone"></i>
                                                <span>{Lang::htmlspecialchars($ds['phonenumber'])}</span>
                                            </a>
                                        {/if}
                                        {if $ds['email']}
                                            <a href="mailto:{$ds['email']}" class="fnp-contact-pill" title="{$ds['email']}" data-toggle="tooltip">
                                                <i class="glyphicon glyphicon-envelope"></i>
                                                <span>{Lang::htmlspecialchars($ds['email'])}</span>
                                            </a>
                                        {/if}
                                        {if $ds['coordinates']}
                                            <a href="https://www.google.com/maps/dir//{$ds['coordinates']}/" target="_blank"
                                                rel="noopener noreferrer" class="fnp-contact-pill is-icon" title="{$ds['coordinates']}" data-toggle="tooltip">
                                                <i class="glyphicon glyphicon-map-marker"></i>
                                            </a>
                                        {/if}
                                    </div>
                                </td>
                                <td api-get-text="{Text::url('autoload/plan_is_active/')}{$ds['id']}">
                                    <span class="label label-default">&bull;</span>
                                </td>
                                <td><span class="fnp-service-badge fnp-service-{strtolower($ds['service_type'])}">{Lang::T($ds['service_type'])}</span></td>
                                <td>
                                    {if $ds['pppoe_username'] || $ds['pppoe_ip']}
                                        <span class="fnp-mono">{Lang::htmlspecialchars($ds['pppoe_username'])}</span>
                                        {if $ds['pppoe_ip']}
                                            <span class="fnp-table-subtext">{Lang::htmlspecialchars($ds['pppoe_ip'])}</span>
                                        {/if}
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>
                                <td>
                                    <span class="fnp-status-badge {if $ds['status'] eq 'Active'}is-online{else}is-offline{/if}">
                                        <i></i>{Lang::T($ds['status'])}
                                    </span>
                                </td>
                                <td>{Lang::dateTimeFormat($ds['created_at'])}</td>
                                <td class="fnp-table-actions fnp-client-actions">
                                    <a href="{Text::url('customers/view/')}{$ds['id']}" class="btn btn-success btn-xs" title="{Lang::T('View')}" data-toggle="tooltip">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="{Text::url('customers/edit/', $ds['id'], '&token=', $csrf_token)}" class="btn btn-info btn-xs" title="{Lang::T('Edit')}" data-toggle="tooltip">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <a href="{Text::url('customers/sync/', $ds['id'], '&token=', $csrf_token)}" class="btn btn-default btn-xs" title="{Lang::T('Sync')}" data-toggle="tooltip">
                                        <i class="fa fa-refresh"></i>
                                    </a>
                                    <a href="{Text::url('plan/recharge/', $ds['id'], '&token=', $csrf_token)}" class="btn btn-primary btn-xs" title="{Lang::T('Recharge')}" data-toggle="tooltip">
                                        <i class="fa fa-bolt"></i>
                                    </a>
                                </td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="12" class="text-center text-muted fnp-empty-row">
                                    No clients found for this filter.
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
        <div class="box-footer">
            <div class="row">
                <div class="col-sm-6">
                    <button id="sendMessageToSelected" class="btn btn-success btn-sm">
                        <i class="fa fa-paper-plane"></i> {Lang::T('Send Message')}
                    </button>
                </div>
                <div class="col-sm-6 text-right">
                    {include file="pagination.tpl"}
                </div>
            </div>
        </div>
    </div>
</div>

<div id="sendMessageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="sendMessageModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="sendMessageModalLabel">{Lang::T('Send Message')}</h4>
            </div>
            <div class="modal-body">
                <select id="messageType" class="form-control">
                    <option value="all">{Lang::T('All')}</option>
                    <option value="email">{Lang::T('Email')}</option>
                    <option value="inbox">{Lang::T('Inbox')}</option>
                    <option value="sms">{Lang::T('SMS')}</option>
                    <option value="wa">{Lang::T('WhatsApp')}</option>
                </select>
                <br>
                <textarea id="messageContent" class="form-control" rows="4"
                    placeholder="{Lang::T('Enter your message here...')}"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{Lang::T('Close')}</button>
                <button type="button" id="sendMessageButton" class="btn btn-primary">{Lang::T('Send Message')}</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var selectAll = document.getElementById('select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                var checkboxes = document.querySelectorAll('input[name="customer_ids[]"]');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = this.checked;
                }
            });
        }

        $(function () {
            $('[data-toggle="tooltip"]').tooltip({ container: 'body' });

            var selectedCustomerIds = [];
            $('#sendMessageToSelected').on('click', function () {
                selectedCustomerIds = $('input[name="customer_ids[]"]:checked').map(function () {
                    return $(this).val();
                }).get();

                if (selectedCustomerIds.length === 0) {
                    Swal.fire({
                        title: 'Error!',
                        text: "{Lang::T('Please select at least one customer to send a message.')}",
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                $('#sendMessageModal').modal('show');
            });

            $('#sendMessageButton').on('click', function () {
                var message = $('#messageContent').val().trim();
                var messageType = $('#messageType').val();
                var button = $(this);

                if (!message) {
                    Swal.fire({
                        title: 'Error!',
                        text: "{Lang::T('Please enter a message to send.')}",
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                button.prop('disabled', true).text('{Lang::T('Sending...')}');

                $.ajax({
                    url: '?_route=message/send_bulk_selected',
                    method: 'POST',
                    data: {
                        customer_ids: selectedCustomerIds,
                        message_type: messageType,
                        message: message
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Success!',
                                text: "{Lang::T('Message sent successfully.')}",
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: "{Lang::T('Error sending message: ')}" + response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                        $('#sendMessageModal').modal('hide');
                        $('#messageContent').val('');
                    },
                    error: function () {
                        Swal.fire({
                            title: 'Error!',
                            text: "{Lang::T('Failed to send the message. Please try again.')}",
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    },
                    complete: function () {
                        button.prop('disabled', false).text('{Lang::T('Send Message')}');
                    }
                });
            });
        });
    }());
</script>
{include file="sections/footer.tpl"}
