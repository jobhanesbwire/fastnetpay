{include file="sections/header.tpl"}

<div class="fnp-saas-page">
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        These settings apply only to this ISP tenant. Global payment secrets, plugin controls, backup/restore, and system maintenance remain SuperAdmin-only.
    </div>

    <form method="post" action="{Text::url('settings/tenant-post')}" class="box box-primary form-horizontal">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-building"></i> Tenant Branding & Support</h3>
        </div>
        <div class="box-body">
            <div class="form-group">
                <label class="col-sm-3 control-label">Business Name</label>
                <div class="col-sm-9"><input class="form-control" name="business_name" value="{$tenant.name|escape}" required></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Logo URL</label>
                <div class="col-sm-9"><input class="form-control" name="logo" value="{$tenant.logo|escape}" placeholder="https://example.com/logo.png"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Primary Color</label>
                <div class="col-sm-3"><input type="color" class="form-control" name="primary_color" value="{$tenant.primary_color|default:'#41a146'|escape}"></div>
                <label class="col-sm-3 control-label">Secondary Color</label>
                <div class="col-sm-3"><input type="color" class="form-control" name="secondary_color" value="{$tenant.secondary_color|default:'#f9c02b'|escape}"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Support Phone</label>
                <div class="col-sm-3"><input class="form-control" name="support_phone" value="{$tenant.contact_phone|escape}"></div>
                <label class="col-sm-2 control-label">Support Email</label>
                <div class="col-sm-4"><input class="form-control" name="support_email" value="{$tenant.contact_email|escape}"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">WhatsApp Support</label>
                <div class="col-sm-3"><input class="form-control" name="support_whatsapp" value="{$tenant_settings.support_whatsapp|escape}"></div>
                <label class="col-sm-2 control-label">Timezone</label>
                <div class="col-sm-2"><input class="form-control" name="timezone" value="{$tenant.timezone|escape}"></div>
                <label class="col-sm-1 control-label">Currency</label>
                <div class="col-sm-1"><input class="form-control" name="currency" value="{$tenant.currency|escape}"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Portal Welcome</label>
                <div class="col-sm-9"><textarea class="form-control" name="portal_welcome" rows="3">{$tenant_settings.portal_welcome|escape}</textarea></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Terms Text</label>
                <div class="col-sm-9"><textarea class="form-control" name="portal_terms" rows="4">{$tenant_settings.portal_terms|escape}</textarea></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Footer Text</label>
                <div class="col-sm-9"><input class="form-control" name="footer_text" value="{$tenant_settings.footer_text|escape}"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Invoice Footer</label>
                <div class="col-sm-9"><textarea class="form-control" name="invoice_footer" rows="3">{$tenant_settings.invoice_footer|escape}</textarea></div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Notification Preferences</label>
                <div class="col-sm-9"><textarea class="form-control" name="notification_preferences" rows="3">{$tenant_settings.notification_preferences|escape}</textarea></div>
            </div>
        </div>
        <div class="box-footer text-right">
            <button class="btn btn-success" type="submit"><i class="fa fa-save"></i> Save Tenant Settings</button>
        </div>
    </form>

    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-credit-card"></i> Payment Status</h3>
        </div>
        <div class="box-body">
            <p><strong>Status:</strong> {if $tenant_settings.payment_enabled eq 'yes'}Enabled{else}Disabled{/if}</p>
            <p><strong>Customer Label:</strong> {$tenant_settings.payment_label|escape}</p>
            <p class="text-muted">{$tenant_settings.payment_support_message|escape}</p>
            <p class="help-block">Payment credentials and callback secrets are managed by FASTNETPAY SuperAdmin and are never shown on tenant screens.</p>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
