{include file="customer/header-public.tpl"}

<form enctype="multipart/form-data" action="{Text::url('register/post')}" method="post" class="fnp-register-grid">
    <section class="fnp-auth-info">
        <div class="fnp-auth-brand fnp-auth-brand-light">
            <span class="fnp-auth-mark"><i class="fa fa-wifi"></i></span>
            <div>
                <strong>{$_c['CompanyName']}</strong>
                <span>Customer Registration</span>
            </div>
        </div>
        <h3>{Lang::T('Registration Info')}</h3>
        {if file_exists("pages/Registration_Info.html")}
            {include file="$_path/../pages/Registration_Info.html"}
        {else}
            {include file="$_path/../pages_template/Registration_Info.html"}
        {/if}
    </section>

    <section class="fnp-auth-card">
        <div class="fnp-auth-card-header">
            <span class="fnp-auth-kicker"><span class="fnp-step-pill">1</span> {Lang::T('Register as Member')}</span>
            <h2>{Lang::T('Create your account')}</h2>
            <p>{Lang::T('Fill in your customer details exactly as they should appear on your account.')}</p>
        </div>

        <div class="form-group">
            <label>
                {if $_c['registration_username'] == 'phone'}
                    {Lang::T('Phone Number')}
                {elseif $_c['registration_username'] == 'email'}
                    {Lang::T('Email')}
                {else}
                    {Lang::T('Usernames')}
                {/if}
            </label>
            <div class="input-group">
                {if $_c['registration_username'] == 'phone'}
                    <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                {elseif $_c['registration_username'] == 'email'}
                    <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                {else}
                    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                {/if}
                <input type="text" class="form-control" name="username"
                    placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']} {Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Usernames')}{/if}">
            </div>
        </div>
        {if $_c['photo_register'] == 'yes'}
            <div class="form-group">
                <label>{Lang::T('Photo')}</label>
                <input type="file" required class="form-control" id="photo" name="photo" accept="image/*">
            </div>
        {/if}
        <div class="form-group">
            <label>{Lang::T('Full Name')}</label>
            <input type="text" {if $_c['man_fields_fname'] neq 'no'}required{/if} class="form-control"
                id="fullname" value="{$fullname}" name="fullname">
        </div>
        <div class="form-group">
            <label>{Lang::T('Email')}</label>
            <input type="text" {if $_c['man_fields_email'] neq 'no'}required{/if} class="form-control"
                id="email" placeholder="xxxxxxx@xxxx.xx" value="{$email}" name="email">
        </div>
        <div class="form-group">
            <label>{Lang::T('Home Address')}</label>
            <input type="text" {if $_c['man_fields_address'] neq 'no'}required{/if} name="address"
                id="address" value="{$address}" class="form-control">
        </div>
        {$customFields}
    </section>

    <section class="fnp-auth-card">
        <div class="fnp-auth-card-header">
            <span class="fnp-auth-kicker"><span class="fnp-step-pill">2</span> {Lang::T('Password')}</span>
            <h2>{Lang::T('Secure access')}</h2>
            <p>{Lang::T('Choose a password for your FASTNETPAY customer portal account.')}</p>
        </div>

        <div class="form-group">
            <label>{Lang::T('Password')}</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                <input type="password" required class="form-control" id="password" name="password">
            </div>
        </div>
        <div class="form-group">
            <label>{Lang::T('Confirm Password')}</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="glyphicon glyphicon-ok"></i></span>
                <input type="password" required class="form-control" id="cpassword" name="cpassword">
            </div>
        </div>

        <div class="fnp-auth-actions">
            <a href="{Text::url('login')}" class="btn btn-default"><i class="fa fa-arrow-left"></i> {Lang::T('Cancel')}</a>
            <button class="btn btn-primary" type="submit"><i class="fa fa-user-plus"></i> {Lang::T('Register')}</button>
        </div>
        <div class="fnp-auth-links">
            <a href="javascript:showPrivacy()">Privacy</a>
            <span>&bull;</span>
            <a href="javascript:showTaC()">T &amp; C</a>
        </div>
    </section>
</form>

{include file="customer/footer-public.tpl"}
