{include file="customer/header.tpl"}
<link rel="stylesheet" href="{$app_url}/ui/themes/fastnetpay-captive/assets/css/fastnetpay-captive.css">

{function fnpPlanCard plan routeType label}
    <article class="fnp-captive-card">
        <div class="fnp-plan-header">
            <h3>{$plan['name_plan']}</h3>
            <span>{$label}</span>
        </div>
        <div class="fnp-plan-body">
            <p class="fnp-plan-price">
                {Lang::moneyFormat($plan['price'])}
                {if !empty($plan['price_old'])}
                    <small class="fnp-plan-old-price">{Lang::moneyFormat($plan['price_old'])}</small>
                {/if}
            </p>
            <ul class="fnp-plan-meta">
                <li><span>{Lang::T('Type')}</span><strong>{$plan['type']}</strong></li>
                {if $_c['show_bandwidth_plan'] == 'yes'}
                    <li><span>{Lang::T('Bandwidth')}</span><strong api-get-text="{Text::url('autoload_user/bw_name/')}{$plan['id_bw']}"></strong></li>
                {/if}
                <li><span>{Lang::T('Validity')}</span><strong>{$plan['validity']} {$plan['validity_unit']}</strong></li>
                {if !$plan['is_radius'] && $plan['routers']}
                    <li><span>{Lang::T('Router')}</span><strong>{$plan['routers']}</strong></li>
                {/if}
            </ul>
        </div>
        <div class="fnp-plan-actions">
            <a href="{Text::url('order/gateway/', $routeType, '/', $plan['id'], '&stoken=', App::getToken())}"
               onclick="return ask(this, '{Lang::T('Buy this? your active package will be overwrite')}')"
               class="fnp-captive-btn fnp-captive-btn-primary">
                <i class="fa fa-wifi"></i> {Lang::T('Buy')}
            </a>
            {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance'] >= $plan['price']}
                <a href="{Text::url('order/send/', $routeType, '/', $plan['id'], '&stoken=', App::getToken())}"
                   onclick="return ask(this, '{Lang::T('Buy this for friend account?')}')"
                   class="fnp-captive-btn fnp-captive-btn-light">
                    <i class="fa fa-user-plus"></i> {Lang::T('Buy for friend')}
                </a>
            {/if}
        </div>
    </article>
{/function}

<div class="fnp-captive-shell">
    <section class="fnp-captive-hero">
        <div class="fnp-captive-banner">
            <span class="fnp-brand-kicker"><i class="fa fa-wifi"></i> FASTNETPAY Hotspot</span>
            <h1>Choose your internet package</h1>
            <p>Fast, simple access for WiFi, PPPoE, and hotspot customers. Pick a package, pay securely, and get connected through the existing PHPNuxBill activation flow.</p>
            <div class="fnp-captive-actions">
                <a class="fnp-captive-btn fnp-captive-btn-primary" href="{Text::url('order/history')}"><i class="fa fa-file-text"></i> {Lang::T('Payment History')}</a>
                <a class="fnp-captive-btn fnp-captive-btn-secondary" href="{Text::url('home')}"><i class="fa fa-home"></i> {Lang::T('Dashboard')}</a>
            </div>
        </div>
        <aside class="fnp-captive-support">
            <h2>Need help?</h2>
            <div class="fnp-support-row"><i class="fa fa-shield"></i><span>Secure M-Pesa STK Push payments</span></div>
            <div class="fnp-support-row"><i class="fa fa-bolt"></i><span>Automatic package activation after confirmation</span></div>
            <div class="fnp-support-row"><i class="fa fa-phone"></i><span>{if $_c['phone']}Support: {$_c['phone']}{else}Contact FASTNETPAY support{/if}</span></div>
        </aside>
    </section>

    {assign hasPlans 0}

    {if $_c['radius_enable'] && isset($radius_hotspot) && Lang::arrayCount($radius_hotspot)>0 && ($_user['service_type'] == 'Hotspot' || $_user['service_type'] == 'Others' || $_user['service_type'] == '')}
        {assign hasPlans 1}
        <div class="fnp-section-title">
            <h2>{if $_c['hotspot_plan']==''}Radius Hotspot Plans{else}{$_c['hotspot_plan']}{/if}</h2>
            <span>Works with Radius-managed hotspot users.</span>
        </div>
        <div class="fnp-plan-grid">
            {foreach $radius_hotspot as $plan}
                {fnpPlanCard plan=$plan routeType='radius' label='Radius Hotspot'}
            {/foreach}
        </div>
    {/if}

    {if $_c['radius_enable'] && isset($radius_pppoe) && Lang::arrayCount($radius_pppoe)>0 && ($_user['service_type'] == 'PPPoE' || $_user['service_type'] == 'Others' || $_user['service_type'] == '')}
        {assign hasPlans 1}
        <div class="fnp-section-title">
            <h2>{if $_c['pppoe_plan']==''}Radius PPPoE Plans{else}{$_c['pppoe_plan']}{/if}</h2>
            <span>Radius-backed PPPoE plans for managed customers.</span>
        </div>
        <div class="fnp-plan-grid">
            {foreach $radius_pppoe as $plan}
                {fnpPlanCard plan=$plan routeType='radius' label='Radius PPPoE'}
            {/foreach}
        </div>
    {/if}

    {if isset($plans_hotspot) && Lang::arrayCount($plans_hotspot)>0 && ($_user['service_type'] == 'Hotspot' || $_user['service_type'] == 'Others' || $_user['service_type'] == '')}
        {assign hasPlans 1}
        <div class="fnp-section-title">
            <h2>{if $_c['hotspot_plan']==''}Hotspot Plans{else}{$_c['hotspot_plan']}{/if}</h2>
            <span>Best for WiFi/captive portal customers.</span>
        </div>
        <div class="fnp-plan-grid">
            {foreach $plans_hotspot as $plan}
                {fnpPlanCard plan=$plan routeType='hotspot' label='Hotspot'}
            {/foreach}
        </div>
    {/if}

    {if isset($plans_pppoe) && Lang::arrayCount($plans_pppoe)>0 && ($_user['service_type'] == 'PPPoE' || $_user['service_type'] == 'Others' || $_user['service_type'] == '')}
        {assign hasPlans 1}
        <div class="fnp-section-title">
            <h2>{if $_c['pppoe_plan']==''}PPPoE Plans{else}{$_c['pppoe_plan']}{/if}</h2>
            <span>PPPoE access packages for home and business customers.</span>
        </div>
        <div class="fnp-plan-grid">
            {foreach $plans_pppoe as $plan}
                {fnpPlanCard plan=$plan routeType='pppoe' label='PPPoE'}
            {/foreach}
        </div>
    {/if}

    {if isset($plans_vpn) && Lang::arrayCount($plans_vpn)>0}
        {assign hasPlans 1}
        <div class="fnp-section-title">
            <h2>VPN Plans</h2>
            <span>VPN access packages where enabled.</span>
        </div>
        <div class="fnp-plan-grid">
            {foreach $plans_vpn as $plan}
                {fnpPlanCard plan=$plan routeType='vpn' label='VPN'}
            {/foreach}
        </div>
    {/if}

    {if !$hasPlans}
        <div class="fnp-captive-card fnp-empty-state">
            <h2>No internet packages are available right now.</h2>
            <p>Please contact FASTNETPAY support or check again later.</p>
        </div>
    {/if}
</div>

{include file="customer/footer.tpl"}
