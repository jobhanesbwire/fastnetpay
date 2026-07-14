{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div>
            <span class="fnp-report-kicker"><i class="fa fa-shopping-cart"></i> FASTNETPAY POS</span>
            <h2>POS Dashboard</h2>
            <p>Sell devices, cables, installation services, and ISP add-ons from one counter screen.</p>
        </div>
        <div class="fnp-clients-head-actions">
            <a href="{Text::url('pos/sell')}" class="btn btn-success btn-sm"><i class="fa fa-shopping-basket"></i> POS Terminal</a>
            <a href="{Text::url('pos/product-add')}" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> Add Product</a>
        </div>
    </div>

    <div class="fnp-report-summary">
        <div class="fnp-report-stat is-success"><span>Today</span><strong>{Lang::moneyFormat($stats.today)}</strong></div>
        <div class="fnp-report-stat"><span>This Month</span><strong>{Lang::moneyFormat($stats.month)}</strong></div>
        <div class="fnp-report-stat is-warning"><span>All Time Revenue</span><strong>{Lang::moneyFormat($stats.all_time)}</strong></div>
        <div class="fnp-report-stat"><span>Products</span><strong>{$stats.products}</strong></div>
        <div class="fnp-report-stat is-danger"><span>Low Stock</span><strong>{$stats.low_stock}</strong></div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary box-solid">
                <div class="box-header"><h3 class="box-title">Top Products</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-condensed">
                        <thead><tr><th>Product</th><th>Qty</th><th>Total</th></tr></thead>
                        <tbody>
                            {foreach $top_products as $p}
                                <tr><td>{$p['product_name']}</td><td>{$p['qty']}</td><td>{Lang::moneyFormat($p['total'])}</td></tr>
                            {foreachelse}
                                <tr><td colspan="3" class="text-muted">No sales yet.</td></tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-primary box-solid">
                <div class="box-header"><h3 class="box-title">Low Stock Alerts</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-condensed">
                        <thead><tr><th>Product</th><th>Stock</th><th>Min</th></tr></thead>
                        <tbody>
                            {foreach $low_stock as $p}
                                <tr><td>{$p['name']}</td><td>{$p['stock']}</td><td>{$p['min_stock']}</td></tr>
                            {foreachelse}
                                <tr><td colspan="3" class="text-muted">No low stock products.</td></tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="box-footer"><a href="{Text::url('pos/stock')}" class="btn btn-warning btn-sm">Manage Stock</a></div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
