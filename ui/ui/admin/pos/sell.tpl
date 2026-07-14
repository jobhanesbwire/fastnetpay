{include file="sections/header.tpl"}

<div class="fnp-clients-page">
    <div class="fnp-clients-head">
        <div><span class="fnp-report-kicker"><i class="fa fa-shopping-basket"></i> FASTNETPAY POS</span><h2>POS Terminal</h2><p>Click products to add them to the cart, then checkout.</p></div>
        <div class="fnp-clients-head-actions"><a href="{Text::url('pos/reports')}" class="btn btn-default btn-sm">Reports</a><a href="{Text::url('pos/stock')}" class="btn btn-default btn-sm">Stock</a><a href="{Text::url('pos/dashboard')}" class="btn btn-success btn-sm">Dashboard</a></div>
    </div>
    <div class="row">
        <div class="col-md-7">
            <div class="box box-primary box-solid">
                <div class="box-header"><h3 class="box-title">Products</h3></div>
                <div class="box-body">
                    <input id="pos-search" class="form-control" placeholder="Search products..." style="margin-bottom:12px">
                    <div class="row" id="pos-products">
                        {foreach $products as $p}
                            <div class="col-sm-6 col-md-4 pos-product" data-name="{$p['name']|lower}">
                                <button type="button" class="btn btn-default btn-block pos-add" data-id="{$p['id']}" data-name="{$p['name']|escape}" data-price="{$p['price']}" data-stock="{$p['stock']}" data-type="{$p['type']}">
                                    <strong>{$p['name']}</strong><br>
                                    <span>{Lang::moneyFormat($p['price'])}</span><br>
                                    <small>{if $p['type'] eq 'stock'}Stock: {$p['stock']}{else}Service{/if}</small>
                                </button>
                            </div>
                        {foreachelse}
                            <div class="col-sm-12 text-muted">No active products. Add products first.</div>
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <form method="post" action="{Text::url('pos/sale-post')}" id="pos-sale-form" class="box box-primary box-solid">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <input type="hidden" name="items_json" id="items_json">
                <div class="box-header"><h3 class="box-title">Cart</h3><button type="button" class="btn btn-xs btn-danger pull-right" id="pos-clear">Clear</button></div>
                <div class="box-body">
                    <div id="pos-cart" class="table-responsive"></div>
                    <div class="form-group"><label>Customer</label><select class="form-control select2" name="customer_id" style="width:100%"><option value="0">Walk-in customer</option>{foreach $customers as $c}<option value="{$c['id']}">{$c['username']} - {$c['fullname']}</option>{/foreach}</select></div>
                    <div class="form-group"><label>Payment Method</label><select class="form-control" name="payment_method"><option value="cash">Cash</option><option value="mpesa">M-Pesa</option><option value="balance">Customer Balance</option><option value="card">Card</option><option value="other">Other</option></select></div>
                    <div class="form-group"><label>Discount</label><input class="form-control" type="number" step="0.01" name="discount" id="pos-discount" value="0"></div>
                    <div class="form-group"><label>Cash Tendered</label><input class="form-control" type="number" step="0.01" name="cash_tendered" value="0"></div>
                    <div class="form-group"><label>Balance Amount</label><input class="form-control" type="number" step="0.01" name="balance_amount" value="0"></div>
                    <div class="form-group"><label>Notes</label><input class="form-control" name="notes" placeholder="e.g. Voucher sale"></div>
                    <div class="well"><strong>Total: <span id="pos-total">0.00</span></strong></div>
                </div>
                <div class="box-footer"><button class="btn btn-success btn-block" id="pos-checkout"><i class="fa fa-credit-card"></i> Charge / Checkout</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var cart = {};
    function money(n) { return Number(n || 0).toFixed(2); }
    function render() {
        var rows = '', subtotal = 0, items = [];
        Object.keys(cart).forEach(function (id) {
            var item = cart[id], line = item.qty * item.price;
            subtotal += line;
            items.push({ id: item.id, qty: item.qty });
            rows += '<tr><td>' + item.name + '</td><td><input class="form-control input-sm pos-qty" data-id="' + id + '" type="number" min="1" value="' + item.qty + '"></td><td>' + money(line) + '</td><td><button type="button" class="btn btn-danger btn-xs pos-remove" data-id="' + id + '"><i class="fa fa-trash"></i></button></td></tr>';
        });
        if (!rows) rows = '<tr><td colspan="4" class="text-muted">Cart is empty - click a product to add.</td></tr>';
        $('#pos-cart').html('<table class="table table-condensed"><thead><tr><th>Item</th><th>Qty</th><th>Total</th><th></th></tr></thead><tbody>' + rows + '</tbody></table>');
        var discount = Number($('#pos-discount').val() || 0);
        $('#pos-total').text(money(Math.max(0, subtotal - discount)));
        $('#items_json').val(JSON.stringify(items));
    }
    $('.pos-add').on('click', function () {
        var id = $(this).data('id'), stock = Number($(this).data('stock')), type = $(this).data('type');
        if (!cart[id]) cart[id] = { id: id, name: $(this).data('name'), price: Number($(this).data('price')), qty: 0, stock: stock, type: type };
        if (type === 'stock' && cart[id].qty >= stock) return;
        cart[id].qty++;
        render();
    });
    $('#pos-cart').on('input', '.pos-qty', function () {
        var id = $(this).data('id'), qty = Math.max(1, Number($(this).val() || 1));
        if (cart[id].type === 'stock') qty = Math.min(qty, cart[id].stock);
        cart[id].qty = qty;
        render();
    });
    $('#pos-cart').on('click', '.pos-remove', function () { delete cart[$(this).data('id')]; render(); });
    $('#pos-clear').on('click', function () { cart = {}; render(); });
    $('#pos-discount').on('input', render);
    $('#pos-search').on('input', function () {
        var q = this.value.toLowerCase();
        $('.pos-product').each(function () { $(this).toggle($(this).data('name').indexOf(q) !== -1); });
    });
    $('#pos-sale-form').on('submit', function () {
        render();
        if ($('#items_json').val() === '[]') { alert('Cart is empty'); return false; }
        return true;
    });
    render();
});
</script>

{include file="sections/footer.tpl"}
