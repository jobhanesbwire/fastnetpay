{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div><span class="fnp-report-kicker"><i class="fa fa-bar-chart"></i> FASTNETPAY POS</span><h2>Sales Reports</h2><p>Review counter sales, payment methods, and daily totals.</p></div>
        <a href="{Text::url('pos/dashboard')}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Dashboard</a>
    </div>
    <div class="fnp-report-summary">
        <div class="fnp-report-stat is-success"><span>Total Revenue</span><strong>{Lang::moneyFormat($summary.total)}</strong></div>
        <div class="fnp-report-stat"><span>Sales Count</span><strong>{$summary.count}</strong></div>
        <div class="fnp-report-stat is-warning"><span>Average Sale</span><strong>{Lang::moneyFormat($summary.average)}</strong></div>
    </div>
    <div class="box box-primary box-solid"><div class="box-body">
        <form method="get" class="fnp-clients-filter">
            <input type="hidden" name="_route" value="pos/reports">
            <div class="form-group"><label>Date From</label><input class="form-control" type="date" name="date_from" value="{$date_from}"></div>
            <div class="form-group"><label>Date To</label><input class="form-control" type="date" name="date_to" value="{$date_to}"></div>
            <div class="form-group"><label>Payment Method</label><select class="form-control" name="payment"><option value="">All</option>{foreach ['cash','mpesa','balance','card','other'] as $p}<option value="{$p}" {if $payment eq $p}selected{/if}>{$p|capitalize}</option>{/foreach}</select></div>
            <div class="form-group"><label>Status</label><select class="form-control" name="status"><option value="">All</option><option value="paid" {if $status eq 'paid'}selected{/if}>Paid</option><option value="void" {if $status eq 'void'}selected{/if}>Void</option></select></div>
            <div class="form-group fnp-clients-filter-actions"><label>&nbsp;</label><button class="btn btn-primary">Filter</button></div>
        </form>
    </div></div>
    {include file="admin/pos/sales-table.tpl"}
</div>

{include file="sections/footer.tpl"}
