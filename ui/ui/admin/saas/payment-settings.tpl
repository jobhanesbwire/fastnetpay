{include file="sections/header.tpl"}

<div class="fnp-saas-page">
    <div class="row">
        <div class="col-md-8">
            <form method="post" action="{Text::url('saas/payment-settings-save')}" class="box box-primary">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-credit-card"></i> SaaS Payment Settings</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <label>Enable SaaS Payments</label>
                            <select class="form-control mb10" name="enabled">
                                <option value="1" {if $settings.enabled}selected{/if}>Enabled</option>
                                <option value="0" {if !$settings.enabled}selected{/if}>Disabled</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label>Provider</label>
                            <select class="form-control mb10" name="provider">
                                <option value="jovipay" {if $settings.provider eq 'jovipay'}selected{/if}>Jovi-Pay</option>
                                <option value="mpesa_c2b" {if $settings.provider eq 'mpesa_c2b'}selected{/if}>M-Pesa C2B</option>
                                <option value="bank_paybill" {if $settings.provider eq 'bank_paybill'}selected{/if}>Bank Paybill</option>
                                <option value="till" {if $settings.provider eq 'till'}selected{/if}>M-Pesa Till</option>
                                <option value="manual" {if $settings.provider eq 'manual'}selected{/if}>Manual</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label>Account Prefix</label>
                            <input class="form-control mb10" name="account_prefix" value="{$settings.account_prefix|escape}" placeholder="FASTNETPAY_">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-4">
                            <label>Shortcode</label>
                            <input class="form-control mb10" name="shortcode" value="{$settings.shortcode|escape}">
                        </div>
                        <div class="col-sm-4">
                            <label>Paybill</label>
                            <input class="form-control mb10" name="paybill_number" value="{$settings.paybill_number|escape}">
                        </div>
                        <div class="col-sm-4">
                            <label>Till Number</label>
                            <input class="form-control mb10" name="till_number" value="{$settings.till_number|escape}">
                        </div>
                    </div>

                    <label>Callback URL</label>
                    <input class="form-control mb10" name="callback_url" value="{$settings.callback_url|escape}">

                    <div class="row">
                        <div class="col-sm-6">
                            <label>Confirmation URL</label>
                            <input class="form-control mb10" name="confirmation_url" value="{$settings.confirmation_url|escape}">
                        </div>
                        <div class="col-sm-6">
                            <label>Validation URL</label>
                            <input class="form-control mb10" name="validation_url" value="{$settings.validation_url|escape}">
                        </div>
                    </div>

                    <label>Callback Secret / Signature Key</label>
                    <input class="form-control mb10" name="callback_secret" value="" placeholder="{if $settings.callback_secret_masked}{$settings.callback_secret_masked|escape}{else}Leave blank to keep empty{/if}">
                    <p class="help-block">Used to validate Jovi-Pay headers such as X-Jovi-Signature. Leave blank to keep the current secret.</p>

                    <div class="row">
                        <div class="col-sm-4">
                            <label>Support Phone</label>
                            <input class="form-control mb10" name="support_phone" value="{$settings.support_phone|escape}">
                        </div>
                        <div class="col-sm-4">
                            <label>Auto-settle Paid Invoices</label>
                            <select class="form-control mb10" name="auto_settle">
                                <option value="1" {if $settings.auto_settle}selected{/if}>Yes</option>
                                <option value="0" {if !$settings.auto_settle}selected{/if}>No</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label>Auto-restore Tenants</label>
                            <select class="form-control mb10" name="auto_restore">
                                <option value="1" {if $settings.auto_restore}selected{/if}>Yes</option>
                                <option value="0" {if !$settings.auto_restore}selected{/if}>No</option>
                            </select>
                        </div>
                    </div>

                    <label>Payment Instructions</label>
                    <textarea class="form-control" rows="4" name="instructions">{$settings.instructions|escape}</textarea>
                </div>
                <div class="box-footer fnp-actions-row">
                    <button class="btn btn-success" type="submit"><i class="fa fa-save"></i> Save Settings</button>
                    <a href="{Text::url('saas/billing')}" class="btn btn-default">Back to Billing</a>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <div class="fnp-saas-card">
                <div class="fnp-saas-card-head">
                    <span class="fnp-saas-logo" style="background:#41a146"></span>
                    <div>
                        <h4>Callback Endpoint</h4>
                        <p>Use this URL in M-Pesa/Jovi-Pay for tenant SaaS invoice settlement.</p>
                    </div>
                </div>
                <div class="fnp-saas-meta" style="margin-top:14px">
                    <code>{$settings.callback_url|escape}</code>
                    <span>Expected account reference: <strong>{$settings.account_prefix|escape}TENANTSLUG</strong></span>
                    <span>Secrets are stored encrypted and are visible only to SuperAdmin.</span>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
