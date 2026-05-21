{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/mpesastkpush" autocomplete="off">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <div class="panel-title">M-Pesa STK Push Settings</div>
                </div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        Configure Safaricom Daraja STK Push for FASTNETPAY. Activate the gateway from the Payment Gateway list after saving these settings.
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Enable Gateway</label>
                        <div class="col-md-6">
                            <label class="radio-inline">
                                <input type="radio" name="enabled" value="yes" {if $mpesa.enabled eq 'yes'}checked{/if}> Enabled
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="enabled" value="no" {if $mpesa.enabled neq 'yes'}checked{/if}> Disabled
                            </label>
                            <span class="help-block">Keep disabled until credentials and callback URL are ready.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Environment</label>
                        <div class="col-md-6">
                            <select class="form-control" name="environment" required>
                                <option value="sandbox" {if $mpesa.environment eq 'sandbox'}selected{/if}>Sandbox</option>
                                <option value="live" {if $mpesa.environment eq 'live'}selected{/if}>Live</option>
                            </select>
                            <span class="help-block">Use sandbox for testing. Production callbacks must be reachable over HTTPS.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Shortcode Type</label>
                        <div class="col-md-6">
                            <select class="form-control" name="shortcode_type" required>
                                <option value="paybill" {if $mpesa.shortcode_type eq 'paybill'}selected{/if}>Paybill</option>
                                <option value="till" {if $mpesa.shortcode_type eq 'till'}selected{/if}>Till Number</option>
                            </select>
                            <span class="help-block">Paybill uses CustomerPayBillOnline; Till uses CustomerBuyGoodsOnline.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Shortcode / Till</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="shortcode" value="{$mpesa.shortcode}" required>
                            <span class="help-block">Use your Paybill shortcode, Till number, or sandbox shortcode from Safaricom Daraja.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Consumer Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="consumer_key" value="{$mpesa.consumer_key}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Consumer Secret</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="consumer_secret" placeholder="{if $mpesa.consumer_secret_masked}{$mpesa.consumer_secret_masked} - leave blank to keep current secret{else}Enter Daraja consumer secret{/if}" autocomplete="new-password">
                            <span class="help-block">Saved secrets are masked and never printed back into the page source.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Passkey</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="passkey" placeholder="{if $mpesa.passkey_masked}{$mpesa.passkey_masked} - leave blank to keep current passkey{else}Enter Daraja online passkey{/if}" autocomplete="new-password">
                            <span class="help-block">The passkey is used server-side to sign STK Push requests.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Callback URL</label>
                        <div class="col-md-6">
                            <input type="url" class="form-control" name="callback_url" value="{$mpesa.callback_url_value}" required>
                            <span class="help-block">Default route: {$mpesa.callback_url_value}. Use a public HTTPS URL in production.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Account Reference Prefix</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="account_prefix" maxlength="12" value="{$mpesa.account_prefix|default:'FASTNETPAY'}">
                            <span class="help-block">Letters and numbers only. Daraja account references are kept to 12 characters.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Transaction Description</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="transaction_desc" value="{$mpesa.transaction_desc|default:'Internet Package Payment'}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">STK Timeout</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="timeout_seconds" min="30" max="900" value="{$mpesa.timeout_seconds|default:'300'}">
                            <span class="help-block">Recommended: 300 seconds. Customers can retry from the payment page if the prompt expires.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Test Phone</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="test_phone" value="{$mpesa.test_phone}" placeholder="2547XXXXXXXX">
                            <span class="help-block">Optional. Used only as a payment page prefill during testing.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Walled Garden Domains</label>
                        <div class="col-md-6">
                            <textarea class="form-control" name="walled_garden_domains" rows="5">{$mpesa.walled_garden_domains}</textarea>
                            <span class="help-block">Add these domains to MikroTik Hotspot walled garden rules so payment and callback traffic can complete.</span>
                        </div>
                    </div>

                    <hr>
                    <h4>Captive Portal Payment Page</h4>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Portal Title</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="portal_title" value="{$mpesa.portal_title|default:'FASTNETPAY WiFi'}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Welcome Message</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="portal_welcome" value="{$mpesa.portal_welcome|default:'Pay securely with M-Pesa STK Push.'}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Logo URL</label>
                        <div class="col-md-6">
                            <input type="url" class="form-control" name="portal_logo_url" value="{$mpesa.portal_logo_url}" placeholder="https://example.com/logo.png">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Support Phone</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="portal_support_phone" value="{$mpesa.portal_support_phone}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Primary Color</label>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="portal_primary_color" value="{$mpesa.portal_primary_color|default:'#41a146'}">
                        </div>
                        <label class="col-md-1 control-label">Secondary</label>
                        <div class="col-md-2">
                            <input type="text" class="form-control" name="portal_secondary_color" value="{$mpesa.portal_secondary_color|default:'#f9c02b'}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Footer Text</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="portal_footer_text" value="{$mpesa.portal_footer_text|default:'Powered by FASTNETPAY'}">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">{Lang::T('Save Change')}</button>
                            <a href="{$_url}paymentgateway" class="btn btn-default">{Lang::T('Cancel')}</a>
                        </div>
                    </div>

                    <pre>/ip hotspot walled-garden
{foreach $mpesa.walled_garden_lines as $domain}
{if trim($domain) neq ''}add dst-host={trim($domain)}
{/if}
{/foreach}</pre>
                    <small class="form-text text-muted">
                        Change default admin credentials immediately after installation and use a non-root database user in production.
                    </small>
                </div>
            </div>
        </div>
    </div>
</form>

{include file="sections/footer.tpl"}
