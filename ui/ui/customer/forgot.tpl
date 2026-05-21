{include file="customer/header-public.tpl"}

<form action="{Text::url('forgot&step=')}{$step+1}" method="post" class="fnp-auth-shell fnp-auth-centered">
    <section class="fnp-auth-card">
        {if $step == 1}
            <div class="fnp-auth-card-header">
                <span class="fnp-auth-kicker"><i class="fa fa-shield"></i> {Lang::T('Verification Code')}</span>
                <h2>{Lang::T('Confirm your account')}</h2>
                <p>{Lang::T('Enter the verification code sent to your account contact.')}</p>
            </div>

            <div class="form-group">
                <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Usernames')}{/if}</label>
                <div class="input-group">
                    {if $_c['country_code_phone']!= ''}
                        <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                    {else}
                        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                    {/if}
                    <input type="text" readonly class="form-control" name="username" value="{$username}"
                        placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Usernames')}{/if}">
                </div>
            </div>
            <div class="form-group">
                <label>{Lang::T('Verification Code')}</label>
                <div class="input-group">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-asterisk"></i></span>
                    <input type="text" required class="form-control" id="otp_code"
                        placeholder="{Lang::T('Verification Code')}" name="otp_code">
                </div>
            </div>
            <div class="fnp-auth-actions">
                <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> {Lang::T('Validate')}</button>
                <a href="{Text::url('forgot&step=-1')}" class="btn btn-default">{Lang::T('Cancel')}</a>
            </div>
        {elseif $step == 2}
            <div class="fnp-auth-card-header">
                <span class="fnp-auth-kicker"><i class="fa fa-check-circle"></i> {Lang::T('Success')}</span>
                <h2>{Lang::T('Password updated')}</h2>
                <p>{Lang::T('Use this temporary password to login, then change it from your account.')}</p>
            </div>

            <div class="form-group">
                <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Usernames')}{/if}</label>
                <div class="input-group">
                    {if $_c['country_code_phone']!= ''}
                        <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                    {else}
                        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                    {/if}
                    <input type="text" readonly class="form-control" name="username" value="{$username}"
                        placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Usernames')}{/if}">
                </div>
            </div>
            <div class="form-group">
                <label>{Lang::T('Your Password has been change to')}</label>
                <input type="text" readonly class="form-control" value="{$passsword}" onclick="this.select()">
            </div>
            <div class="fnp-auth-actions">
                <a href="{Text::url('login')}" class="btn btn-primary"><i class="fa fa-arrow-left"></i> {Lang::T('Back')}</a>
            </div>
        {elseif $step == 6}
            <div class="fnp-auth-card-header">
                <span class="fnp-auth-kicker"><i class="fa fa-user"></i> {Lang::T('Forgot Username')}</span>
                <h2>{Lang::T('Find your account')}</h2>
                <p>{Lang::T('Please input your Email or Phone number')}</p>
            </div>

            <div class="form-group">
                <label>{Lang::T('Email')} / {Lang::T('Phone Number')}</label>
                <div class="input-group">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
                    <input type="text" name="find" class="form-control" required value="">
                </div>
            </div>
            <div class="fnp-auth-actions">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> {Lang::T('Validate')}</button>
                <a href="{Text::url('forgot')}" class="btn btn-default">{Lang::T('Back')}</a>
            </div>
        {else}
            <div class="fnp-auth-card-header">
                <span class="fnp-auth-kicker"><i class="fa fa-key"></i> {Lang::T('Forgot Password')}</span>
                <h2>{Lang::T('Reset access')}</h2>
                <p>{Lang::T('Enter your account identifier and we will help you recover access.')}</p>
            </div>

            <div class="form-group">
                <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Usernames')}{/if}</label>
                <div class="input-group">
                    {if $_c['country_code_phone']!= ''}
                        <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                    {else}
                        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                    {/if}
                    <input type="text" class="form-control" name="username" required
                        placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Usernames')}{/if}">
                </div>
            </div>
            <div class="fnp-auth-actions">
                <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> {Lang::T('Validate')}</button>
                <a href="{Text::url('forgot&step=6')}" class="btn btn-warning">{Lang::T('Forgot Usernames')}</a>
                <a href="{Text::url('login')}" class="btn btn-default">{Lang::T('Back')}</a>
            </div>
        {/if}
    </section>
</form>

{include file="customer/footer-public.tpl"}
