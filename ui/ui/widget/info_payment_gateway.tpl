<div class="panel panel-success panel-hovered mb20 activities fnp-payment-gateways">
    <div class="panel-heading">
        <i class="fa fa-credit-card"></i> {Lang::T('Payment Gateway')}
    </div>
    <div class="panel-body">
        {assign gateways explode(",", $_c['payment_gateway'])}
        {foreach $gateways as $gateway}
            {if trim($gateway) neq ''}
                <span class="fnp-gateway-pill">{if trim($gateway) eq 'mpesastkpush'}M-Pesa STK Push{else}{ucwords(str_replace('_', ' ', trim($gateway)))}{/if}</span>
            {/if}
        {/foreach}
    </div>
</div>
