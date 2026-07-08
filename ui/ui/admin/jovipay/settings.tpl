{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-credit-card"></i> Jovi-Pay Integration</h3>
            </div>
            <form method="post" action="{Text::url('jovipay/settings-post')}" class="form-horizontal">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <input type="hidden" name="enabled" value="no">
                <div class="box-body">
                    <div class="alert alert-info">
                        <strong>FASTNETPAY uses Jovi-Pay as the preferred captive portal M-Pesa orchestrator when enabled.</strong>
                        Direct MPESA STK Push remains available as a fallback when this integration is disabled.
                    </div>
                    <div class="alert alert-warning">
                        <strong>STK initiation model:</strong>
                        Jovi-Pay currently forwards confirmed C2B transactions by account prefix. If the configured Jovi-Pay STK API endpoint is unavailable,
                        FASTNETPAY will try the existing <strong>MPESA STK Push</strong> gateway credentials and still use the generated <code>WIFI_...</code> reference so Jovi-Pay C2B forwarding can auto-reconcile the payment.
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Enable Jovi-Pay</label>
                        <div class="col-sm-9">
                            <label class="switch">
                                <input type="checkbox" name="enabled" value="yes" {if $jovipay.enabled eq '1'}checked{/if}>
                                <span class="slider"></span>
                            </label>
                            <p class="help-block">When enabled, hotspot portal STK requests are sent to Jovi-Pay instead of direct Daraja credentials inside FASTNETPAY.</p>
                        </div>
                    </div>

                    <hr>
                    <h4>Jovi-Pay API</h4>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">API Base URL</label>
                        <div class="col-sm-9">
                            <input type="url" name="api_base_url" class="form-control" value="{$jovipay.api_base_url|escape}" placeholder="https://pay.example.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">STK Push Endpoint</label>
                        <div class="col-sm-9">
                            <input type="text" name="stk_endpoint" class="form-control" value="{$jovipay.stk_endpoint|escape}" placeholder="/api/stk-push">
                            <p class="help-block">Use a relative path such as <code>/api/stk-push</code> or a full URL if Jovi-Pay provides a POST STK initiation endpoint. A missing/404 endpoint falls back to the MPESA STK Push gateway when those credentials are configured.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">API Token / Secret</label>
                        <div class="col-sm-9">
                            <input type="password" name="api_token" class="form-control" placeholder="{if $jovipay.api_token_masked}{$jovipay.api_token_masked}{else}Paste token, leave blank to keep existing{/if}" autocomplete="new-password">
                            <p class="help-block">Stored encrypted where possible. Leave blank to keep the saved token.</p>
                        </div>
                    </div>

                    <hr>
                    <h4>Account Prefix &amp; Callback</h4>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account Prefix</label>
                        <div class="col-sm-9">
                            <input type="text" name="account_prefix" class="form-control" value="{$jovipay.account_prefix|escape}" placeholder="WIFI_">
                            <p class="help-block">Jovi-Pay forwards only matching prefixes. FASTNETPAY generates references like <code>WIFI_1_3_SESSION_TIME</code>.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Callback Mode</label>
                        <div class="col-sm-9">
                            <select name="callback_mode" class="form-control">
                                <option value="local_tunnel" {if $jovipay.callback_mode eq 'local_tunnel'}selected{/if}>Local tunnel testing</option>
                                <option value="production" {if $jovipay.callback_mode eq 'production'}selected{/if}>Production VPS HTTPS</option>
                            </select>
                            <p class="help-block">Effective callback now: <code>{$jovipay.effective_callback_url|escape}</code></p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Fallback Callback URL</label>
                        <div class="col-sm-9">
                            <input type="url" name="callback_url" class="form-control" value="{$jovipay.callback_url|escape}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Local Tunnel URL</label>
                        <div class="col-sm-9">
                            <input type="url" name="local_tunnel_url" class="form-control" value="{$jovipay.local_tunnel_url|escape}" placeholder="https://abc.trycloudflare.com/?_route=api/jovipay/callback">
                            <p class="help-block">Use this while FASTNETPAY runs locally and Jovi-Pay needs a public HTTPS callback URL.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Production Callback URL</label>
                        <div class="col-sm-9">
                            <input type="url" name="production_callback_url" class="form-control" value="{$jovipay.production_callback_url|escape}" placeholder="https://fastnetpay.co.ke/?_route=api/jovipay/callback">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Callback Secret</label>
                        <div class="col-sm-9">
                            <input type="password" name="callback_secret" class="form-control" placeholder="{if $jovipay.callback_secret_masked}{$jovipay.callback_secret_masked}{else}Shared secret or HMAC key{/if}" autocomplete="new-password">
                            <p class="help-block">FASTNETPAY verifies <code>X-Jovi-Signature = hash_hmac('sha256', payloadJson + X-Jovi-Timestamp, secret)</code>. Leave blank to keep existing.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Mini-App ID</label>
                        <div class="col-sm-9">
                            <input type="text" name="mini_app_id" class="form-control" value="{$jovipay.mini_app_id|escape}" placeholder="Optional, e.g. 1">
                            <p class="help-block">Optional. When set, FASTNETPAY requires <code>X-Jovi-App-ID</code> or <code>mini_app.id</code> to match.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Jovi-Pay Allowed IPs</label>
                        <div class="col-sm-9">
                            <textarea name="allowed_ips" class="form-control" rows="3" placeholder="Optional. One IP per line.">{$jovipay.allowed_ips|escape}</textarea>
                            <p class="help-block">Leave blank while using rotating tunnels. Add fixed Jovi-Pay/VPS source IPs in production.</p>
                        </div>
                    </div>

                    <hr>
                    <h4>Portal Experience</h4>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Gateway Label</label>
                        <div class="col-sm-9">
                            <input type="text" name="gateway_label" class="form-control" value="{$jovipay.gateway_label|escape}" placeholder="M-Pesa STK Push">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Support Phone</label>
                        <div class="col-sm-9">
                            <input type="text" name="support_phone" class="form-control" value="{$jovipay.support_phone|escape}" placeholder="+254...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">WhatsApp Link</label>
                        <div class="col-sm-9">
                            <input type="url" name="support_whatsapp" class="form-control" value="{$jovipay.support_whatsapp|escape}" placeholder="https://wa.me/254...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Payment Timeout</label>
                        <div class="col-sm-3">
                            <input type="number" name="payment_timeout_seconds" min="30" max="1200" class="form-control" value="{$jovipay.payment_timeout_seconds|escape}">
                        </div>
                        <label class="col-sm-3 control-label">Polling Interval</label>
                        <div class="col-sm-3">
                            <input type="number" name="polling_interval_seconds" min="3" max="60" class="form-control" value="{$jovipay.polling_interval_seconds|escape}">
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Jovi-Pay Settings</button>
                    <a href="{Text::url('jovipay/transactions')}" class="btn btn-default"><i class="fa fa-list"></i> View Transactions</a>
                </div>
            </form>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
