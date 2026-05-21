{include file="customer/header.tpl"}
<link rel="stylesheet" href="{$app_url}/ui/themes/fastnetpay-captive/assets/css/fastnetpay-captive.css">

<div class="fnp-captive-shell">
    <section class="fnp-captive-hero">
        <div class="fnp-captive-banner">
            <span class="fnp-brand-kicker"><i class="fa fa-wifi"></i> FASTNETPAY</span>
            <h1>Welcome back, {$_user['fullname']}</h1>
            <p>Manage your connection, buy internet packages, review payments, and keep your WiFi access active from one clean portal.</p>
            <div class="fnp-captive-actions">
                <a class="fnp-captive-btn fnp-captive-btn-primary" href="{Text::url('order/package')}"><i class="fa fa-shopping-cart"></i> {Lang::T('Buy Package')}</a>
                <a class="fnp-captive-btn fnp-captive-btn-secondary" href="{Text::url('order/history')}"><i class="fa fa-file-text"></i> {Lang::T('Payment History')}</a>
            </div>
        </div>
        <aside class="fnp-captive-support">
            <h2>Account</h2>
            <div class="fnp-support-row"><i class="fa fa-user"></i><span>{$_user['username']}</span></div>
            <div class="fnp-support-row"><i class="fa fa-phone"></i><span>{if $_user['phonenumber']}{$_user['phonenumber']}{else}Phone not set{/if}</span></div>
            <div class="fnp-support-row"><i class="fa fa-money"></i><span>{if $_c['enable_balance'] == 'yes'}{Lang::moneyFormat($_user['balance'])}{else}FASTNETPAY WiFi customer{/if}</span></div>
        </aside>
    </section>

    {function showWidget pos=0}
        {foreach $widgets as $w}
            {if $w['position'] == $pos}
                {$w['content']}
            {/if}
        {/foreach}
    {/function}

    {assign rows explode(".", $_c['dashboard_Customer'])}
    {assign pos 1}
    {foreach $rows as $cols}
        {if $cols == 12}
            <div class="row">
                <div class="col-md-12">{showWidget widgets=$widgets pos=$pos}</div>
            </div>
            {assign pos value=$pos+1}
        {else}
            {assign colss explode(",", $cols)}
            <div class="row">
                {foreach $colss as $c}
                    <div class="col-md-{$c}">{showWidget widgets=$widgets pos=$pos}</div>
                    {assign pos value=$pos+1}
                {/foreach}
            </div>
        {/if}
    {/foreach}
</div>

{if isset($hostname) && $hchap == 'true' && $_c['hs_auth_method'] == 'hchap'}
    <script type="text/javascript" src="/ui/ui/scripts/md5.js"></script>
    <script type="text/javascript">
        var hostname = "http://{$hostname}/login";
        var user = "{$_user['username']}";
        var pass = "{$_user['password']}";
        var dst = "{$apkurl}";
        var authdly = "2";
        var key = hexMD5('{$key1}' + pass + '{$key2}');
        var auth = hostname + '?username=' + user + '&dst=' + dst + '&password=' + key;
        document.write('<meta http-equiv="refresh" target="_blank" content="' + authdly + '; url=' + auth + '">');
    </script>
{/if}

{include file="customer/footer.tpl"}
