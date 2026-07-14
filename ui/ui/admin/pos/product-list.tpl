{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div><span class="fnp-report-kicker"><i class="fa fa-list"></i> FASTNETPAY POS</span><h2>Products</h2><p>Manage sellable stock items and services.</p></div>
        <a href="{Text::url('pos/product-add')}" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Add Product</a>
    </div>
    <div class="box box-primary box-solid"><div class="box-body">
        <form method="get" class="fnp-clients-filter">
            <input type="hidden" name="_route" value="pos/product-list">
            <div class="form-group"><label>Product Name</label><input class="form-control" name="name" value="{$name}" placeholder="Search..."></div>
            <div class="form-group"><label>Category</label><select class="form-control" name="category"><option value="">All</option>{foreach $categories as $cat}<option value="{$cat}" {if $category eq $cat}selected{/if}>{$cat}</option>{/foreach}</select></div>
            <div class="form-group"><label>Type</label><select class="form-control" name="type"><option value="">All</option><option value="stock" {if $type eq 'stock'}selected{/if}>Stock</option><option value="service" {if $type eq 'service'}selected{/if}>Service</option></select></div>
            <div class="form-group"><label>Status</label><select class="form-control" name="status"><option value="">All</option><option value="active" {if $status eq 'active'}selected{/if}>Active</option><option value="inactive" {if $status eq 'inactive'}selected{/if}>Inactive</option></select></div>
            <div class="form-group fnp-clients-filter-actions"><label>&nbsp;</label><button class="btn btn-primary"><i class="fa fa-search"></i> Filter</button></div>
        </form>
    </div></div>
    <div class="box box-primary box-solid fnp-clients-table-box"><div class="box-body no-padding table-responsive">
        <table class="table table-bordered table-striped table-hover table-condensed">
            <thead><tr><th>Name</th><th>SKU</th><th>Category</th><th>Type</th><th>Price</th><th>Stock</th><th>Status</th><th>Manage</th></tr></thead>
            <tbody>
                {foreach $products as $p}
                    <tr {if $p['status'] neq 'active'}class="danger"{/if}>
                        <td>{$p['name']}</td><td><span class="fnp-mono">{$p['sku']}</span></td><td>{$p['category']}</td><td>{$p['type']|capitalize}</td><td>{Lang::moneyFormat($p['price'])}</td><td>{if $p['type'] eq 'stock'}{$p['stock']}{else}<span class="text-muted">Service</span>{/if}</td><td>{$p['status']|capitalize}</td>
                        <td><a class="btn btn-info btn-xs" href="{Text::url('pos/product-edit/', $p['id'])}"><i class="fa fa-pencil"></i></a></td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="8" class="text-center text-muted fnp-empty-row">No products yet.</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div><div class="box-footer">{include file="pagination.tpl"}</div></div>
</div>

{include file="sections/footer.tpl"}
