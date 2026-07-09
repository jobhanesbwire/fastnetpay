<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)

 **/

try {
    require_once 'init.php';
} catch (Throwable $e) {
    die($e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>');
} catch (Exception $e) {
    die($e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>');
}

function _notify($msg, $type = 'e')
{
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

$ui = new Smarty();
$ui->assign('_kolaps', $_COOKIE['kolaps']);
if (!empty($config['theme']) && $config['theme'] != 'default') {
    $_theme = APP_URL . '/' . $UI_PATH . '/themes/' . $config['theme'];
    $ui->setTemplateDir([
        'custom' => File::pathFixer($UI_PATH . '/ui_custom/'),
        'theme' => File::pathFixer($UI_PATH . '/themes/' . $config['theme']),
        'default' => File::pathFixer($UI_PATH . '/ui/')
    ]);
} else {
    $_theme = APP_URL . '/' . $UI_PATH . '/ui';
    $ui->setTemplateDir([
        'custom' => File::pathFixer($UI_PATH . '/ui_custom/'),
        'default' => File::pathFixer($UI_PATH . '/ui/')
    ]);
}
$ui->assign('_theme', $_theme);
$ui->addTemplateDir($PAYMENTGATEWAY_PATH . File::pathFixer('/ui/'), 'pg');
$ui->addTemplateDir($PLUGIN_PATH . File::pathFixer('/ui/'), 'plugin');
$ui->setCompileDir(File::pathFixer($UI_PATH . '/compiled/'));
$ui->setConfigDir(File::pathFixer($UI_PATH . '/conf/'));
$ui->setCacheDir(File::pathFixer($UI_PATH . '/cache/'));
$ui->assign('app_url', APP_URL);
$ui->assign('_domain', str_replace('www.', '', parse_url(APP_URL, PHP_URL_HOST)));
$ui->assign('_url', APP_URL . '/?_route=');
$ui->assign('_path', __DIR__);
$ui->assign('_c', $config);
$ui->assign('_app_stage', $_app_stage);
$ui->assign('user_language', $_SESSION['user_language']);
if (class_exists('Tenant')) {
    Tenant::assignToSmarty($ui);
}
$ui->assign('UPLOAD_PATH', str_replace($root_path, '',  $UPLOAD_PATH));
$ui->assign('CACHE_PATH', str_replace($root_path, '',  $CACHE_PATH));
$ui->assign('PAGES_PATH', str_replace($root_path, '',  $PAGES_PATH));
$ui->assign('_system_menu', 'dashboard');

function _msglog($type, $msg)
{
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

if (isset($_SESSION['notify'])) {
    $notify = $_SESSION['notify'];
    $ntype = $_SESSION['ntype'];
    $ui->assign('notify', $notify);
    $ui->assign('notify_t', $ntype);
    unset($_SESSION['notify']);
    unset($_SESSION['ntype']);
}

if (!isset($_GET['_route'])) {
    $req = ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $len = strlen(ltrim(parse_url(APP_URL, PHP_URL_PATH), '/'));
    if ($len > 0) {
        $req = ltrim(substr($req, $len), '/');
    }
} else {
    // Routing Engine
    $req = _get('_route');
}

$routes = explode('/', $req);
$ui->assign('_routes', $routes);
$handler = $routes[0];
if ($handler == '') {
    $handler = 'default';
}
if (class_exists('PerformanceProfiler')) {
    PerformanceProfiler::setRoute($req);
}
try {
    if (!empty($_GET['uid'])) {
        $_COOKIE['uid'] = $_GET['uid'];
    }
    $admin = Admin::_info();
    if (class_exists('Tenant')) {
        $ui->assign('_tenant_admin_scope_warning', '');
        if ($admin && Tenant::isTenantRequest() && $admin['user_type'] !== 'SuperAdmin' && (int) ($admin['tenant_id'] ?? 0) !== Tenant::currentId()) {
            Tenant::audit('tenant.access_blocked', 'Admin session tenant did not match requested tenant host.', 'user', (string) $admin['id'], Tenant::currentId(), (int) $admin['id']);
            Admin::removeCookie();
            r2(getUrl('admin'), 'e', Lang::T('Please log in through your ISP tenant domain.'));
        }
        $paymentOnlyRoutes = ['tenant-payment', 'admin', 'logout', 'api'];
        if ($admin && class_exists('SaasBilling') && Tenant::isTenantRequest() && $admin['user_type'] !== 'SuperAdmin' && !SaasBilling::tenantCanLogin(Tenant::current()) && !in_array($handler, $paymentOnlyRoutes, true)) {
            Tenant::audit('tenant.access_blocked_suspended', 'Active tenant admin session blocked because tenant is suspended.', 'user', (string) $admin['id'], Tenant::currentId(), (int) $admin['id']);
            r2(getUrl('tenant-payment'), 'e', SaasBilling::suspensionMessage(Tenant::currentId()));
        }
        if ($admin && Tenant::isTenantRequest() && $admin['user_type'] !== 'SuperAdmin') {
            Tenant::denyTenantAccessToSuperAdminRoutes($handler, $routes);
        }
    }
    $sys_render = $root_path . File::pathFixer('system/controllers/' . $handler . '.php');
    if (file_exists($sys_render)) {
        $menus = array();
        // "name" => $name,
        // "admin" => $admin,
        // "position" => $position,
        // "function" => $function
        $ui->assign('_system_menu', $routes[0]);
        foreach ($menu_registered as $menu) {
            if (class_exists('Tenant') && Tenant::isTenantRequest() && $admin && $admin['user_type'] !== 'SuperAdmin') {
                $tenantAllowedPluginMenus = ['mikrotik_monitor_ui', 'mikrotik_monitor_pppoe', 'mikrotik_monitor_hotspot', 'mikrotik_import_ui', 'mikrotik_import_start_ui'];
                if (!in_array($menu['function'], $tenantAllowedPluginMenus, true)) {
                    continue;
                }
            }
            if ($menu['admin'] && _admin(false)) {
                if (count($menu['auth']) == 0 || in_array($admin['user_type'], $menu['auth'])) {
                    $menus[$menu['position']] .= '<li' . (($routes[1] == $menu['function']) ? ' class="active"' : '') . '><a href="' . getUrl('plugin/' . $menu['function']) . '">';
                    if (!empty($menu['icon'])) {
                        $menus[$menu['position']] .= '<i class="' . $menu['icon'] . '"></i>';
                    }
                    if (!empty($menu['label'])) {
                        $menus[$menu['position']] .= '<span class="pull-right-container">';
                        $menus[$menu['position']] .= '<small class="label pull-right bg-' . $menu['color'] . '">' . $menu['label'] . '</small></span>';
                    }
                    $menus[$menu['position']] .= '<span class="text">' . $menu['name'] . '</span></a></li>';
                }
            } else if (!$menu['admin'] && _auth(false)) {
                $menus[$menu['position']] .= '<li' . (($routes[1] == $menu['function']) ? ' class="active"' : '') . '><a href="' . getUrl('plugin/' . $menu['function']) . '">';
                if (!empty($menu['icon'])) {
                    $menus[$menu['position']] .= '<i class="' . $menu['icon'] . '"></i>';
                }
                if (!empty($menu['label'])) {
                    $menus[$menu['position']] .= '<span class="pull-right-container">';
                    $menus[$menu['position']] .= '<small class="label pull-right bg-' . $menu['color'] . '">' . $menu['label'] . '</small></span>';
                }
                $menus[$menu['position']] .= '<span class="text">' . $menu['name'] . '</span></a></li>';
            }
        }
        foreach ($menus as $k => $v) {
            $ui->assign('_MENU_' . $k, $v);
        }
        unset($menus, $menu_registered);
        include($sys_render);
    } else {
        if( empty($_SERVER["HTTP_SEC_FETCH_DEST"]) || $_SERVER["HTTP_SEC_FETCH_DEST"] != 'document' ){
            // header 404
            header("HTTP/1.0 404 Not Found");
            header("Content-Type: text/html; charset=utf-8");
            echo "404 Not Found";
            die();
        }else{
            r2(getUrl('login'));
        }
    }
} catch (Throwable $e) {
    Message::sendTelegram(
        "Sistem Error.\n" .
            $e->getMessage() . "\n" .
            $e->getTraceAsString()
    );
    $ui->assign("error_summary", Lang::T('The request could not be completed. Please reload the page or return to the dashboard.'));
    $ui->assign("error_debug", $e->getMessage() . "\n\n" . $e->getTraceAsString());
    $ui->assign("error_title", "FASTNETPAY System Notice");
    if (empty($_SESSION['aid'])) {
        $ui->display('customer/error.tpl');
        die();
    }
    $ui->display('admin/error.tpl');
    die();
} catch (Exception $e) {
    Message::sendTelegram(
        "Sistem Error.\n" .
            $e->getMessage() . "\n" .
            $e->getTraceAsString()
    );
    $ui->assign("error_summary", Lang::T('The request could not be completed. Please reload the page or return to the dashboard.'));
    $ui->assign("error_debug", $e->getMessage() . "\n\n" . $e->getTraceAsString());
    $ui->assign("error_title", "FASTNETPAY System Notice");
    if (empty($_SESSION['aid'])) {
        $ui->display('customer/error.tpl');
        die();
    }
    $ui->display('admin/error.tpl');
    die();
}
