<?php

_admin();
$admin = Admin::_info();
if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

$ui->assign('_title', 'Point of Sale');
$ui->assign('_system_menu', 'pos');
$ui->assign('_admin', $admin);

fnp_pos_ensure_schema();
$action = $routes['1'] ?? 'sell';

switch ($action) {
    case 'dashboard':
        $ui->assign('stats', fnp_pos_stats());
        $ui->assign('top_products', fnp_pos_top_products());
        $ui->assign('low_stock', fnp_pos_low_stock());
        $ui->display('admin/pos/dashboard.tpl');
        break;

    case 'product-list':
        $name = trim((string) _req('name'));
        $category = trim((string) _req('category'));
        $type = trim((string) _req('type'));
        $status = trim((string) _req('status'));
        $query = ORM::for_table('pos_products')->order_by_desc('id');
        $query = Tenant::scopeIfTenant($query);
        if ($name !== '') {
            $query->where_like('name', '%' . $name . '%');
        }
        if ($category !== '') {
            $query->where('category', $category);
        }
        if ($type !== '') {
            $query->where('type', $type);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        $ui->assign('products', Paginator::findMany($query, [], 30, http_build_query(compact('name', 'category', 'type', 'status'))));
        $ui->assign('categories', fnp_pos_categories());
        $ui->assign('name', $name);
        $ui->assign('category', $category);
        $ui->assign('type', $type);
        $ui->assign('status', $status);
        $ui->display('admin/pos/product-list.tpl');
        break;

    case 'product-add':
    case 'product-edit':
        $product = null;
        if ($action === 'product-edit') {
            $product = Tenant::scopeIfTenant(ORM::for_table('pos_products'))->find_one((int) ($routes['2'] ?? 0));
        }
        $ui->assign('product', $product);
        $ui->assign('categories', fnp_pos_categories());
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/pos/product-form.tpl');
        break;

    case 'product-save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('pos/product-list'), 'e', Lang::T('Invalid or expired form token'));
        }
        $id = (int) _post('id');
        $row = $id ? Tenant::scopeIfTenant(ORM::for_table('pos_products'))->find_one($id) : ORM::for_table('pos_products')->create();
        if (!$row) {
            r2(getUrl('pos/product-list'), 'e', Lang::T('Product not found'));
        }
        $productName = trim((string) _post('name'));
        if ($productName === '') {
            r2(getUrl($id ? 'pos/product-edit/' . $id : 'pos/product-add'), 'e', Lang::T('Product name is required'));
        }
        $sku = trim((string) Text::alphanumeric((string) _post('sku'), '-_./'));
        if ($sku === '') {
            $sku = $id && trim((string) $row['sku']) !== '' ? (string) $row['sku'] : fnp_pos_generate_sku($productName);
        }
        $category = fnp_pos_clean_text(_post('category') ?: 'General', 120);
        if ($category === '') {
            $category = 'General';
        }
        Tenant::stamp($row, null, 'pos_products');
        $row->name = fnp_pos_clean_text($productName, 180);
        $row->sku = $sku;
        $row->category = $category;
        $row->description = fnp_pos_clean_text(_post('description'), 1000);
        $row->type = in_array(_post('type'), ['stock', 'service'], true) ? _post('type') : 'stock';
        $row->price = (float) _post('price');
        $row->cost_price = (float) _post('cost_price');
        $row->stock = (int) _post('stock');
        $row->min_stock = max(0, (int) _post('min_stock'));
        $row->status = in_array(_post('status'), ['active', 'inactive'], true) ? _post('status') : 'active';
        $row->updated_at = date('Y-m-d H:i:s');
        if (!$id) {
            $row->created_at = date('Y-m-d H:i:s');
            $row->created_by = (int) $admin['id'];
        }
        $row->save();
        r2(getUrl('pos/product-list'), 's', Lang::T('Product saved successfully'));

    case 'stock':
        $filter = _req('filter');
        $search = trim((string) _req('search'));
        $query = ORM::for_table('pos_products')->where('type', 'stock')->order_by_asc('name');
        $query = Tenant::scopeIfTenant($query);
        if ($search !== '') {
            $query->where_like('name', '%' . $search . '%');
        }
        if ($filter === 'low') {
            $query->where_raw('stock <= min_stock AND stock > 0');
        } elseif ($filter === 'out') {
            $query->where_lte('stock', 0);
        } elseif ($filter === 'in') {
            $query->where_gt('stock', 0);
        }
        $ui->assign('products', Paginator::findMany($query, [], 30, http_build_query(compact('filter', 'search'))));
        $ui->assign('all_products', Tenant::scopeIfTenant(ORM::for_table('pos_products')->where('type', 'stock')->order_by_asc('name'))->find_array());
        $ui->assign('movements', Tenant::scopeIfTenant(ORM::for_table('pos_stock_movements')->order_by_desc('id'))->limit(20)->find_array());
        $ui->assign('filter', $filter);
        $ui->assign('search', $search);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/pos/stock.tpl');
        break;

    case 'stock-post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('pos/stock'), 'e', Lang::T('Invalid or expired form token'));
        }
        $product = Tenant::scopeIfTenant(ORM::for_table('pos_products'))->find_one((int) _post('product_id'));
        if (!$product) {
            r2(getUrl('pos/stock'), 'e', Lang::T('Product not found'));
        }
        $qty = max(0, (int) _post('quantity'));
        $old = (int) $product['stock'];
        $type = in_array(_post('type'), ['in', 'out', 'adjustment'], true) ? _post('type') : 'in';
        $new = $type === 'in' ? $old + $qty : ($type === 'out' ? max(0, $old - $qty) : $qty);
        $product->stock = $new;
        $product->updated_at = date('Y-m-d H:i:s');
        $product->save();
        fnp_pos_stock_movement((int) $product['id'], $type, $qty, $old, $new, _post('notes'), $admin);
        r2(getUrl('pos/stock'), 's', Lang::T('Stock updated'));

    case 'sell':
        $ui->assign('products', Tenant::scopeIfTenant(ORM::for_table('pos_products')->where('status', 'active')->order_by_asc('name'))->find_array());
        $ui->assign('customers', Tenant::scopeIfTenant(ORM::for_table('tbl_customers')->select('id')->select('username')->select('fullname')->order_by_asc('username'))->limit(500)->find_array());
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/pos/sell.tpl');
        break;

    case 'sale-post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('pos/sell'), 'e', Lang::T('Invalid or expired form token'));
        }
        $items = json_decode((string) _post('items_json'), true);
        if (!is_array($items) || count($items) === 0) {
            r2(getUrl('pos/sell'), 'e', Lang::T('Cart is empty'));
        }
        $saleId = fnp_pos_create_sale($items, $admin);
        r2(getUrl('pos/history'), 's', 'Sale #' . $saleId . ' completed');

    case 'reports':
    case 'history':
        $dateFrom = _req('date_from', date('Y-m-01'));
        $dateTo = _req('date_to', date('Y-m-d'));
        $payment = _req('payment');
        $status = _req('status');
        $query = ORM::for_table('pos_sales')->where_gte('created_at', $dateFrom . ' 00:00:00')->where_lte('created_at', $dateTo . ' 23:59:59')->order_by_desc('id');
        $query = Tenant::scopeIfTenant($query);
        if ($payment !== '') {
            $query->where('payment_method', $payment);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        $sales = Paginator::findMany($query, [], 50, http_build_query(compact('dateFrom', 'dateTo', 'payment', 'status')));
        $summary = fnp_pos_sales_summary($dateFrom, $dateTo, $payment, $status);
        $ui->assign('sales', $sales);
        $ui->assign('summary', $summary);
        $ui->assign('date_from', $dateFrom);
        $ui->assign('date_to', $dateTo);
        $ui->assign('payment', $payment);
        $ui->assign('status', $status);
        $ui->display($action === 'history' ? 'admin/pos/history.tpl' : 'admin/pos/reports.tpl');
        break;
}

function fnp_pos_ensure_schema()
{
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS pos_products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NULL,
        name VARCHAR(180) NOT NULL,
        sku VARCHAR(80) NOT NULL DEFAULT '',
        category VARCHAR(120) NOT NULL DEFAULT 'General',
        description TEXT NULL,
        type VARCHAR(24) NOT NULL DEFAULT 'stock',
        price DECIMAL(14,2) NOT NULL DEFAULT 0,
        cost_price DECIMAL(14,2) NOT NULL DEFAULT 0,
        stock INT NOT NULL DEFAULT 0,
        min_stock INT NOT NULL DEFAULT 5,
        status VARCHAR(24) NOT NULL DEFAULT 'active',
        created_by INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        INDEX idx_tenant_status (tenant_id, status),
        INDEX idx_category (category),
        INDEX idx_sku (sku)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS pos_sales (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NULL,
        sale_number VARCHAR(48) NOT NULL,
        customer_id INT UNSIGNED NOT NULL DEFAULT 0,
        payment_method VARCHAR(32) NOT NULL DEFAULT 'cash',
        subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
        discount DECIMAL(14,2) NOT NULL DEFAULT 0,
        total DECIMAL(14,2) NOT NULL DEFAULT 0,
        cash_tendered DECIMAL(14,2) NOT NULL DEFAULT 0,
        balance_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        notes VARCHAR(255) NOT NULL DEFAULT '',
        status VARCHAR(24) NOT NULL DEFAULT 'paid',
        created_by INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NULL,
        INDEX idx_tenant_created (tenant_id, created_at),
        INDEX idx_payment (payment_method),
        UNIQUE KEY uniq_sale_number (sale_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS pos_sale_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NULL,
        sale_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        product_name VARCHAR(180) NOT NULL,
        qty INT NOT NULL DEFAULT 1,
        price DECIMAL(14,2) NOT NULL DEFAULT 0,
        total DECIMAL(14,2) NOT NULL DEFAULT 0,
        INDEX idx_sale (sale_id),
        INDEX idx_tenant_sale (tenant_id, sale_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS pos_stock_movements (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NULL,
        product_id INT UNSIGNED NOT NULL,
        movement_type VARCHAR(24) NOT NULL,
        qty INT NOT NULL DEFAULT 0,
        old_stock INT NOT NULL DEFAULT 0,
        new_stock INT NOT NULL DEFAULT 0,
        notes VARCHAR(255) NOT NULL DEFAULT '',
        created_by INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NULL,
        INDEX idx_tenant_product (tenant_id, product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function fnp_pos_generate_sku($name)
{
    $prefix = strtoupper(substr(Text::alphanumeric((string) $name), 0, 4));
    if ($prefix === '') {
        $prefix = 'POS';
    }
    return 'POS-' . $prefix . '-' . strtoupper(substr(sha1($name . '|' . microtime(true) . '|' . rand()), 0, 8));
}

function fnp_pos_clean_text($value, $maxLength = 255)
{
    $text = strip_tags((string) $value);
    $text = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim(substr($text, 0, $maxLength));
}

function fnp_pos_categories()
{
    $query = ORM::for_table('pos_products')->select('category')->distinct('category')->where_not_equal('category', '');
    return array_column(Tenant::scopeIfTenant($query)->find_array(), 'category');
}

function fnp_pos_stats()
{
    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');
    return [
        'today' => (float) Tenant::scopeIfTenant(ORM::for_table('pos_sales')->where_gte('created_at', $today . ' 00:00:00'))->sum('total'),
        'month' => (float) Tenant::scopeIfTenant(ORM::for_table('pos_sales')->where_gte('created_at', $monthStart . ' 00:00:00'))->sum('total'),
        'all_time' => (float) Tenant::scopeIfTenant(ORM::for_table('pos_sales'))->sum('total'),
        'products' => (int) Tenant::scopeIfTenant(ORM::for_table('pos_products'))->count(),
        'low_stock' => (int) Tenant::scopeIfTenant(ORM::for_table('pos_products')->where_raw('stock <= min_stock AND type = ?', ['stock']))->count(),
    ];
}

function fnp_pos_top_products()
{
    $query = ORM::for_table('pos_sale_items')
        ->select('product_name')
        ->select_expr('SUM(qty)', 'qty')
        ->select_expr('SUM(total)', 'total')
        ->where_gte('id', 0)
        ->group_by('product_name')
        ->order_by_desc('qty')
        ->limit(10);
    return Tenant::scopeIfTenant($query)->find_array();
}

function fnp_pos_low_stock()
{
    return Tenant::scopeIfTenant(ORM::for_table('pos_products')->where('type', 'stock')->where_raw('stock <= min_stock')->order_by_asc('stock'))->limit(10)->find_array();
}

function fnp_pos_stock_movement($productId, $type, $qty, $old, $new, $notes, $admin)
{
    $row = ORM::for_table('pos_stock_movements')->create();
    Tenant::stamp($row, null, 'pos_stock_movements');
    $row->product_id = $productId;
    $row->movement_type = $type;
    $row->qty = $qty;
    $row->old_stock = $old;
    $row->new_stock = $new;
    $row->notes = Text::sanitize($notes);
    $row->created_by = (int) $admin['id'];
    $row->created_at = date('Y-m-d H:i:s');
    $row->save();
}

function fnp_pos_create_sale($items, $admin)
{
    $subtotal = 0;
    $clean = [];
    foreach ($items as $item) {
        $product = Tenant::scopeIfTenant(ORM::for_table('pos_products'))->find_one((int) ($item['id'] ?? 0));
        if (!$product || $product['status'] !== 'active') {
            continue;
        }
        $qty = max(1, (int) ($item['qty'] ?? 1));
        if ($product['type'] === 'stock' && (int) $product['stock'] < $qty) {
            r2(getUrl('pos/sell'), 'e', 'Insufficient stock for ' . $product['name']);
        }
        $line = $qty * (float) $product['price'];
        $subtotal += $line;
        $clean[] = [$product, $qty, $line];
    }
    if (!$clean) {
        r2(getUrl('pos/sell'), 'e', Lang::T('Cart is empty'));
    }
    $discount = max(0, (float) _post('discount'));
    $total = max(0, $subtotal - $discount);
    $sale = ORM::for_table('pos_sales')->create();
    Tenant::stamp($sale, null, 'pos_sales');
    $sale->sale_number = 'POS-' . date('ymdHis') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
    $sale->customer_id = (int) _post('customer_id');
    $sale->payment_method = in_array(_post('payment_method'), ['cash', 'balance', 'mpesa', 'card', 'other'], true) ? _post('payment_method') : 'cash';
    $sale->subtotal = $subtotal;
    $sale->discount = $discount;
    $sale->total = $total;
    $sale->cash_tendered = (float) _post('cash_tendered');
    $sale->balance_amount = (float) _post('balance_amount');
    $sale->notes = Text::sanitize(_post('notes'));
    $sale->status = 'paid';
    $sale->created_by = (int) $admin['id'];
    $sale->created_at = date('Y-m-d H:i:s');
    $sale->save();

    foreach ($clean as $entry) {
        [$product, $qty, $line] = $entry;
        $item = ORM::for_table('pos_sale_items')->create();
        Tenant::stamp($item, null, 'pos_sale_items');
        $item->sale_id = (int) $sale->id();
        $item->product_id = (int) $product['id'];
        $item->product_name = $product['name'];
        $item->qty = $qty;
        $item->price = (float) $product['price'];
        $item->total = $line;
        $item->save();
        if ($product['type'] === 'stock') {
            $old = (int) $product['stock'];
            $product->stock = max(0, $old - $qty);
            $product->save();
            fnp_pos_stock_movement((int) $product['id'], 'sale', $qty, $old, (int) $product['stock'], 'POS sale ' . $sale['sale_number'], $admin);
        }
    }
    _log('[' . $admin['username'] . ']: completed POS sale ' . $sale['sale_number'] . ' for ' . Lang::moneyFormat($total), $admin['user_type'], $admin['id']);
    return (int) $sale->id();
}

function fnp_pos_sales_summary($from, $to, $payment = '', $status = '')
{
    $query = ORM::for_table('pos_sales')->where_gte('created_at', $from . ' 00:00:00')->where_lte('created_at', $to . ' 23:59:59');
    $query = Tenant::scopeIfTenant($query);
    if ($payment !== '') {
        $query->where('payment_method', $payment);
    }
    if ($status !== '') {
        $query->where('status', $status);
    }
    $total = (float) $query->sum('total');
    $countQuery = ORM::for_table('pos_sales')->where_gte('created_at', $from . ' 00:00:00')->where_lte('created_at', $to . ' 23:59:59');
    $countQuery = Tenant::scopeIfTenant($countQuery);
    if ($payment !== '') {
        $countQuery->where('payment_method', $payment);
    }
    if ($status !== '') {
        $countQuery->where('status', $status);
    }
    $count = (int) $countQuery->count();
    return ['total' => $total, 'count' => $count, 'average' => $count ? $total / $count : 0];
}
