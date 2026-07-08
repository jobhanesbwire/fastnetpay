{include file="sections/header.tpl"}

{assign var=is_edit value=($tenant && $tenant.id)}

<form method="post" action="{if $is_edit}{Text::url('saas/update-post')}{else}{Text::url('saas/create-post')}{/if}" class="form-horizontal fnp-saas-page">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {if $is_edit}<input type="hidden" name="tenant_id" value="{$tenant.id}">{/if}

    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-building"></i> Tenant Identity</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">ISP / Tenant Name</label>
                        <div class="col-sm-9"><input class="form-control" name="name" required value="{$tenant.name|default:''|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Slug</label>
                        <div class="col-sm-9"><input class="form-control" name="slug" value="{$tenant.slug|default:''|escape}" placeholder="isp1"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Subdomain</label>
                        <div class="col-sm-9"><input class="form-control" name="subdomain" value="{$tenant.subdomain|default:''|escape}" placeholder="isp1"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Custom Domain</label>
                        <div class="col-sm-9"><input class="form-control" name="custom_domain" value="{$tenant.custom_domain|default:''|escape}" placeholder="billing.exampleisp.co.ke"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-9">
                            <select class="form-control" name="status">
                                {foreach ['trial','active','suspended'] as $status}
                                    <option value="{$status}" {if ($tenant.status|default:'trial') eq $status}selected{/if}>{$status|capitalize}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-credit-card"></i> Tenant Payment Assignment</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">SuperAdmin-managed, non-secret tenant payment settings. Credentials, consumer secrets, passkeys, and API tokens stay hidden from tenant users.</p>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Payment Enabled</label>
                        <div class="col-sm-3">
                            <select class="form-control" name="payment_enabled">
                                <option value="yes" {if ($payment_settings.payment_enabled|default:'yes') eq 'yes'}selected{/if}>Enabled</option>
                                <option value="no" {if ($payment_settings.payment_enabled|default:'yes') eq 'no'}selected{/if}>Disabled</option>
                            </select>
                        </div>
                        <label class="col-sm-2 control-label">Gateway Slugs</label>
                        <div class="col-sm-4"><input class="form-control" name="active_gateways" value="{$payment_settings.active_gateways|default:'mpesastkpush'|escape}" placeholder="mpesastkpush"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Public Payment Label</label>
                        <div class="col-sm-3"><input class="form-control" name="payment_label" value="{$payment_settings.payment_label|default:'M-Pesa STK Push'|escape}"></div>
                        <label class="col-sm-2 control-label">Jovi Prefix</label>
                        <div class="col-sm-4"><input class="form-control" name="jovipay_prefix" value="{$payment_settings.jovipay_prefix|default:''|escape}" placeholder="WIFI_SEGA_"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Callback URL</label>
                        <div class="col-sm-9"><input class="form-control" name="jovipay_callback_url" value="{$payment_settings.jovipay_callback_url|default:''|escape}" placeholder="https://tenant.example.com/?_route=api/jovipay/callback"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Tenant Payment Message</label>
                        <div class="col-sm-9"><textarea class="form-control" name="payment_support_message" rows="2">{$payment_settings.payment_support_message|default:'Pay securely with M-Pesa.'|escape}</textarea></div>
                    </div>
                </div>
            </div>

            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-paint-brush"></i> Branding & Contact</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Logo URL</label>
                        <div class="col-sm-9"><input class="form-control" name="logo" value="{$tenant.logo|default:''|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Primary Color</label>
                        <div class="col-sm-3"><input type="color" class="form-control" name="primary_color" value="{$tenant.primary_color|default:'#41a146'|escape}"></div>
                        <label class="col-sm-3 control-label">Secondary Color</label>
                        <div class="col-sm-3"><input type="color" class="form-control" name="secondary_color" value="{$tenant.secondary_color|default:'#f9c02b'|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Dark Green</label>
                        <div class="col-sm-3"><input type="color" class="form-control" name="dark_primary_color" value="{$tenant.dark_primary_color|default:'#4ade80'|escape}"></div>
                        <label class="col-sm-3 control-label">Dark Gold</label>
                        <div class="col-sm-3"><input type="color" class="form-control" name="dark_secondary_color" value="{$tenant.dark_secondary_color|default:'#facc15'|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact Phone</label>
                        <div class="col-sm-3"><input class="form-control" name="contact_phone" value="{$tenant.contact_phone|default:''|escape}"></div>
                        <label class="col-sm-2 control-label">Contact Email</label>
                        <div class="col-sm-4"><input class="form-control" name="contact_email" value="{$tenant.contact_email|default:''|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Billing Email</label>
                        <div class="col-sm-3"><input class="form-control" name="billing_email" value="{$tenant.billing_email|default:''|escape}"></div>
                        <label class="col-sm-2 control-label">Currency</label>
                        <div class="col-sm-2"><input class="form-control" name="currency" value="{$tenant.currency|default:'KES'|escape}"></div>
                        <label class="col-sm-1 control-label">TZ</label>
                        <div class="col-sm-1"><input class="form-control" name="timezone" value="{$tenant.timezone|default:'Africa/Nairobi'|escape}"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-credit-card"></i> SaaS Billing Readiness</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label class="col-sm-5 control-label">Plan</label>
                        <div class="col-sm-7">
                            <select class="form-control" name="subscription_plan">
                                {foreach $plans as $plan}
                                    <option value="{$plan.name|escape}" {if ($tenant.subscription_plan|default:'Starter') eq $plan.name}selected{/if}>{$plan.name|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-5 control-label">Subscription</label>
                        <div class="col-sm-7"><input class="form-control" name="subscription_status" value="{$tenant.subscription_status|default:'trial'|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-5 control-label">Trial Ends</label>
                        <div class="col-sm-7"><input type="datetime-local" class="form-control" name="trial_ends_at" value="{$tenant.trial_ends_at|default:''|replace:' ':'T'|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-5 control-label">Max Routers</label>
                        <div class="col-sm-7"><input type="number" class="form-control" name="max_routers" value="{$tenant.max_routers|default:''|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-5 control-label">Max Clients</label>
                        <div class="col-sm-7"><input type="number" class="form-control" name="max_clients" value="{$tenant.max_clients|default:''|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-5 control-label">Internal Tenant</label>
                        <div class="col-sm-7">
                            <select class="form-control" name="internal_tenant">
                                <option value="0" {if !($tenant.internal_tenant|default:0)}selected{/if}>No</option>
                                <option value="1" {if ($tenant.internal_tenant|default:0)}selected{/if}>Yes</option>
                            </select>
                            <p class="help-block">Use this for the mother ISP/internal tenant if it should be separated from the SuperAdmin portal.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-5 control-label">Billing Exempt</label>
                        <div class="col-sm-7">
                            <select class="form-control" name="billing_exempt">
                                <option value="0" {if !($tenant.billing_exempt|default:0)}selected{/if}>Bill normally</option>
                                <option value="1" {if ($tenant.billing_exempt|default:0)}selected{/if}>Exempt / non-billable</option>
                            </select>
                        </div>
                    </div>
                    <textarea class="form-control mb10" name="exemption_reason" rows="2" placeholder="Billing exemption reason">{$tenant.exemption_reason|default:''|escape}</textarea>
                    <textarea class="form-control" name="allowed_features" rows="3" placeholder="Allowed features">{$tenant.allowed_features|default:''|escape}</textarea>
                </div>
            </div>

            {if !$is_edit}
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-user-plus"></i> First Tenant Admin</h3>
                    </div>
                    <div class="box-body">
                        <input class="form-control mb10" name="admin_username" placeholder="Username">
                        <input class="form-control mb10" name="admin_fullname" placeholder="Full name">
                        <input class="form-control mb10" name="admin_email" placeholder="Email">
                        <input class="form-control mb10" name="admin_phone" placeholder="Phone">
                        <select class="form-control mb10" name="admin_role">
                            <option value="Admin">Tenant Admin</option>
                            <option value="Agent">Tenant Agent</option>
                            <option value="Sales">Tenant Sales</option>
                            <option value="Report">Tenant Report</option>
                        </select>
                        <input type="password" class="form-control" name="admin_password" placeholder="Password">
                    </div>
                </div>
            {else}
                <div class="box box-info">
                    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-user-plus"></i> Add Tenant Admin</h3></div>
                    <div class="box-body">
                        <p class="text-muted">Use this to create an ISP administrator scoped to this tenant only.</p>
                    </div>
                </div>
            {/if}
        </div>
    </div>

    <div class="form-group text-center">
        <button class="btn btn-success btn-lg" type="submit"><i class="fa fa-save"></i> Save Tenant</button>
        <a href="{Text::url('saas/tenants')}" class="btn btn-default btn-lg">Cancel</a>
    </div>
</form>

{if $is_edit}
    <form method="post" action="{Text::url('saas/admin-post')}" class="fnp-saas-page">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <input type="hidden" name="tenant_id" value="{$tenant.id}">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">Create Tenant Admin</h3></div>
            <div class="box-body row">
                <div class="col-md-2"><input class="form-control" name="admin_username" placeholder="Username" required></div>
                <div class="col-md-3"><input class="form-control" name="admin_fullname" placeholder="Full name" required></div>
                <div class="col-md-2"><input class="form-control" name="admin_email" placeholder="Email"></div>
                <div class="col-md-2"><input class="form-control" name="admin_phone" placeholder="Phone"></div>
                <div class="col-md-1">
                    <select class="form-control" name="admin_role">
                        <option value="Admin">Admin</option>
                        <option value="Agent">Agent</option>
                        <option value="Sales">Sales</option>
                        <option value="Report">Report</option>
                    </select>
                </div>
                <div class="col-md-1"><input type="password" class="form-control" name="admin_password" placeholder="Password" required></div>
                <div class="col-md-1"><button class="btn btn-primary btn-block" type="submit">Add</button></div>
            </div>
        </div>
    </form>
{/if}

{include file="sections/footer.tpl"}
