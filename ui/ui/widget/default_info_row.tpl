<div class="fnp-dashboard-range">
    <span class="fnp-range-chip">
        <i class="fa fa-calendar-o"></i>
        {Lang::dateFormat($start_date)}
    </span>
    <span class="fnp-range-chip">
        <i class="fa fa-calendar"></i>
        {Lang::dateFormat($current_date)}
    </span>
    {if $_c['enable_balance'] == 'yes' && in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
        <a class="fnp-range-chip fnp-range-chip-money" href="{Text::url('customers&search=&order=balance&filter=Active&orderby=desc')}">
            <i class="fa fa-credit-card"></i>
            <span>{Lang::T('Customer Balance')}</span>
            <strong><sup>{$_c['currency_code']}</sup>{number_format($cb,0,$_c['dec_point'],$_c['thousands_sep'])}</strong>
        </a>
    {/if}
</div>
