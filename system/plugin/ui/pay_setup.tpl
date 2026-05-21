{include file="sections/header.tpl"}

<style>
    /* Checkbox container */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    /* Hidden checkbox */
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    /* Slider */
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: #2196F3;
    }

    input:focus+.slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }
</style>
{if isset($message)}
<div class="alert alert-{if $notify_t == 's'}success{else}danger{/if}">
    <button type="button" class="close" data-dismiss="alert">
        <span aria-hidden="true">×</span>
    </button>
    <div>{$message}</div>
</div>
{/if}
<!-- <section class="content-header">
    <button class="btn btn-default"><a href="{$_url}plugin/hotspot_update&db">Update Database</a></button></li>
</section> -->
<section class="content-header">
    <section class="content-header">
        <form class="form-horizontal" method="post" autocomplete="off" role="form" action="">
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <div class="panel panel-primary panel-hovered panel-stacked mb30">
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="col-md-2 control-label">{Lang::T('Template Type')}</label>
                                <div class="col-md-6">
                                    <select class="form-control" name="pay_template" id="pay_template">
                                        <option value="default" {if $_c['pay_template']=='default' }selected{/if}>
                                            {Lang::T('Default')}</option>
                                        <option value="custom" {if $_c['pay_template']=='custom' }selected{/if}>
                                            {Lang::T('Custom')}</option>
                                    </select>
                                </div>
                                <p class="help-block col-md-4"><small>{Lang::T('Choose Template type')}</small></p>
                            </div>
                            <div class="form-group" id="pay_button">
                                <label class="col-md-2 control-label">{Lang::T('Hide Pay Now Button')}</label>
                                <div class="col-md-6">
                                    <label class="switch">
                                        <input type="checkbox" id="pay_button" value="yes" {if
                                            $_c['pay_button']== 'yes'}checked{/if} name="pay_button">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <p class="help-block col-md-4"><small>{Lang::T('Display the Pay Now button or
                                        not')}</small></p>
                            </div>
                            <div class="form-group" id="default-message-header">
                                <label class="col-md-2 control-label">{Lang::T('Message Header')}</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="pay_default_message_header"
                                        name="pay_default_message_header" rows="6"
                                        value="{$_c['pay_default_message_header']}"
                                        placeholder="{Lang::T('Internet Service Suspended')}">
                                </div>
                                <p class="help-block col-md-4"><small>{Lang::T('Expiry Message Header (html is allowed)')}</small></p>
                            </div>
                            <div class="form-group" id="default-message">
                                <label class="col-md-2 control-label">{Lang::T('Message')}</label>
                                <div class="col-md-6">
                                    <textarea class="form-control" id="pay_default_message" name="pay_default_message"
                                        rows="6">{$_c['pay_default_message']}</textarea>
                                </div>
                                <p class="help-block col-md-4"><small>{Lang::T('Expiry Message (html is allowed)')}</small></p>
                            </div>

                            <div class="form-group" id="custom-message" style="display: none;">
                                <label class="col-md-2 control-label">{Lang::T('HTML Message')}</label>
                                <div class="col-md-6">
                                    <textarea rows="8" name="pay_custom_message" id="pay_custom_message"
                                        class="form-control"
                                        placeholder="{Lang::T('Enter html here...')}">{$_c['pay_custom_message']}</textarea>
                                </div>
                                <p class="help-block col-md-4"><small>
                                    {Lang::T('Placeholder')}: [[pay_link]]  {Lang::T('for account verification link')} <br>
                                    {Lang::T('HTML Expiry Message')}:
                                    <br> {Lang::T('Use html tags to format your message')} <br>
                                    {Lang::T('Example: ')} {htmlspecialchars('<b>bold text</b> or <i> italic text</i> <br> for line break')} <br>
                                    {Lang::T('Use')} {htmlspecialchars('<a href="http://www.example.com">link</a>')}   {Lang::T('for links')}</small></p>


                                </small></p>
                            </div>

                            <div class="panel-body">
                                <div class="form-group">
                                    <button class="btn btn-success btn-block" type="submit">
                                        {Lang::T('Save Changes')}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</section>
<script>
    function toggleTemplate() {
        const template = document.getElementById('pay_template');
        const defaultMessage = document.getElementById('default-message');
        const customMessage = document.getElementById('custom-message');
        const defaultMessageHeader = document.getElementById('default-message-header');
        const payButton = document.getElementById('pay_button');

        if (template.value === 'default') {
            defaultMessage.style.display = 'block';
            defaultMessageHeader.style.display = 'block';
            payButton.style.display = 'block';
            customMessage.style.display = 'none';
        } else if (template.value === 'custom') {
            defaultMessage.style.display = 'none';
            customMessage.style.display = 'block';
            defaultMessageHeader.style.display = 'none';
            payButton.style.display = 'none';
        }
    }

    // Initialize the display based on the current selection
    document.addEventListener('DOMContentLoaded', function () {
        toggleTemplate(); // Call the function on page load
        document.getElementById('pay_template').addEventListener('change', toggleTemplate);
    });
</script>
{include file="sections/footer.tpl"}