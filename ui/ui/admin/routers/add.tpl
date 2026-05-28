{include file="sections/header.tpl"}
<!-- routers-add -->

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Add Router')}
                <div class="btn-group pull-right">
                    <a class="btn btn-success btn-xs" href="{Text::url('routers/provision')}&draft=1" title="Provision Router Automatically">
                        <i class="fa fa-magic"></i> Provision Router Automatically
                    </a>
                </div>
            </div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}routers/add-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Status')}</label>
                        <div class="col-md-10">
                            <label class="radio-inline warning">
                                <input type="radio" checked name="enabled" value="1"> {Lang::T('Enable')}
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="enabled" value="0"> {Lang::T('Disable')}
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Router Name / Location')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="name" name="name" maxlength="32">
                            <p class="help-block">{Lang::T('Name of Area that router operated')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('IP Address')}</label>
                        <div class="col-md-6">
                            <input type="text" placeholder="192.168.88.1:8728" class="form-control" id="ip_address"
                                name="ip_address">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">API Username</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="username" name="username" placeholder="fastnet-api-usr">
                            <p class="help-block">Use <code>fastnet-api-usr</code> for production. The provisioning wizard can create this API-only user for you.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">API Password</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="password" name="password"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Description')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="description" name="description"></textarea>
                            <p class="help-block">{Lang::T('Explain Coverage of router')}</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-2 control-label"></label>
                        <div class="col-md-6">
                            <label><input type="checkbox" checked name="testIt" value="yes"> {Lang::T('Test Connection')}</label>
                            <p class="help-block">Test connection expects the router API user to already exist. Use Provision Router Automatically to create it from bootstrap/admin credentials.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary" onclick="return ask(this, '{Lang::T("Continue the process of adding Routers?")}')"
                                type="submit">{Lang::T('Save')}</button>
                            <a class="btn btn-success" href="{Text::url('routers/provision')}&draft=1">
                                <i class="fa fa-magic"></i> Provision Router Automatically
                            </a>
                            Or <a href="{Text::url('')}routers/list">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
