<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * used for ajax
 **/

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$ui->assign('_admin', $admin);

switch ($action) {
    case 'pool':
        $routers = _get('routers');
        if (empty($routers)) {
            $query = Tenant::scopeIfTenant(ORM::for_table('tbl_pool'));
            $d = $query->find_many();
        } else {
            $query = ORM::for_table('tbl_pool')->where('routers', $routers);
            $query = Tenant::scopeIfTenant($query);
            $d = $query->find_many();
        }
        $ui->assign('routers', $routers);
        $ui->assign('d', $d);
        $ui->display('admin/autoload/pool.tpl');
        break;
    case 'bw_name':
        $bw = Tenant::scopeIfTenant(ORM::for_table('tbl_bandwidth')->select("name_bw"))->find_one($routes['2']);
        echo $bw['name_bw'];
        die();
    case 'balance':
        $customer = Tenant::scopeIfTenant(ORM::for_table('tbl_customers')->select("balance"))->find_one($routes['2']);
        $balance = $customer ? $customer['balance'] : 0;
        if ($routes['3'] == '1') {
            echo Lang::moneyFormat($balance);
        } else {
            echo $balance;
        }
        die();
    case 'server':
        $query = ORM::for_table('tbl_routers')->where('enabled', '1');
        $query = Tenant::scopeIfTenant($query);
        $d = $query->find_many();
        $ui->assign('d', $d);

        $ui->display('admin/autoload/server.tpl');
        break;
    case 'pppoe_ip_used':
        if (!empty(_get('ip'))) {
            $cs = ORM::for_table('tbl_customers')
                ->select("username")
                ->where_not_equal('id', _get('id'))
                ->where("pppoe_ip", _get('ip'));
            $cs = Tenant::scopeIfTenant($cs)->findArray();
            if (count($cs) > 0) {
                $c = array_column($cs, 'username');
                die(Lang::T("IP has been used by") . ' : ' . implode(", ", $c));
            }
        }
        die();
    case 'pppoe_username_used':
        if (!empty(_get('u'))) {
            $cs = ORM::for_table('tbl_customers')
                ->select("username")
                ->where_not_equal('id', _get('id'))
                ->where("pppoe_username", _get('u'));
            $cs = Tenant::scopeIfTenant($cs)->findArray();
            if (count($cs) > 0) {
                $c = array_column($cs, 'username');
                die(Lang::T("Username has been used by") . ' : ' . implode(", ", $c));
            }
        }
        die();
    case 'plan':
        $server = _post('server');
        $jenis = _post('jenis');
        if (in_array($admin['user_type'], array('SuperAdmin', 'Admin'))) {
            switch ($server) {
                case 'radius':
                    $query = ORM::for_table('tbl_plans')->where('is_radius', 1)->where('type', $jenis);
                    $d = Tenant::scopeIfTenant($query)->find_many();
                    break;
                case '':
                    break;
                default:
                    $query = ORM::for_table('tbl_plans')->where('routers', $server)->where('type', $jenis);
                    $d = Tenant::scopeIfTenant($query)->find_many();
                    break;
            }
        } else {
            switch ($server) {
                case 'radius':
                    $query = ORM::for_table('tbl_plans')->where('is_radius', 1)->where('type', $jenis);
                    $d = Tenant::scopeIfTenant($query)->find_many();
                    break;
                case '':
                    break;
                default:
                    $query = ORM::for_table('tbl_plans')->where('routers', $server)->where('type', $jenis);
                    $d = Tenant::scopeIfTenant($query)->find_many();
                    break;
            }
        }
        $ui->assign('d', $d);

        $ui->display('admin/autoload/plan.tpl');
        break;
    case 'customer_is_active':
        if ($config['check_customer_online'] == 'yes') {
            $customerQuery = ORM::for_table('tbl_customers')->where('username', $routes['2']);
            $c = Tenant::scopeIfTenant($customerQuery)->find_one();
            $p = Tenant::scopeIfTenant(ORM::for_table('tbl_plans'))->find_one($routes['3']);
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'Demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    try {
                        //don't wait more than 5 seconds for response from device, otherwise we get timeout error.
                        ini_set('default_socket_timeout', 5);
                        if ((new $p['device'])->online_customer($c, $p['routers'])) {
                            echo '<span style="color: green;" title="online">&bull;</span>';
                        }else{
                            echo '<span style="color: yellow;" title="offline">&bull;</span>';
                        }
                    } catch (Exception $e) {
                        echo '<span style="color: red;" title="'.$e->getMessage().'">&bull;</span>';
                    }
                }
            }
        }
        break;
    case 'plan_is_active':
        $billQuery = ORM::for_table('tbl_user_recharges')->where('customer_id', $routes['2']);
        $billQuery = Tenant::scopeIfTenant($billQuery);
        $ds = $billQuery->find_array();
        if ($ds) {
            $ps = [];
            $c = Tenant::scopeIfTenant(ORM::for_table('tbl_customers'))->find_one($routes['2']);
            foreach ($ds as $d) {
                if ($d['status'] == 'on') {
                    if ($config['check_customer_online'] == 'yes') {
                        $p = Tenant::scopeIfTenant(ORM::for_table('tbl_plans'))->find_one($d['plan_id']);
                        $dvc = Package::getDevice($p);
                        $status = "";
                        if ($_app_stage != 'Demo') {
                            if (file_exists($dvc)) {
                                require_once $dvc;
                                try {
                                    //don't wait more than 5 seconds for response from device, otherwise we get timeout error.
                                    ini_set('default_socket_timeout', 5);
                                    if ((new $p['device'])->online_customer($c, $p['routers'])) {
                                        $status = '<span style="color: green;" title="online">&bull;</span>';
                                    }else{
                                        $status = '<span style="color: yellow;" title="offline">&bull;</span>';
                                    }
                                } catch (Exception $e) {
                                    $status = '<span style="color: red;" title="'.$e->getMessage().'">&bull;</span>';
                                }
                            }
                        }
                    }
                    $ps[] = ('<span class="label label-primary m-1" title="Expired ' . Lang::dateAndTimeFormat($d['expiration'], $d['time']) . '">' . $d['namebp'] . ' ' . $status . '</span>');
                } else {
                    $ps[] = ('<span class="label label-danger m-1" title="Expired ' . Lang::dateAndTimeFormat($d['expiration'], $d['time']) . '">' . $d['namebp'] . '</span>');
                }
            }
            echo implode("<br>", $ps);
        } else {
            die('');
        }
        break;
    case 'customer_select2':

        $s = addslashes(_get('s'));
        if (empty($s)) {
            $query = Tenant::scopeIfTenant(ORM::for_table('tbl_customers')->limit(30));
            $c = $query->find_many();
        } else {
            $query = ORM::for_table('tbl_customers')->where_raw("(`username` LIKE '%$s%' OR `fullname` LIKE '%$s%' OR `phonenumber` LIKE '%$s%' OR `email` LIKE '%$s%')")->limit(30);
            $query = Tenant::scopeIfTenant($query);
            $c = $query->find_many();
        }
        header('Content-Type: application/json');
        foreach ($c as $cust) {
            $json[] = [
                'id' => $cust['id'],
                'text' => $cust['username'] . ' - ' . $cust['fullname'] . ' - ' . $cust['email']
            ];
        }
        echo json_encode(['results' => $json]);
        die();
    default:
        $ui->display('admin/404.tpl');
}
