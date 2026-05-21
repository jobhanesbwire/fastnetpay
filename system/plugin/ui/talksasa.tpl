{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" autocomplete="off" role="form" action="">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">TALKSASA SMS Gateway</div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        Configure TALKSASA as the FASTNETPAY SMS gateway. Saving this page sets the SMS provider to TALKSASA for existing voucher, payment, customer, and reminder notifications.
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">API TOKEN</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="api_token" placeholder="{if $talksasa.api_token_masked}{$talksasa.api_token_masked} - leave blank to keep current token{else}Enter TALKSASA API token{/if}" autocomplete="new-password">
                            <span class="help-block">The token is stored server-side and is never printed back into the page source.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">API ENDPOINT</label>
                        <div class="col-md-6">
                            <input type="url" class="form-control" name="api_endpoint" value="{$talksasa.api_endpoint|default:'https://bulksms.talksasa.com/api/v3/sms/send'}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">SENDER_ID</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="sender_id" value="{$talksasa.sender_id}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" name="save" value="save" type="submit">{Lang::T('Save')}</button>
                            <a href="{$_url}settings/app" class="btn btn-default">{Lang::T('Cancel')}</a>
                        </div>
                    </div>

                    <small class="form-text text-muted">
                        Kenyan numbers are normalized from 07XXXXXXXX and 01XXXXXXXX to 2547XXXXXXXX and 2541XXXXXXXX. Comma-separated recipients are supported.
                    </small>
                </div>
            </div>
        </div>
    </div>
</form>

{include file="sections/footer.tpl"}
