{include file="customer/header.tpl"}
<link rel="stylesheet" href="{$app_url}/ui/themes/fastnetpay-captive/assets/css/fastnetpay-captive.css">

<div class="fnp-captive-shell">
    <section class="fnp-payment-panel">
        <div class="fnp-payment-panel-header">
            <span class="fnp-brand-kicker"><i class="fa fa-credit-card"></i> FASTNETPAY Secure Checkout</span>
            <h1>{Lang::T('Make Payment')}</h1>
        </div>
        <div class="fnp-payment-panel-body">
            {if file_exists("$PAGES_PATH/Payment_Info.html")}
                <div class="alert alert-info">{include file="$PAGES_PATH/Payment_Info.html"}</div>
            {/if}

            {if !$custom}
                <ul class="fnp-payment-summary">
                    <li><span>{Lang::T('Package Name')}</span><strong>{$plan['name_plan']}</strong></li>
                    {if $plan['is_radius'] or $plan['routers']}
                        <li><span>{Lang::T('Location')}</span><strong>{if $plan['is_radius']}Radius{else}{$plan['routers']}{/if}</strong></li>
                    {/if}
                    <li><span>{Lang::T('Type')}</span><strong>{if $plan['prepaid'] eq 'yes'}{Lang::T('Prepaid')}{else}{Lang::T('Postpaid')}{/if} {$plan['type']}</strong></li>
                    <li>
                        <span>{Lang::T('Package Price')}</span>
                        <strong>
                            {if !empty($plan['price_old'])}<small class="fnp-plan-old-price">{Lang::moneyFormat($plan['price_old'])}</small> {/if}
                            {Lang::moneyFormat($plan['price'])}
                        </strong>
                    </li>
                    {if $plan['validity']}
                        <li><span>{Lang::T('Validity Period')}</span><strong>{$plan['validity']} {$plan['validity_unit']}</strong></li>
                    {/if}
                </ul>
            {else}
                <ul class="fnp-payment-summary">
                    <li><span>{Lang::T('Package Name')}</span><strong>{Lang::T('Custom Balance')}</strong></li>
                    <li><span>{Lang::T('Amount')}</span><strong>{Lang::moneyFormat($amount)}</strong></li>
                </ul>
            {/if}

            {if $discount == '' && $plan['type'] neq 'Balance' && $custom == '' && $_c['enable_coupons'] == 'yes'}
                <form class="fnp-coupon-row" action="{Text::url('order/gateway/')}{$route2}/{$route3}" method="post">
                    <label>{Lang::T('Coupon Code')}</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="coupon" id="coupon" maxlength="50" required placeholder="{Lang::T('Enter your coupon code')}">
                        <span class="input-group-btn">
                            <button type="submit" name="add_coupon" class="btn btn-info btn-flat">{Lang::T('Apply Coupon')}</button>
                        </span>
                    </div>
                </form>
            {/if}

            <ul class="fnp-payment-summary">
                {if $add_cost != 0}
                    {foreach $bills as $k => $v}
                        <li><span>{$k}</span><strong>{Lang::moneyFormat($v)}</strong></li>
                    {/foreach}
                    <li><span>{Lang::T('Additional Cost')}</span><strong>{Lang::moneyFormat($add_cost)}</strong></li>
                {/if}
                {if $discount}
                    <li><span>{Lang::T('Discount Applied')}</span><strong>{Lang::moneyFormat($discount)}</strong></li>
                {/if}
                {if $amount neq '' && $custom == '1'}
                    <li><span>{Lang::T('Total')}</span><strong>{Lang::moneyFormat($amount)}</strong></li>
                {elseif $plan['type'] eq 'Balance'}
                    <li><span>{Lang::T('Total')}</span><strong>{Lang::moneyFormat($plan['price'] + $add_cost)}</strong></li>
                {else}
                    {if $tax}
                        <li><span>{Lang::T('Tax')}</span><strong>{Lang::moneyFormat($tax)}</strong></li>
                    {/if}
                    <li><span>{Lang::T('Total')}</span><strong>{Lang::moneyFormat($plan['price'] + $add_cost + $tax)}</strong></li>
                {/if}
            </ul>

            <form method="post" action="{Text::url('order/buy/')}{$route2}/{$route3}">
                <input type="hidden" name="coupon" value="{$discount}">
                {if $custom == '1' && $amount neq ''}
                    <input type="hidden" name="custom" value="1">
                    <input type="hidden" name="amount" value="{$amount}">
                {/if}
                <div class="form-group">
                    <label for="gateway">{Lang::T('Payment Gateway')}</label>
                    <select name="gateway" id="gateway" class="form-control fnp-gateway-select">
                        {if $_c['enable_balance'] neq 'no' && $plan['type'] neq 'Balance' && $custom == '' && $_user['balance'] >= $plan['price'] + $add_cost + $tax}
                            <option value="balance">{Lang::T('Balance')} {Lang::moneyFormat($_user['balance'])}</option>
                        {/if}
                        {foreach $pgs as $pg}
                            <option value="{$pg}">{if $pg eq 'mpesastkpush'}M-Pesa STK Push{else}{ucwords(str_replace('_', ' ', $pg))}{/if}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="fnp-captive-actions">
                    <button type="submit" name="pay" class="fnp-captive-btn fnp-captive-btn-primary" onclick="return ask(this, '{Lang::T("Are You Sure?")}')">
                        <i class="fa fa-lock"></i> {Lang::T('Pay Now')}
                    </button>
                    <a href="{Text::url('home')}" class="fnp-captive-btn fnp-captive-btn-light">{Lang::T('Cancel')}</a>
                </div>
            </form>
        </div>
    </section>
</div>

{include file="customer/footer.tpl"}
