{include file="sections/header.tpl"}

<div class="fnp-saas-page">
    <form method="post" action="{Text::url('saas/tenant-gateway-save')}" class="box box-primary">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <input type="hidden" name="gateway_id" value="{if $gateway}{$gateway.id}{/if}">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-credit-card-alt"></i> {if $gateway}Edit{else}Add{/if} Tenant Customer Gateway</h3>
            <div class="box-tools pull-right">
                <a href="{Text::url('saas/tenant-gateways')}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <label>Tenant / ISP</label>
                    <select class="form-control mb10" name="tenant_id">
                        {foreach $tenants as $tenant}
                            <option value="{$tenant.id}" {if ($gateway && $gateway.tenant_id eq $tenant.id) || (!$gateway && $tenant_id eq $tenant.id)}selected{/if}>{$tenant.name|escape} ({$tenant.slug|escape})</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Router Assignment</label>
                    <select class="form-control mb10" name="router_id">
                        <option value="">Per tenant / all routers</option>
                        {foreach $routers as $router}
                            <option value="{$router.id}" {if $gateway && $gateway.router_id eq $router.id}selected{/if}>{$router.name|escape}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Gateway Type</label>
                    <select class="form-control mb10" name="gateway_type">
                        <option value="jovipay_prefix" {if !$gateway || $gateway.gateway_type eq 'jovipay_prefix'}selected{/if}>Jovi-Pay Prefix Forwarding</option>
                        <option value="mpesa_paybill_c2b" {if $gateway && $gateway.gateway_type eq 'mpesa_paybill_c2b'}selected{/if}>M-Pesa Paybill C2B</option>
                        <option value="bank_mpesa_paybill" {if $gateway && $gateway.gateway_type eq 'bank_mpesa_paybill'}selected{/if}>Bank M-Pesa Paybill</option>
                        <option value="mpesa_till" {if $gateway && $gateway.gateway_type eq 'mpesa_till'}selected{/if}>M-Pesa Till</option>
                        <option value="manual" {if $gateway && $gateway.gateway_type eq 'manual'}selected{/if}>Manual Payment</option>
                        <option value="other" {if $gateway && $gateway.gateway_type eq 'other'}selected{/if}>Other / Future Gateway</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label>Gateway Name</label>
                    <input class="form-control mb10" name="gateway_name" value="{if $gateway}{$gateway.gateway_name|escape}{else}Tenant M-Pesa Gateway{/if}">
                </div>
                <div class="col-md-4">
                    <label>Payment Label</label>
                    <input class="form-control mb10" name="payment_label" value="{if $gateway}{$gateway.payment_label|escape}{else}M-Pesa STK Push{/if}">
                </div>
                <div class="col-md-4">
                    <label>Account Prefix</label>
                    <input class="form-control mb10" name="account_prefix" value="{if $gateway}{$gateway.account_prefix|escape}{else}WIFI_{/if}" placeholder="WIFI_SEGA_">
                </div>
            </div>

            <div class="row">
                <div class="col-sm-3">
                    <label>Shortcode</label>
                    <input class="form-control mb10" name="shortcode" value="{if $gateway}{$gateway.shortcode|escape}{/if}">
                </div>
                <div class="col-sm-3">
                    <label>Paybill</label>
                    <input class="form-control mb10" name="paybill_number" value="{if $gateway}{$gateway.paybill_number|escape}{/if}">
                </div>
                <div class="col-sm-3">
                    <label>Till</label>
                    <input class="form-control mb10" name="till_number" value="{if $gateway}{$gateway.till_number|escape}{/if}">
                </div>
                <div class="col-sm-3">
                    <label>Bank Name</label>
                    <input class="form-control mb10" name="bank_name" value="{if $gateway}{$gateway.bank_name|escape}{/if}">
                </div>
            </div>

            <label>Settlement Account Name</label>
            <input class="form-control mb10" name="settlement_account_name" value="{if $gateway}{$gateway.settlement_account_name|escape}{/if}">

            <div class="row">
                <div class="col-sm-4">
                    <label>Callback URL</label>
                    <input class="form-control mb10" name="callback_url" value="{if $gateway}{$gateway.callback_url|escape}{/if}">
                </div>
                <div class="col-sm-4">
                    <label>Confirmation URL</label>
                    <input class="form-control mb10" name="confirmation_url" value="{if $gateway}{$gateway.confirmation_url|escape}{/if}">
                </div>
                <div class="col-sm-4">
                    <label>Validation URL</label>
                    <input class="form-control mb10" name="validation_url" value="{if $gateway}{$gateway.validation_url|escape}{/if}">
                </div>
            </div>

            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">Secure Credentials</h3></div>
                <div class="box-body">
                    <p class="help-block">Leave secret fields blank to keep the existing encrypted values. Tenant admins cannot see these values.</p>
                    <div class="row">
                        <div class="col-sm-4"><label>API Base URL</label><input class="form-control mb10" name="api_base_url" placeholder="https://..."></div>
                        <div class="col-sm-4"><label>STK Endpoint</label><input class="form-control mb10" name="stk_endpoint" placeholder="/api/stk-push"></div>
                        <div class="col-sm-4"><label>Mini App ID</label><input class="form-control mb10" name="mini_app_id"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><label>API Token</label><input type="password" class="form-control mb10" name="api_token"></div>
                        <div class="col-sm-4"><label>API Secret</label><input type="password" class="form-control mb10" name="api_secret"></div>
                        <div class="col-sm-4"><label>Callback Secret</label><input type="password" class="form-control mb10" name="callback_secret"></div>
                    </div>
                </div>
            </div>

            <label>Public Instructions Shown On Captive Portal</label>
            <textarea class="form-control mb10" rows="4" name="public_instructions">{if $gateway}{$gateway.public_instructions|escape}{else}Pay securely with M-Pesa to activate your internet package.{/if}</textarea>

            <div class="row">
                <div class="col-sm-6">
                    <label>Status</label>
                    <select class="form-control mb10" name="is_enabled">
                        <option value="1" {if !$gateway || $gateway.is_enabled}selected{/if}>Enabled</option>
                        <option value="0" {if $gateway && !$gateway.is_enabled}selected{/if}>Disabled</option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label>Default For Tenant</label>
                    <select class="form-control mb10" name="is_default">
                        <option value="1" {if !$gateway || $gateway.is_default}selected{/if}>Yes</option>
                        <option value="0" {if $gateway && !$gateway.is_default}selected{/if}>No</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="box-footer fnp-actions-row">
            <button class="btn btn-success" type="submit"><i class="fa fa-save"></i> Save Gateway</button>
            <a href="{Text::url('saas/tenant-gateways')}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>

{include file="sections/footer.tpl"}
