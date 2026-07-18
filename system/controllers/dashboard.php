<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Dashboard'));
$ui->assign('_admin', $admin);

if (isset($_GET['refresh'])) {
    $files = scandir($CACHE_PATH);
    foreach ($files as $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (is_file($CACHE_PATH . DIRECTORY_SEPARATOR . $file) && $ext == 'temp') {
            unlink($CACHE_PATH . DIRECTORY_SEPARATOR . $file);
        }
    }
    r2(getUrl('dashboard'), 's', 'Data Refreshed');
}

$tipeUser = _req("user");
if (empty($tipeUser)) {
    $tipeUser = 'Admin';
}
$ui->assign('tipeUser', $tipeUser);

$reset_day = $config['reset_day'];
if (empty($reset_day)) {
    $reset_day = 1;
}
//first day of month
if (date("d") >= $reset_day) {
    $start_date = date('Y-m-' . $reset_day);
} else {
    $start_date = date('Y-m-' . $reset_day, strtotime("-1 MONTH"));
}

$current_date = date('Y-m-d');
$ui->assign('start_date', $start_date);
$ui->assign('current_date', $current_date);

$tipeUser = $admin['user_type'];
if (in_array($tipeUser, ['SuperAdmin', 'Admin'])) {
    $tipeUser = 'Admin';
}

$widgets = ORM::for_table('tbl_widgets')->where("enabled", 1)->where('user', $tipeUser)->order_by_asc("orders")->findArray();
$count = count($widgets);
for ($i = 0; $i < $count; $i++) {
    try{
        if(file_exists($WIDGET_PATH . DIRECTORY_SEPARATOR . $widgets[$i]['widget'].".php")){
            require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . $widgets[$i]['widget'].".php";
            $widgets[$i]['content'] = (new $widgets[$i]['widget'])->getWidget($widgets[$i]);
        }else{
            $widgets[$i]['content'] = "Widget not found";
        }
    } catch (Throwable $e) {
        $widgets[$i]['content'] = $e->getMessage();
    }
}

$ui->assign('widgets', $widgets);
$ui->assign('expiry_health', ExpiryWorker::health($UPLOAD_PATH));
$ui->assign('ops_analytics', fnp_dashboard_ops_analytics($CACHE_PATH));
if (($admin['user_type'] ?? '') === 'SuperAdmin' && class_exists('SaasBilling')) {
    try {
        $ui->assign('saas_analytics', fnp_dashboard_saas_analytics($CACHE_PATH));
    } catch (Throwable $e) {
        $ui->assign('saas_analytics_error', $e->getMessage());
    }
}
run_hook('view_dashboard'); #HOOK
$ui->display('admin/dashboard.tpl');

function fnp_dashboard_table_exists($table)
{
    global $db_name;
    static $tableCache = [];

    if (isset($tableCache[$table])) {
        return $tableCache[$table];
    }

    try {
        $row = ORM::for_table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $db_name)
            ->where('TABLE_NAME', $table)
            ->find_one();
        $tableCache[$table] = (bool) $row;
        return $tableCache[$table];
    } catch (Throwable $e) {
        $tableCache[$table] = false;
        return false;
    }
}

function fnp_dashboard_ops_analytics($cachePath = null)
{
    $cached = fnp_dashboard_read_cache($cachePath, 'dashboard_ops_analytics');
    if ($cached !== null) {
        return $cached;
    }

    $data = [
        'pos_today' => 0,
        'pos_month' => 0,
        'open_tickets' => 0,
        'urgent_tickets' => 0,
        'acs_devices' => 0,
        'pppoe_balance' => 0,
    ];
    if (fnp_dashboard_table_exists('pos_sales')) {
        $data['pos_today'] = (float) Tenant::scopeIfTenant(ORM::for_table('pos_sales')->where_gte('created_at', date('Y-m-d') . ' 00:00:00'))->sum('total');
        $data['pos_month'] = (float) Tenant::scopeIfTenant(ORM::for_table('pos_sales')->where_gte('created_at', date('Y-m-01') . ' 00:00:00'))->sum('total');
    }
    if (fnp_dashboard_table_exists('support_tickets')) {
        $data['open_tickets'] = (int) Tenant::scopeIfTenant(ORM::for_table('support_tickets')->where_in('status', ['open', 'in_progress', 'waiting_customer']))->count();
        $data['urgent_tickets'] = (int) Tenant::scopeIfTenant(ORM::for_table('support_tickets')->where('priority', 'urgent')->where_not_in('status', ['resolved', 'closed']))->count();
    }
    if (fnp_dashboard_table_exists('acs_devices')) {
        $data['acs_devices'] = (int) Tenant::scopeIfTenant(ORM::for_table('acs_devices'))->count();
    }
    if (class_exists('Tenant') && Tenant::hasColumn('tbl_customers', 'balance')) {
        $row = Tenant::scopeIfTenant(ORM::for_table('tbl_customers')->select_expr('COALESCE(SUM(balance),0)', 'balance_total')->where('service_type', 'PPPoE'))->find_one();
        $data['pppoe_balance'] = (float) ($row['balance_total'] ?? 0);
    }
    fnp_dashboard_write_cache($cachePath, 'dashboard_ops_analytics', $data);
    return $data;
}

function fnp_dashboard_saas_analytics($cachePath = null)
{
    $cached = fnp_dashboard_read_cache($cachePath, 'dashboard_saas_analytics');
    if ($cached !== null) {
        return $cached;
    }

    $data = [
        'tenants' => [
            'total' => 0,
            'active' => 0,
            'suspended' => 0,
        ],
        'routers' => [
            'total' => 0,
            'online' => 0,
            'offline' => 0,
        ],
        'clients' => [
            'total' => 0,
            'hotspot' => 0,
            'pppoe' => 0,
        ],
        'financial' => [
            'expected' => 0,
            'overdue' => 0,
        ],
    ];

    if (fnp_dashboard_table_exists('tenants')) {
        $data['tenants']['total'] = (int) ORM::for_table('tenants')->count();
        $data['tenants']['active'] = (int) ORM::for_table('tenants')->where('status', 'active')->count();
        $data['tenants']['suspended'] = (int) ORM::for_table('tenants')->where('status', 'suspended')->count();
    }

    $data['routers']['total'] = (int) ORM::for_table('tbl_routers')->count();
    $data['routers']['online'] = (int) ORM::for_table('tbl_routers')
        ->where('enabled', '1')
        ->where_raw("(status IS NULL OR status = '' OR LOWER(status) IN ('online', 'up', 'active', '1'))")
        ->count();
    $data['routers']['offline'] = max(0, $data['routers']['total'] - $data['routers']['online']);

    $data['clients']['total'] = (int) ORM::for_table('tbl_customers')->count();
    if (class_exists('Tenant') && Tenant::hasColumn('tbl_user_recharges', 'type')) {
        $data['clients']['hotspot'] = (int) ORM::for_table('tbl_user_recharges')->where('type', 'Hotspot')->count();
        $data['clients']['pppoe'] = (int) ORM::for_table('tbl_user_recharges')->where_raw('UPPER(type) = ?', ['PPPOE'])->count();
    }

    if (fnp_dashboard_table_exists('tenant_billing_snapshots')) {
        $snapshot = ORM::for_table('tenant_billing_snapshots')
            ->select_expr('COALESCE(SUM(amount_due), 0)', 'expected')
            ->where('billing_month', date('Y-m'))
            ->find_one();
        $data['financial']['expected'] = (float) ($snapshot['expected'] ?? 0);
    }

    if (fnp_dashboard_table_exists('saas_invoices')) {
        $invoice = ORM::for_table('saas_invoices')
            ->select_expr('COALESCE(SUM(total_due), 0)', 'overdue')
            ->where_not_equal('status', 'paid')
            ->where_lt('grace_until', date('Y-m-d'))
            ->find_one();
        $data['financial']['overdue'] = (float) ($invoice['overdue'] ?? 0);
    }

    fnp_dashboard_write_cache($cachePath, 'dashboard_saas_analytics', $data);
    return $data;
}

function fnp_dashboard_read_cache($cachePath, $name)
{
    $file = fnp_dashboard_cache_file($cachePath, $name);
    if ($file === null || !is_file($file) || (time() - filemtime($file)) > 60) {
        return null;
    }

    $data = @unserialize((string) file_get_contents($file), ['allowed_classes' => false]);
    return is_array($data) ? $data : null;
}

function fnp_dashboard_write_cache($cachePath, $name, $data)
{
    $file = fnp_dashboard_cache_file($cachePath, $name);
    if ($file !== null) {
        @file_put_contents($file, serialize($data), LOCK_EX);
    }
}

function fnp_dashboard_cache_file($cachePath, $name)
{
    if (!$cachePath || !is_dir($cachePath)) {
        return null;
    }

    $tenant = class_exists('Tenant') && Tenant::currentId() ? Tenant::currentId() : 'main';
    $key = preg_replace('/[^A-Za-z0-9_-]/', '_', $name . '_' . $tenant);
    return rtrim($cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key . '.temp';
}
