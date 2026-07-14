{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div><span class="fnp-report-kicker"><i class="fa fa-plus-circle"></i> FASTNETPAY POS</span><h2>{if $product}Edit Product{else}Add New Product{/if}</h2><p>Products can be physical stock or non-stock services.</p></div>
        <a href="{Text::url('pos/product-list')}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to Products</a>
    </div>
    <div class="box box-primary box-solid">
        <form class="form-horizontal" method="post" action="{Text::url('pos/product-save')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <input type="hidden" name="id" value="{if $product}{$product['id']}{/if}">
            <div class="box-body">
                <div class="form-group"><label class="col-md-3 control-label">Product Name *</label><div class="col-md-7"><input class="form-control" name="name" required value="{if $product}{$product['name']}{/if}" placeholder="e.g. MikroTik hAP AC2"></div></div>
                <div class="form-group"><label class="col-md-3 control-label">SKU / Code</label><div class="col-md-7"><input class="form-control" name="sku" value="{if $product}{$product['sku']}{/if}" placeholder="Leave blank to auto-generate"><span class="help-block">Optional. FASTNETPAY will generate one if left blank.</span></div></div>
                <div class="form-group"><label class="col-md-3 control-label">Category</label><div class="col-md-7"><input class="form-control" name="category" value="{if $product}{$product['category']}{else}General{/if}" list="pos_categories"><datalist id="pos_categories">{foreach $categories as $cat}<option value="{$cat}">{/foreach}</datalist></div></div>
                <div class="form-group"><label class="col-md-3 control-label">Description</label><div class="col-md-7"><textarea class="form-control" name="description" rows="3">{if $product}{$product['description']}{/if}</textarea></div></div>
                <div class="form-group"><label class="col-md-3 control-label">Product Type *</label><div class="col-md-7"><select class="form-control" name="type"><option value="stock" {if !$product || $product['type'] eq 'stock'}selected{/if}>Stock Item</option><option value="service" {if $product && $product['type'] eq 'service'}selected{/if}>Service</option></select></div></div>
                <div class="form-group"><label class="col-md-3 control-label">Selling Price *</label><div class="col-md-7"><input class="form-control" type="number" step="0.01" name="price" required value="{if $product}{$product['price']}{else}0{/if}"></div></div>
                <div class="form-group"><label class="col-md-3 control-label">Cost Price</label><div class="col-md-7"><input class="form-control" type="number" step="0.01" name="cost_price" value="{if $product}{$product['cost_price']}{else}0{/if}"></div></div>
                <div class="form-group"><label class="col-md-3 control-label">Initial Stock</label><div class="col-md-7"><input class="form-control" type="number" name="stock" value="{if $product}{$product['stock']}{else}0{/if}"></div></div>
                <div class="form-group"><label class="col-md-3 control-label">Low Stock Alert At</label><div class="col-md-7"><input class="form-control" type="number" name="min_stock" value="{if $product}{$product['min_stock']}{else}5{/if}"></div></div>
                <div class="form-group"><label class="col-md-3 control-label">Status</label><div class="col-md-7"><select class="form-control" name="status"><option value="active" {if !$product || $product['status'] eq 'active'}selected{/if}>Active</option><option value="inactive" {if $product && $product['status'] eq 'inactive'}selected{/if}>Inactive</option></select></div></div>
            </div>
            <div class="box-footer text-right"><button class="btn btn-success"><i class="fa fa-save"></i> Save Product</button></div>
        </form>
    </div>
</div>

{include file="sections/footer.tpl"}
