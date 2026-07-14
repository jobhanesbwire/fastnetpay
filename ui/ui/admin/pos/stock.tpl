{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div><span class="fnp-report-kicker"><i class="fa fa-cubes"></i> FASTNETPAY POS</span><h2>Stock Management</h2><p>Adjust inventory and monitor low-stock items.</p></div>
        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#stockModal"><i class="fa fa-plus"></i> Adjust Stock</button>
    </div>
    <div class="fnp-quick-filters">
        <a href="{Text::url('pos/stock')}" class="label label-default">All Products</a>
        <a href="{Text::url('pos/stock&filter=in')}" class="label label-success">In Stock</a>
        <a href="{Text::url('pos/stock&filter=low')}" class="label label-warning">Low Stock</a>
        <a href="{Text::url('pos/stock&filter=out')}" class="label label-danger">Out of Stock</a>
    </div>
    <div class="box box-primary box-solid fnp-clients-table-box"><div class="box-body no-padding table-responsive">
        <table class="table table-bordered table-striped table-hover table-condensed">
            <thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Low Alert</th><th>Status</th></tr></thead>
            <tbody>{foreach $products as $p}<tr><td>{$p['name']}</td><td>{$p['sku']}</td><td>{$p['stock']}</td><td>{$p['min_stock']}</td><td>{if $p['stock'] <= 0}<span class="label label-danger">Out</span>{elseif $p['stock'] <= $p['min_stock']}<span class="label label-warning">Low</span>{else}<span class="label label-success">In Stock</span>{/if}</td></tr>{foreachelse}<tr><td colspan="5" class="text-center text-muted">No stock products.</td></tr>{/foreach}</tbody>
        </table>
    </div><div class="box-footer">{include file="pagination.tpl"}</div></div>

    <div class="box box-primary box-solid"><div class="box-header"><h3 class="box-title">Recent Stock Activity</h3></div><div class="box-body table-responsive">
        <table class="table table-condensed"><thead><tr><th>Product ID</th><th>Type</th><th>Qty</th><th>Old</th><th>New</th><th>Notes</th><th>Date</th></tr></thead><tbody>{foreach $movements as $m}<tr><td>{$m['product_id']}</td><td>{$m['movement_type']}</td><td>{$m['qty']}</td><td>{$m['old_stock']}</td><td>{$m['new_stock']}</td><td>{$m['notes']}</td><td>{$m['created_at']}</td></tr>{foreachelse}<tr><td colspan="7" class="text-muted">No movements yet.</td></tr>{/foreach}</tbody></table>
    </div></div>
</div>

<div class="modal fade" id="stockModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="{Text::url('pos/stock-post')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Adjust Stock</h4></div>
    <div class="modal-body">
        <div class="form-group"><label>Product</label><select class="form-control" name="product_id" required>{foreach $all_products as $p}<option value="{$p['id']}">{$p['name']} ({$p['stock']})</option>{/foreach}</select></div>
        <div class="form-group"><label>Adjustment Type</label><select class="form-control" name="type"><option value="in">Stock In</option><option value="out">Stock Out</option><option value="adjustment">Set To</option></select></div>
        <div class="form-group"><label>Quantity</label><input class="form-control" type="number" name="quantity" value="1" min="0"></div>
        <div class="form-group"><label>Notes / Reason</label><input class="form-control" name="notes" placeholder="e.g. Restocked from supplier"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-success">Save Adjustment</button></div>
</form></div></div>

{include file="sections/footer.tpl"}
