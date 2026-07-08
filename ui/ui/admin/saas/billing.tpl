{include file="sections/header.tpl"}

<div class="fnp-saas-page fnp-saas-billing">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Expected This Month</span>
                <b>Ksh {$analytics.financial.expected|string_format:"%.2f"}</b>
                <small>Previewed from active tenants</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Invoiced</span>
                <b>Ksh {$analytics.financial.invoiced|string_format:"%.2f"}</b>
                <small>{$analytics.recent_invoices|count} recent invoice records</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Paid</span>
                <b>Ksh {$analytics.financial.paid|string_format:"%.2f"}</b>
                <small>Confirmed SaaS payments</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-saas-card fnp-saas-kpi">
                <span>Overdue</span>
                <b>Ksh {$analytics.financial.overdue|string_format:"%.2f"}</b>
                <small>{$analytics.tenants.suspended} suspended tenant(s)</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <form method="post" action="{Text::url('saas/billing-save-settings')}" class="box box-primary">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-sliders"></i> Billing Settings</h3>
                </div>
                <div class="box-body">
                    <label>Configuration Fee</label>
                    <input class="form-control mb10" name="configuration_fee" value="{$settings.configuration_fee|escape}">

                    <label>First Month Payment</label>
                    <input class="form-control mb10" name="first_month_payment" value="{$settings.first_month_payment|escape}">

                    <div class="row">
                        <div class="col-xs-6">
                            <label>Billing Day</label>
                            <input type="number" min="1" max="28" class="form-control mb10" name="billing_day" value="{$settings.billing_day|escape}">
                        </div>
                        <div class="col-xs-6">
                            <label>Grace Day</label>
                            <input type="number" min="1" max="31" class="form-control mb10" name="grace_day" value="{$settings.grace_day|escape}">
                        </div>
                    </div>

                    <label>Invoice Generation</label>
                    <select class="form-control mb10" name="invoice_generation_mode">
                        <option value="manual" {if $settings.invoice_generation_mode neq 'automatic'}selected{/if}>Manual</option>
                        <option value="automatic" {if $settings.invoice_generation_mode eq 'automatic'}selected{/if}>Automatic by cron</option>
                    </select>

                    <label>Auto Suspend Unpaid Tenants</label>
                    <select class="form-control mb10" name="auto_suspend_unpaid">
                        <option value="yes" {if $settings.auto_suspend_unpaid eq 'yes'}selected{/if}>Yes</option>
                        <option value="no" {if $settings.auto_suspend_unpaid neq 'yes'}selected{/if}>No</option>
                    </select>

                    <label>Disable VPN/Router Sync on Suspension</label>
                    <select class="form-control mb10" name="auto_disconnect_vpn">
                        <option value="no" {if $settings.auto_disconnect_vpn neq 'yes'}selected{/if}>No, only mark tenant suspended</option>
                        <option value="yes" {if $settings.auto_disconnect_vpn eq 'yes'}selected{/if}>Yes, mark tenant routers blocked</option>
                    </select>

                    <label>Reminder Days Before Due</label>
                    <input class="form-control mb10" name="reminder_days_before_due" value="{$settings.reminder_days_before_due|escape}" placeholder="3,1">

                    <label>Suspension Message</label>
                    <textarea class="form-control mb10" rows="4" name="suspension_message">{$settings.suspension_message|escape}</textarea>

                    <label>Tenant Invoice Preview</label>
                    <select class="form-control" name="allow_tenant_invoice_preview">
                        <option value="yes" {if $settings.allow_tenant_invoice_preview neq 'no'}selected{/if}>Allowed</option>
                        <option value="no" {if $settings.allow_tenant_invoice_preview eq 'no'}selected{/if}>SuperAdmin only</option>
                    </select>
                </div>
                <div class="box-footer">
                    <button class="btn btn-success btn-block" type="submit"><i class="fa fa-save"></i> Save Billing Settings</button>
                </div>
            </form>
        </div>

        <div class="col-md-8">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-calculator"></i> Service Billing Bands</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover fnp-compact-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Range</th>
                                <th>Base</th>
                                <th>Included</th>
                                <th>Extra/User</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $bands as $band}
                                <tr>
                                    <form method="post" action="{Text::url('saas/billing-save-band')}">
                                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                        <input type="hidden" name="band_id" value="{$band.id}">
                                        <td>
                                            <select class="form-control input-sm" name="service_type">
                                                <option value="hotspot" {if $band.service_type eq 'hotspot'}selected{/if}>Hotspot</option>
                                                <option value="pppoe" {if $band.service_type eq 'pppoe'}selected{/if}>PPPoE</option>
                                            </select>
                                        </td>
                                        <td><input class="form-control input-sm" name="name" value="{$band.name|escape}"></td>
                                        <td class="fnp-band-range">
                                            <input class="form-control input-sm" name="min_users" value="{$band.min_users|escape}">
                                            <span>-</span>
                                            <input class="form-control input-sm" name="max_users" value="{$band.max_users|escape}" placeholder="∞">
                                        </td>
                                        <td><input class="form-control input-sm" name="base_price" value="{$band.base_price|escape}"></td>
                                        <td><input class="form-control input-sm" name="included_users" value="{$band.included_users|escape}"></td>
                                        <td><input class="form-control input-sm" name="extra_user_price" value="{$band.extra_user_price|escape}"></td>
                                        <td>
                                            <select class="form-control input-sm" name="enabled">
                                                <option value="1" {if $band.enabled}selected{/if}>On</option>
                                                <option value="0" {if !$band.enabled}selected{/if}>Off</option>
                                            </select>
                                            <input type="hidden" name="sort_order" value="{$band.sort_order|escape}">
                                        </td>
                                        <td><button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-check"></i></button></td>
                                    </form>
                                </tr>
                            {/foreach}
                            <tr class="fnp-new-band-row">
                                <form method="post" action="{Text::url('saas/billing-save-band')}">
                                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                    <td>
                                        <select class="form-control input-sm" name="service_type">
                                            <option value="hotspot">Hotspot</option>
                                            <option value="pppoe">PPPoE</option>
                                        </select>
                                    </td>
                                    <td><input class="form-control input-sm" name="name" placeholder="New band"></td>
                                    <td class="fnp-band-range"><input class="form-control input-sm" name="min_users" value="0"><span>-</span><input class="form-control input-sm" name="max_users" placeholder="∞"></td>
                                    <td><input class="form-control input-sm" name="base_price" value="0"></td>
                                    <td><input class="form-control input-sm" name="included_users" value="0"></td>
                                    <td><input class="form-control input-sm" name="extra_user_price" value="0"></td>
                                    <td><input type="hidden" name="enabled" value="1"><input class="form-control input-sm" name="sort_order" value="100"></td>
                                    <td><button class="btn btn-success btn-sm" type="submit"><i class="fa fa-plus"></i></button></td>
                                </form>
                            </tr>
                        </tbody>
                    </table>
                    <p class="help-block">Example: 27 PPPoE users on the default PPPoE Master band bills Ksh 540: base Ksh 500 plus 2 users at Ksh 20.</p>
                </div>
            </div>

            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-file-text-o"></i> Invoice Preview</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-striped fnp-compact-table">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>Hotspot</th>
                                <th>PPPoE</th>
                                <th>Routers</th>
                                <th>First Invoice</th>
                                <th>Total Due</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $previews as $preview}
                                <tr>
                                    <td><strong>{$preview.tenant.name|escape}</strong><br><small>{$preview.tenant.slug|escape}</small></td>
                                    <td>{$preview.usage.hotspot}</td>
                                    <td>{$preview.usage.pppoe}</td>
                                    <td>{$preview.usage.routers}</td>
                                    <td>{if $preview.first_invoice}<span class="label label-warning">setup + first month</span>{else}<span class="label label-default">recurring</span>{/if}</td>
                                    <td><strong>Ksh {$preview.total_due|string_format:"%.2f"}</strong></td>
                                    <td>
                                        <form method="post" action="{Text::url('saas/billing-generate')}">
                                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                            <input type="hidden" name="tenant_id" value="{$preview.tenant.id}">
                                            <button class="btn btn-success btn-sm" type="submit"><i class="fa fa-file-text"></i> Generate</button>
                                        </form>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                    <form method="post" action="{Text::url('saas/billing-generate')}" class="fnp-inline-action">
                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-files-o"></i> Generate All Tenant Invoices</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-list"></i> Recent SaaS Invoices</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover fnp-compact-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tenant</th>
                        <th>Month</th>
                        <th>Due</th>
                        <th>Grace</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $invoices as $invoice}
                        <tr>
                            <td>{$invoice.invoice_number|escape}</td>
                            <td>#{$invoice.tenant_id}</td>
                            <td>{$invoice.billing_month|escape}</td>
                            <td>{$invoice.due_date|escape}</td>
                            <td>{$invoice.grace_until|escape}</td>
                            <td><span class="label label-{if $invoice.status eq 'paid'}success{elseif $invoice.status eq 'overdue'}danger{else}warning{/if}">{$invoice.status|escape}</span></td>
                            <td>Ksh {$invoice.total_due|string_format:"%.2f"}</td>
                            <td><a class="btn btn-info btn-sm" href="{Text::url('saas/invoice/')}{$invoice.id}"><i class="fa fa-eye"></i></a></td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="8" class="text-center text-muted">No SaaS invoices generated yet.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
