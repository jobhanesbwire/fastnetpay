<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$action = $routes['1'] ?? '';
$provisionJsonActions = [
    'provision-detect',
    'provision-preview',
    'provision-run',
    'provision-status',
    'provision-final-test',
    'provision-refresh-portal',
    'provision-bootstrap',
];

if (in_array($action, $provisionJsonActions, true) && !Admin::getID()) {
    RouterProvisioning::json([
        'ok' => false,
        'message' => 'Your admin session expired while provisioning. Please log in again, reopen the wizard, and rerun the last step.',
    ], 401);
}

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');

$ui->assign('_admin', $admin);

require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . "MikrotikHotspot.php";

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    if (in_array($action, $provisionJsonActions, true)) {
        RouterProvisioning::json([
            'ok' => false,
            'message' => 'You do not have permission to run router provisioning actions.',
        ], 403);
    }
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

$provisionPostOnly = function ($id) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        RouterProvisioning::json([
            'ok' => false,
            'message' => 'This provisioning action must be run from the wizard. Refresh the wizard page and try again.',
        ], 405);
    }
};

$leafletpickerHeader = <<<EOT
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">
EOT;

switch ($action) {
    case 'add':
        run_hook('view_add_routers'); #HOOK
        $ui->display('admin/routers/add.tpl');
        break;

    case 'provision':
        RouterProvisioning::installSchema();
        $id = (int) ($routes['2'] ?? 0);
        if ($id <= 0 && $_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['draft'] ?? '') !== '1') {
            $firstRouterQuery = ORM::for_table('tbl_routers')->where('enabled', 1)->order_by_asc('id');
            $firstRouterQuery = Tenant::scopeIfTenant($firstRouterQuery);
            $firstRouter = $firstRouterQuery->find_one();
            if (!$firstRouter) {
                $firstRouterQuery = ORM::for_table('tbl_routers')->order_by_asc('id');
                $firstRouterQuery = Tenant::scopeIfTenant($firstRouterQuery);
                $firstRouter = $firstRouterQuery->find_one();
            }
            if ($firstRouter) {
                r2(getUrl('routers/provision/' . $firstRouter['id']));
            }
        }
        $router = RouterProvisioning::router($id);
        $settings = RouterProvisioning::settingsFromRequest($router);
        $ui->assign('_title', 'Router Provisioning Wizard');
        $ui->assign('router', $router);
        $ui->assign('router_id', $router ? (int) $router['id'] : 0);
        $routerListQuery = ORM::for_table('tbl_routers')->order_by_asc('name');
        $routerListQuery = Tenant::scopeIfTenant($routerListQuery);
        $ui->assign('routers', $routerListQuery->find_many());
        $ui->assign('templates', RouterProvisioning::templates());
        $ui->assign('plans', RouterProvisioning::plans());
        $ui->assign('settings', $settings);
        $mpesa = RouterProvisioning::mpesaReadiness();
        $ui->assign('mpesa', $mpesa);
        $ui->assign('mpesa_missing', implode(', ', $mpesa['missing']));
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->assign('xfooter', '<script src="' . Text::runtimeBaseUrl() . '/ui/ui/scripts/fastnetpay-provisioning.js?2026.7.18.3"></script>');
        $ui->display('admin/routers/provision.tpl');
        break;

    case 'provision-detect':
        $id = (int) ($routes['2'] ?? 0);
        $provisionPostOnly($id);
        if (!Csrf::check(_post('csrf_token'))) {
            RouterProvisioning::json(['ok' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.'], 403);
        }
        try {
            $router = RouterProvisioning::router($id);
            $settings = RouterProvisioning::settingsFromRequest($router);
            RouterProvisioning::json(RouterProvisioning::detect($router, $settings));
        } catch (Throwable $e) {
            RouterProvisioning::json(['ok' => false, 'message' => $e->getMessage()], 400);
        }
        break;

    case 'provision-preview':
        $id = (int) ($routes['2'] ?? 0);
        $provisionPostOnly($id);
        if (!Csrf::check(_post('csrf_token'))) {
            RouterProvisioning::json(['ok' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.'], 403);
        }
        try {
            $router = RouterProvisioning::router($id);
            $settings = RouterProvisioning::settingsFromRequest($router);
	            $preview = RouterProvisioning::buildProvisioningScript($router, $settings, RouterProvisioning::plans(), RouterProvisioning::mpesaReadiness(), true);
            RouterProvisioning::json(['ok' => true] + $preview);
        } catch (Throwable $e) {
            RouterProvisioning::json(['ok' => false, 'message' => $e->getMessage()], 400);
        }
        break;

    case 'provision-bootstrap':
        $id = (int) ($routes['2'] ?? 0);
        $provisionPostOnly($id);
        if (!Csrf::check(_post('csrf_token'))) {
            RouterProvisioning::json(['ok' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.'], 403);
        }
        try {
            $router = RouterProvisioning::router($id);
            $settings = RouterProvisioning::settingsFromRequest($router);
            $bootstrap = RouterProvisioning::buildResetRouterBootstrapScript($router, $settings);
            RouterProvisioning::json($bootstrap);
        } catch (Throwable $e) {
            RouterProvisioning::json(['ok' => false, 'message' => $e->getMessage()], 400);
        }
        break;

    case 'provision-run':
        $id = (int) ($routes['2'] ?? 0);
        $provisionPostOnly($id);
        if (!Csrf::check(_post('csrf_token'))) {
            RouterProvisioning::json(['ok' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.'], 403);
        }
        try {
            $router = RouterProvisioning::router($id);
            $settings = RouterProvisioning::settingsFromRequest($router);
            RouterProvisioning::json(RouterProvisioning::queueProvisioning($router, $settings, $admin));
        } catch (Throwable $e) {
            RouterProvisioning::json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    case 'provision-status':
        $id = (int) ($routes['2'] ?? 0);
        $provisionPostOnly($id);
        if (!Csrf::check(_post('csrf_token'))) {
            RouterProvisioning::json(['ok' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.'], 403);
        }
        try {
            RouterProvisioning::json(RouterProvisioning::provisioningStatus($id, (int) _post('run_id')));
        } catch (Throwable $e) {
            RouterProvisioning::json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    case 'provision-final-test':
        $id = (int) ($routes['2'] ?? 0);
        $provisionPostOnly($id);
        if (!Csrf::check(_post('csrf_token'))) {
            RouterProvisioning::json(['ok' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.'], 403);
        }
        try {
            $router = RouterProvisioning::router($id);
            $settings = RouterProvisioning::settingsFromRequest($router);
            RouterProvisioning::json(RouterProvisioning::finalTest($router, $settings));
        } catch (Throwable $e) {
            RouterProvisioning::json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    case 'provision-refresh-portal':
        $id = (int) ($routes['2'] ?? 0);
        $provisionPostOnly($id);
        if (!Csrf::check(_post('csrf_token'))) {
            RouterProvisioning::json(['ok' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.'], 403);
        }
        try {
            $router = RouterProvisioning::router($id);
            $settings = RouterProvisioning::settingsFromRequest($router);
            RouterProvisioning::json(RouterProvisioning::refreshPortalFilesOnly($router, $settings, $admin));
        } catch (Throwable $e) {
            RouterProvisioning::json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    case 'provision-logs':
        RouterProvisioning::installSchema();
        $id = (int) ($routes['2'] ?? 0);
        $router = RouterProvisioning::router($id);
        $runs = RouterProvisioning::runs($id);
        $runs_with_steps = [];
        foreach ($runs as $run) {
            $runs_with_steps[] = [
                'run' => $run,
                'steps' => RouterProvisioning::steps($run['id']),
            ];
        }
        $ui->assign('_title', 'Router Provisioning Logs');
        $ui->assign('router', $router);
        $ui->assign('router_id', $id);
        $ui->assign('runs_with_steps', $runs_with_steps);
        $ui->display('admin/routers/provision-logs.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = Tenant::scopeIfTenant(ORM::for_table('tbl_routers'))->find_one($id);
        if (!$d) {
            $fallbackQuery = ORM::for_table('tbl_routers')->where_equal('name', _get('name'));
            $fallbackQuery = Tenant::scopeIfTenant($fallbackQuery);
            $d = $fallbackQuery->find_one();
        }
        $ui->assign('xheader', $leafletpickerHeader);
        if ($d) {
            $ui->assign('d', $d);
            run_hook('view_router_edit'); #HOOK
            $ui->display('admin/routers/edit.tpl');
        } else {
            r2(getUrl('routers/list'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        $id  = $routes['2'];
        run_hook('router_delete'); #HOOK
        $d = Tenant::scopeIfTenant(ORM::for_table('tbl_routers'))->find_one($id);
        if ($d) {
            $d->delete();
            r2(getUrl('routers/list'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'add-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::Length($name, 30, 1) == false) {
            $msg .= 'Name should be between 1 to 30 characters' . '<br>';
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }

            $routerIpQuery = ORM::for_table('tbl_routers')->where('ip_address', $ip_address);
            $routerIpQuery = Tenant::scopeIfTenant($routerIpQuery);
            $d = $routerIpQuery->find_one();
            if ($d) {
                $msg .= Lang::T('IP Router Already Exist') . '<br>';
            }
        }
        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            run_hook('add_router'); #HOOK
            if (_post("testIt")) {
                (new MikrotikHotspot())->getClient($ip_address, $username, $password);
            }
            $d = ORM::for_table('tbl_routers')->create();
            Tenant::stamp($d, null, 'tbl_routers');
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
            $d->description = $description;
            $d->enabled = $enabled;
            $d->save();

            r2(getUrl('routers/edit/') . $d->id(), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('routers/add'), 'e', $msg);
        }
        break;


    case 'edit-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
        $coordinates = _post('coordinates');
        $coverage = _post('coverage');
        $enabled = $_POST['enabled'];
        $msg = '';
        if (Validator::Length($name, 30, 4) == false) {
            $msg .= 'Name should be between 5 to 30 characters' . '<br>';
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }

        $id = _post('id');
        $d = Tenant::scopeIfTenant(ORM::for_table('tbl_routers'))->find_one($id);
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($d['name'] != $name) {
            $routerNameQuery = ORM::for_table('tbl_routers')->where('name', $name)->where_not_equal('id', $id);
            $routerNameQuery = Tenant::scopeIfTenant($routerNameQuery);
            $c = $routerNameQuery->find_one();
            if ($c) {
                $msg .= 'Name Already Exists<br>';
            }
        }
        $oldname = $d['name'];

        if($enabled || _post("testIt")){
            if ($d['ip_address'] != $ip_address) {
                $routerIpQuery = ORM::for_table('tbl_routers')->where('ip_address', $ip_address)->where_not_equal('id', $id);
                $routerIpQuery = Tenant::scopeIfTenant($routerIpQuery);
                $c = $routerIpQuery->find_one();
                if ($c) {
                    $msg .= 'IP Already Exists<br>';
                }
            }
        }

        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            run_hook('router_edit'); #HOOK
            if (_post("testIt")) {
                (new MikrotikHotspot())->getClient($ip_address, $username, $password);
            }
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
            $d->description = $description;
            $d->coordinates = $coordinates;
            $d->coverage = $coverage;
            $d->enabled = $enabled;
            $d->save();
            if ($name != $oldname) {
                $p = Tenant::scopeIfTenant(ORM::for_table('tbl_plans')->where('routers', $oldname))->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = Tenant::scopeIfTenant(ORM::for_table('tbl_payment_gateway')->where('routers', $oldname))->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = Tenant::scopeIfTenant(ORM::for_table('tbl_pool')->where('routers', $oldname))->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = Tenant::scopeIfTenant(ORM::for_table('tbl_transactions')->where('routers', $oldname))->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = Tenant::scopeIfTenant(ORM::for_table('tbl_user_recharges')->where('routers', $oldname))->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = Tenant::scopeIfTenant(ORM::for_table('tbl_voucher')->where('routers', $oldname))->find_result_set();
                $p->set('routers', $name);
                $p->save();
            }
            r2(getUrl('routers/list'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('routers/edit/') . $id, 'e', $msg);
        }
        break;

    default:

        $name = _post('name');
        $name = _post('name');
        $query = ORM::for_table('tbl_routers')->order_by_desc('id');
        $query = Tenant::scopeIfTenant($query);
        if ($name != '') {
            $query->where_like('name', '%' . $name . '%');
        }
        $d = Paginator::findMany($query, ['name' => $name]);
        $ui->assign('d', $d);
        run_hook('view_list_routers'); #HOOK
        $ui->display('admin/routers/list.tpl');
        break;
}
