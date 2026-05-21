{include file="customer/header-public.tpl"}

<div class="fnp-auth-shell">
    <section class="fnp-auth-hero">
        <div class="fnp-auth-brand">
            <span class="fnp-auth-mark"><i class="fa fa-wifi"></i></span>
            <div>
                <strong>{$_c['CompanyName']}</strong>
                <span>ISP Billing Portal</span>
            </div>
        </div>
        <h1>Fast internet access, managed beautifully.</h1>
        <p>Login to manage your packages, payments, vouchers, and hotspot access through the FASTNETPAY customer portal.</p>
        <ul class="fnp-auth-points">
            <li><i class="fa fa-check-circle"></i> M-Pesa ready payments</li>
            <li><i class="fa fa-check-circle"></i> Hotspot and PPPoE package management</li>
            <li><i class="fa fa-check-circle"></i> Secure customer self-service</li>
        </ul>
    </section>

    <section class="fnp-auth-card">
        <div class="fnp-auth-card-header">
            <span class="fnp-auth-kicker"><i class="fa fa-lock"></i> Secure Login</span>
            <h2>{Lang::T('Log in to Member Panel')}</h2>
            <p>Enter your account credentials to continue.</p>
        </div>
        <form action="{Text::url('login/post')}" method="post">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
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
            <div class="form-group">
                <label>{Lang::T('Password')}</label>
                <div class="input-group">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="{Lang::T('Password')}">
                </div>
            </div>

            <div class="fnp-auth-actions">
                {if $_c['disable_registration'] != 'noreg'}
                    <a href="{Text::url('register')}" class="btn btn-warning"><i class="fa fa-user-plus"></i> {Lang::T('Register')}</a>
                {/if}
                <button type="submit" class="btn btn-primary"><i class="fa fa-sign-in"></i> {Lang::T('Login')}</button>
            </div>

            <div class="fnp-auth-links">
                <a href="{Text::url('forgot')}">{Lang::T('Forgot Password')}</a>
                <span>&bull;</span>
                <a href="javascript:showPrivacy()">Privacy</a>
                <span>&bull;</span>
                <a href="javascript:showTaC()">T &amp; C</a>
            </div>
        </form>
    </section>
</div>

{include file="customer/footer-public.tpl"}
