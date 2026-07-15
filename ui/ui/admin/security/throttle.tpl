{include file="sections/header.tpl"}

<div class="fnp-clients-page fnp-security-page">
    <div class="fnp-clients-head">
        <div>
            <span class="fnp-report-kicker"><i class="fa fa-shield"></i> FASTNETPAY Security</span>
            <h2>Traffic Throttling</h2>
            <p>Limit abusive hits, log throttled attempts, and manage block/whitelist rules before production traffic reaches expensive PHP logic.</p>
        </div>
        <a href="{Text::url('security/cleanup&days=14&csrf_token=', $csrf_token)}" class="btn btn-default btn-sm">
            <i class="fa fa-trash"></i> Clean Old Events
        </a>
    </div>

    <div class="row fnp-summary-row">
        <div class="col-md-3 col-sm-6">
            <div class="fnp-summary-card">
                <span class="fnp-summary-icon fnp-green"><i class="fa fa-bolt"></i></span>
                <div><small>Events 24h</small><strong>{$stats.events_24h}</strong></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-summary-card">
                <span class="fnp-summary-icon fnp-yellow"><i class="fa fa-clock-o"></i></span>
                <div><small>Events 1h</small><strong>{$stats.events_1h}</strong></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-summary-card">
                <span class="fnp-summary-icon fnp-red"><i class="fa fa-ban"></i></span>
                <div><small>Blocked 24h</small><strong>{$stats.blocked_24h}</strong></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fnp-summary-card">
                <span class="fnp-summary-icon fnp-blue"><i class="fa fa-list"></i></span>
                <div><small>Active Rules</small><strong>{$stats.rules}</strong></div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Production note:</strong> this application throttle is one security layer. Keep Cloudflare or Nginx rate limiting enabled for larger Layer 3/4/7 floods.
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="box box-primary box-solid">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-sliders"></i> Limits</h3></div>
                <form method="post" action="{Text::url('security/settings-post')}" class="form-horizontal">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Enable App Throttle</label>
                            <div class="col-sm-7">
                                <select name="security_throttle_enabled" class="form-control">
                                    <option value="yes" {if $throttle_config.security_throttle_enabled eq 'yes'}selected{/if}>Enabled</option>
                                    <option value="no" {if $throttle_config.security_throttle_enabled neq 'yes'}selected{/if}>Disabled</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Window Seconds</label>
                            <div class="col-sm-7"><input type="number" min="10" class="form-control" name="security_throttle_window_seconds" value="{$throttle_config.security_throttle_window_seconds}"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Guest Limit</label>
                            <div class="col-sm-7"><input type="number" min="1" class="form-control" name="security_throttle_guest_limit" value="{$throttle_config.security_throttle_guest_limit}"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Logged-in Limit</label>
                            <div class="col-sm-7"><input type="number" min="1" class="form-control" name="security_throttle_auth_limit" value="{$throttle_config.security_throttle_auth_limit}"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Login/Forgot/Register Limit</label>
                            <div class="col-sm-7"><input type="number" min="1" class="form-control" name="security_throttle_login_limit" value="{$throttle_config.security_throttle_login_limit}"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">API Limit</label>
                            <div class="col-sm-7"><input type="number" min="1" class="form-control" name="security_throttle_api_limit" value="{$throttle_config.security_throttle_api_limit}"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Payment/Callback Limit</label>
                            <div class="col-sm-7"><input type="number" min="1" class="form-control" name="security_throttle_payment_limit" value="{$throttle_config.security_throttle_payment_limit}"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Block Minutes</label>
                            <div class="col-sm-7"><input type="number" min="1" class="form-control" name="security_throttle_block_minutes" value="{$throttle_config.security_throttle_block_minutes}"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Event Retention Days</label>
                            <div class="col-sm-7"><input type="number" min="1" class="form-control" name="security_throttle_event_retention_days" value="{$throttle_config.security_throttle_event_retention_days}"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Trust Proxy Headers</label>
                            <div class="col-sm-7">
                                <select name="security_trust_proxy_headers" class="form-control">
                                    <option value="no" {if $throttle_config.security_trust_proxy_headers neq 'yes'}selected{/if}>No</option>
                                    <option value="yes" {if $throttle_config.security_trust_proxy_headers eq 'yes'}selected{/if}>Yes, behind Cloudflare/Nginx only</option>
                                </select>
                                <span class="help-block">Enable only when direct VPS access is firewalled or proxied correctly.</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">No-index / No-AI Headers</label>
                            <div class="col-sm-7">
                                <select name="security_robots_headers_enabled" class="form-control">
                                    <option value="yes" {if $throttle_config.security_robots_headers_enabled eq 'yes'}selected{/if}>Enabled</option>
                                    <option value="no" {if $throttle_config.security_robots_headers_enabled neq 'yes'}selected{/if}>Disabled</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-5 control-label">Block Known AI Crawlers</label>
                            <div class="col-sm-7">
                                <select name="security_ai_bot_block_enabled" class="form-control">
                                    <option value="yes" {if $throttle_config.security_ai_bot_block_enabled eq 'yes'}selected{/if}>Enabled</option>
                                    <option value="no" {if $throttle_config.security_ai_bot_block_enabled neq 'yes'}selected{/if}>Disabled</option>
                                </select>
                                <span class="help-block">Blocks known AI crawler user-agents. This cannot stop dishonest bots that spoof browsers.</span>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer text-right">
                        <button class="btn btn-success"><i class="fa fa-save"></i> Save Security Limits</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="box box-warning box-solid">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-filter"></i> Block / Whitelist Rule</h3></div>
                <form method="post" action="{Text::url('security/rule-post')}" class="form-horizontal">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Action</label>
                            <div class="col-sm-8">
                                <select name="action_type" class="form-control">
                                    <option value="block">Block</option>
                                    <option value="whitelist">Whitelist</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Rule Type</label>
                            <div class="col-sm-8">
                                <select name="rule_type" class="form-control">
                                    <option value="ip">IP Address</option>
                                    <option value="cidr">CIDR Range</option>
                                    <option value="user_agent">User-Agent Contains</option>
                                    <option value="route">Route Prefix</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Value</label>
                            <div class="col-sm-8"><input class="form-control" name="value" required placeholder="Example: 203.0.113.10 or 203.0.113.0/24"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Reason</label>
                            <div class="col-sm-8"><input class="form-control" name="reason" placeholder="Optional internal note"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Expires At</label>
                            <div class="col-sm-8"><input class="form-control" name="expires_at" placeholder="Optional, e.g. 2026-07-30 18:00:00"></div>
                        </div>
                    </div>
                    <div class="box-footer text-right">
                        <button class="btn btn-warning"><i class="fa fa-plus"></i> Add Rule</button>
                    </div>
                </form>
            </div>

            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">Current Rules</h3></div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead><tr><th>Action</th><th>Type</th><th>Value</th><th>Reason</th><th>Expires</th><th></th></tr></thead>
                        <tbody>
                            {foreach $rules as $rule}
                                <tr>
                                    <td><span class="label {if $rule.action eq 'whitelist'}label-success{else}label-danger{/if}">{$rule.action}</span></td>
                                    <td>{$rule.rule_type}</td>
                                    <td><code>{$rule.value|escape:'html'}</code></td>
                                    <td>{$rule.reason|escape:'html'}</td>
                                    <td>{if $rule.expires_at}{$rule.expires_at|escape:'html'}{else}<span class="text-muted">Never</span>{/if}</td>
                                    <td class="text-right"><a class="btn btn-xs btn-danger" href="{Text::url('security/rule-delete/', $rule.id)}&csrf_token={$csrf_token}" onclick="return confirm('Delete this rule?')"><i class="fa fa-trash"></i></a></td>
                                </tr>
                            {foreachelse}
                                <tr><td colspan="6" class="text-center text-muted">No security rules yet.</td></tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-danger">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-list"></i> Throttled / Blocked Attempts</h3>
            <div class="box-tools">
                <form class="form-inline" method="get">
                    <input type="hidden" name="_route" value="security/throttle">
                    <input class="form-control input-sm" name="ip" value="{$filters.ip|escape:'html'}" placeholder="Filter IP">
                    <select class="form-control input-sm" name="action">
                        <option value="">All actions</option>
                        <option value="throttled" {if $filters.action eq 'throttled'}selected{/if}>Throttled</option>
                        <option value="blocked" {if $filters.action eq 'blocked'}selected{/if}>Blocked</option>
                        <option value="ai_blocked" {if $filters.action eq 'ai_blocked'}selected{/if}>AI Blocked</option>
                    </select>
                    <button class="btn btn-default btn-sm"><i class="fa fa-search"></i></button>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>IP</th>
                        <th>Action</th>
                        <th>Route</th>
                        <th>Hits</th>
                        <th>Reason</th>
                        <th>User-Agent</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $events as $event}
                        {assign var=eventIpUrl value=$event.ip|escape:'url'}
                        <tr>
                            <td>{$event.created_at}</td>
                            <td><code>{$event.ip|escape:'html'}</code></td>
                            <td><span class="label {if $event.action eq 'ai_blocked'}label-warning{elseif $event.action eq 'blocked'}label-danger{else}label-info{/if}">{$event.action|escape:'html'}</span></td>
                            <td>{$event.method|escape:'html'} <code>{$event.route|escape:'html'}</code></td>
                            <td>{$event.hit_count}/{$event.limit_count}</td>
                            <td>{$event.reason|escape:'html'}</td>
                            <td><small>{$event.user_agent|truncate:90|escape:'html'}</small></td>
                            <td class="text-right">
                                <a class="btn btn-xs btn-danger" href="{Text::url('security/block-ip&ip=', $eventIpUrl)}&csrf_token={$csrf_token}" title="Block IP"><i class="fa fa-ban"></i></a>
                                <a class="btn btn-xs btn-success" href="{Text::url('security/whitelist-ip&ip=', $eventIpUrl)}&csrf_token={$csrf_token}" title="Whitelist IP"><i class="fa fa-check"></i></a>
                            </td>
                        </tr>
                    {foreachelse}
                        <tr><td colspan="8" class="text-center text-muted">No throttled events recorded yet.</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
