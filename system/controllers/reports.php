<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Reports'));
$ui->assign('_system_menu', 'reports');

$action = $routes['1'];
$ui->assign('_admin', $admin);

$mdate = date('Y-m-d');
$mtime = date('H:i:s');
$tdate = date('Y-m-d', strtotime('today - 30 days'));
$firs_day_month = date('Y-m-01');
$this_week_start = date('Y-m-d', strtotime('previous sunday'));
$before_30_days = date('Y-m-d', strtotime('today - 30 days'));
$month_n = date('n');

switch ($action) {
    case 'ajax':
        $data = $routes['2'];
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
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $tps = ($_GET['tps']) ? $_GET['tps'] : $types;
        $plans = reports_distinct_transactions('plan_name');
        $plns = ($_GET['plns']) ? $_GET['plns'] : $plans;
        $methods = reports_distinct_methods();
        $mts = ($_GET['mts']) ? $_GET['mts'] : $methods;
        $routers = reports_distinct_transactions('routers');
        $rts = ($_GET['rts']) ? $_GET['rts'] : $routers;
        $result = [];
        switch ($data) {
            case 'type':
                foreach ($tps as $tp) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where('type', $tp);
                    $query = Tenant::scopeIfTenant($query);
                    if (count($mts) > 0) {
                        if (count($mts) != count($methods)) {
                            $w = [];
                            $v = [];
                            foreach ($mts as $mt) {
                                $w[] ='method';
                                $v[] = "$mt - %";
                            }
                            $query->where_likes($w, $v);
                        }
                    }
                    if (count($rts) > 0) {
                        $query->where_in('routers', $rts);
                    }
                    if (count($plns) > 0) {
                        $query->where_in('plan_name', $plns);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$tp ($count)";
                    }
                }
                break;
            case 'plan':
                foreach ($plns as $pln) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where('plan_name', $pln);
                    $query = Tenant::scopeIfTenant($query);
                    if (count($tps) > 0) {
                        $query->where_in('type', $tps);
                    }
                    if (count($mts) > 0) {
                        if (count($mts) != count($methods)) {
                            $w = [];
                            $v = [];
                            foreach ($mts as $mt) {
                                $w[] ='method';
                                $v[] = "$mt - %";
                            }
                            $query->where_likes($w, $v);
                        }
                    }
                    if (count($rts) > 0) {
                        $query->where_in('routers', $rts);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$pln ($count)";
                    }
                }
                break;
            case 'method':
                foreach ($mts as $mt) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where_like('method', "$mt - %");
                    $query = Tenant::scopeIfTenant($query);
                    if (count($tps) > 0) {
                        $query->where_in('type', $tps);
                    }
                    if (count($rts) > 0) {
                        $query->where_in('routers', $rts);
                    }
                    if (count($plns) > 0) {
                        $query->where_in('plan_name', $plns);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$mt ($count)";
                    }
                }
                break;
            case 'router':
                foreach ($rts as $rt) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where('routers', $rt);
                    $query = Tenant::scopeIfTenant($query);
                    if (count($tps) > 0) {
                        $query->where_in('type', $tps);
                    }
                    if (count($plns) > 0) {
                        $query->where_in('plan_name', $plns);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$rt ($count)";
                    }
                }
                break;
            case 'line':
                $query = ORM::for_table('tbl_transactions')
                    ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                    ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                    ->order_by_desc('id');
                $query = Tenant::scopeIfTenant($query);
                if (count($tps) > 0) {
                    $query->where_in('type', $tps);
                }
                if (count($mts) > 0) {
                    if (count($mts) != count($methods)) {
                        $w = [];
                        $v = [];
                        foreach ($mts as $mt) {
                            $w[] ='method';
                            $v[] = "$mt - %";
                        }
                        $query->where_likes($w, $v);
                    }
                }
                if (count($rts) > 0) {
                    $query->where_in('routers', $rts);
                }
                if (count($plns) > 0) {
                    $query->where_in('plan_name', $plns);
                }
                $datas = $query->find_array();
                $period = new DatePeriod(
                    new DateTime($sd),
                    new DateInterval('P1D'),
                    new DateTime($ed)
                );
                $pos = 0;
                $dates = [];
                foreach ($period as $key => $value) {
                    $dates[] = $value->format('Y-m-d');
                }
                $dates = array_reverse($dates);
                $result = [];
                $temp;
                foreach ($dates as $date) {
                    $result['labels'][] = $date;
                    // type
                    foreach ($tps as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && $data['type'] == $key) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }
                    //plan
                    foreach ($plns as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && $data['plan_name'] == $key) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }
                    //method
                    foreach ($mts as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && strpos($data['method'], $key) !== false) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }

                    foreach ($rts as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && $data['routers'] == $key) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }
                    $pos++;
                    if ($pos > 29) {
                        // only 30days
                        break;
                    }
                }
                foreach ($temp as $key => $value) {
                    $array = ['label' => $key];
                    $total = 0;
                    foreach ($value as $k => $v) {
                        $total += $v;
                        $array['data'][] = $v;
                    }
                    if($total>0){
                        $result['datas'][] = $array;
                    }
                }
                break;
            default:
                $result = ['labels' => [], 'datas' => []];
        }
        echo json_encode($result);
        die();
    case 'by-date':
    case 'transactions':
    case 'activation':
        $q = (_post('q') ? _post('q') : _get('q'));
        $keep = _post('keep');
        if (!empty($keep)) {
            $keepDays = max(1, (int) $keep);
            if (class_exists('Tenant') && Tenant::isTenantRequest() && Tenant::hasColumn('tbl_transactions', 'tenant_id')) {
                ORM::raw_execute("DELETE FROM tbl_transactions WHERE tenant_id = ? AND date < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL $keepDays DAY))", [Tenant::currentId()]);
            } else {
                ORM::raw_execute("DELETE FROM tbl_transactions WHERE date < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL $keepDays DAY))");
            }
            r2(getUrl('logs/list/'), 's', "Delete logs older than $keep days");
        }
        if ($q != '') {
            $query = ORM::for_table('tbl_transactions')->where_like('invoice', '%' . $q . '%')->order_by_desc('id');
            $query = Tenant::scopeIfTenant($query);
            $d = Paginator::findMany($query, ['q' => $q]);
        } else {
            $query = ORM::for_table('tbl_transactions')->order_by_desc('id');
            $query = Tenant::scopeIfTenant($query);
            $d = Paginator::findMany($query);
        }

        $ui->assign('activation', $d);
        $ui->assign('q', $q);
        $ui->display('admin/reports/activation.tpl');
        break;

    case 'mpesa-logs':
        $sd = reports_mpesa_date(_req('sd', $before_30_days), $before_30_days);
        $ed = reports_mpesa_date(_req('ed', $mdate), $mdate);
        $status = _req('status', 'all');
        $status = in_array($status, ['all', '1', '2', '3', '4'], true) ? $status : 'all';
        $q = alphanumeric(_req('q'), '_ .@:/-');
        $append_url = '&' . http_build_query([
            'sd' => $sd,
            'ed' => $ed,
            'status' => $status,
            'q' => $q,
        ]);

        $query = reports_mpesa_query($sd, $ed, $status, $q);
        $payments = Paginator::findMany($query, [], 50, $append_url);
        if (!$payments) {
            $payments = [];
        }

        $logs = [];
        foreach ($payments as $payment) {
            $logs[] = reports_mpesa_row($payment);
        }

        $summary = reports_mpesa_summary($sd, $ed, $q);

        $ui->assign('_title', 'M-Pesa Logs');
        $ui->assign('logs', $logs);
        $ui->assign('summary', $summary);
        $ui->assign('sd', $sd);
        $ui->assign('ed', $ed);
        $ui->assign('status', $status);
        $ui->assign('q', $q);
        $ui->display('admin/reports/mpesa-logs.tpl');
        break;

    case 'by-period':
        $ui->assign('mdate', $mdate);
        $ui->assign('mtime', $mtime);
        $ui->assign('tdate', $tdate);
        run_hook('view_reports_by_period'); #HOOK
        $ui->display('admin/reports/period.tpl');
        break;

    case 'period-view':
        $fdate = _post('fdate');
        $tdate = _post('tdate');
        $stype = _post('stype');

        $d = ORM::for_table('tbl_transactions');
        $d = Tenant::scopeIfTenant($d);
        if ($stype != '') {
            $d->where('type', $stype);
        }

        $d->where_gte('recharged_on', $fdate);
        $d->where_lte('recharged_on', $tdate);
        $d->order_by_desc('id');
        $x =  $d->find_many();

        $dr = ORM::for_table('tbl_transactions');
        $dr = Tenant::scopeIfTenant($dr);
        if ($stype != '') {
            $dr->where('type', $stype);
        }

        $dr->where_gte('recharged_on', $fdate);
        $dr->where_lte('recharged_on', $tdate);
        $xy = $dr->sum('price');

        $ui->assign('d', $x);
        $ui->assign('dr', $xy);
        $ui->assign('fdate', $fdate);
        $ui->assign('tdate', $tdate);
        $ui->assign('stype', $stype);
        run_hook('view_reports_period'); #HOOK
        $ui->display('admin/reports/period-view.tpl');
        break;

    case 'clients':
    case 'routers':
    case 'daily-report':
    default:
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $methods = reports_distinct_methods();
        $routers = reports_distinct_transactions('routers');
        $plans = reports_distinct_transactions('plan_name');
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
        $tps = ($_GET['tps']) ? $_GET['tps'] : $types;
        $mts = ($_GET['mts']) ? $_GET['mts'] : $methods;
        $rts = ($_GET['rts']) ? $_GET['rts'] : $routers;
        $plns = ($_GET['plns']) ? $_GET['plns'] : $plans;
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');
        $urlquery = str_replace('_route=reports', '', $_SERVER['QUERY_STRING']);


        $query = ORM::for_table('tbl_transactions')
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
            ->order_by_desc('id');
        $query = Tenant::scopeIfTenant($query);
        if (count($tps) > 0) {
            $query->where_in('type', $tps);
        }
        if (count($mts) > 0) {
            $w = [];
            $v = [];
            foreach ($mts as $mt) {
                $w[] ='method';
                $v[] = "$mt - %";
            }
            $query->where_likes($w, $v);
        }
        if (count($rts) > 0) {
            $query->where_in('routers', $rts);
        }
        if (count($plns) > 0) {
            $query->where_in('plan_name', $plns);
        }
        $d = Paginator::findMany($query, [], 100, $urlquery);
        $dr = $query->sum('price');

        $ui->assign('methods', $methods);
        $ui->assign('types', $types);
        $ui->assign('routers', $routers);
        $ui->assign('plans', $plans);
        $ui->assign('filter', $urlquery);

        // time
        $ui->assign('sd', $sd);
        $ui->assign('ed', $ed);
        $ui->assign('ts', $ts);
        $ui->assign('te', $te);

        $ui->assign('mts', $mts);
        $ui->assign('tps', $tps);
        $ui->assign('rts', $rts);
        $ui->assign('plns', $plns);

        $ui->assign('d', $d);
        $ui->assign('dr', $dr);
        $ui->assign('mdate', $mdate);
        run_hook('view_daily_reports'); #HOOK
        $ui->display('admin/reports/list.tpl');
        break;
}

function reports_mpesa_date($value, $fallback)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback;
}

function reports_mpesa_query($sd, $ed, $status = 'all', $q = '')
{
    $query = ORM::for_table('tbl_payment_gateway')
        ->where('gateway', 'mpesastkpush')
        ->where_gte('created_date', $sd . ' 00:00:00')
        ->where_lte('created_date', $ed . ' 23:59:59')
        ->order_by_desc('id');
    $query = Tenant::scopeIfTenant($query);

    if ($status !== 'all') {
        $query->where('status', (int) $status);
    }

    if ($q !== '') {
        $like = '%' . $q . '%';
        $query->where_raw(
            '(gateway_trx_id LIKE ? OR username LIKE ? OR routers LIKE ? OR plan_name LIKE ? OR trx_invoice LIKE ? OR payment_method LIKE ? OR payment_channel LIKE ? OR pg_request LIKE ? OR pg_paid_response LIKE ?)',
            [$like, $like, $like, $like, $like, $like, $like, $like, $like]
        );
    }

    return $query;
}

function reports_distinct_transactions($column)
{
    $column = in_array($column, ['plan_name', 'routers'], true) ? $column : 'plan_name';
    $query = ORM::for_table('tbl_transactions')
        ->select($column)
        ->distinct($column)
        ->where_not_equal($column, '');
    $query = Tenant::scopeIfTenant($query);
    return array_column($query->find_array(), $column);
}

function reports_distinct_methods()
{
    $query = ORM::for_table('tbl_transactions')->select('method')->distinct('method')->where_not_equal('method', '');
    $query = Tenant::scopeIfTenant($query);
    $methods = [];
    foreach ($query->find_array() as $row) {
        $method = trim((string) ($row['method'] ?? ''));
        if ($method === '') {
            continue;
        }
        $methods[] = trim(explode(' - ', $method)[0]);
    }
    return array_values(array_unique($methods));
}

function reports_mpesa_summary($sd, $ed, $q = '')
{
    $base = reports_mpesa_query($sd, $ed, 'all', $q);
    $total = $base->count();

    $paidQuery = reports_mpesa_query($sd, $ed, '2', $q);
    $paidTotal = $paidQuery->sum('price');

    return [
        'total' => (int) $total,
        'paid' => (int) reports_mpesa_query($sd, $ed, '2', $q)->count(),
        'pending' => (int) reports_mpesa_query($sd, $ed, '1', $q)->count(),
        'failed' => (int) reports_mpesa_query($sd, $ed, '3', $q)->count(),
        'canceled' => (int) reports_mpesa_query($sd, $ed, '4', $q)->count(),
        'failed_total' => (int) reports_mpesa_query($sd, $ed, '3', $q)->count() + (int) reports_mpesa_query($sd, $ed, '4', $q)->count(),
        'paid_total' => $paidTotal == '' ? 0 : (float) $paidTotal,
    ];
}

function reports_mpesa_row($payment)
{
    $request = reports_mpesa_decode($payment['pg_request']);
    $response = reports_mpesa_decode($payment['pg_paid_response']);
    $phone = reports_mpesa_value($response, 'PhoneNumber', reports_mpesa_value($request, 'phone', reports_mpesa_value($request, 'default_phone', '')));
    $amount = reports_mpesa_value($response, 'Amount', $payment['price']);
    $receipt = reports_mpesa_value($response, 'MpesaReceiptNumber', '');
    $resultCode = reports_mpesa_value($response, 'ResultCode', '');
    $resultDesc = reports_mpesa_value($response, 'ResultDesc', reports_mpesa_value($request, 'status_note', ''));
    $merchantRequestId = reports_mpesa_value($response, 'MerchantRequestID', reports_mpesa_value($request, 'merchant_request_id', ''));
    $checkoutRequestId = reports_mpesa_value($response, 'CheckoutRequestID', reports_mpesa_value($request, 'checkout_request_id', $payment['gateway_trx_id']));
    $transactionDate = reports_mpesa_transaction_date(reports_mpesa_value($response, 'TransactionDate', ''));

    return [
        'id' => $payment['id'],
        'username' => $payment['username'],
        'plan_name' => $payment['plan_name'],
        'routers' => $payment['routers'],
        'price' => $payment['price'],
        'amount' => $amount,
        'phone' => $phone,
        'receipt' => $receipt,
        'result_code' => $resultCode,
        'result_desc' => $resultDesc,
        'merchant_request_id' => $merchantRequestId,
        'checkout_request_id' => $checkoutRequestId,
        'created_date' => $payment['created_date'],
        'paid_date' => $payment['paid_date'],
        'transaction_date' => $transactionDate,
        'invoice' => $payment['trx_invoice'],
        'payment_link' => $payment['pg_url_payment'],
        'status' => (int) $payment['status'],
        'status_label' => reports_mpesa_status_label((int) $payment['status']),
        'status_class' => reports_mpesa_status_class((int) $payment['status']),
    ];
}

function reports_mpesa_decode($value)
{
    if ($value == '') {
        return [];
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function reports_mpesa_value($array, $key, $fallback = '')
{
    return isset($array[$key]) && $array[$key] !== '' ? $array[$key] : $fallback;
}

function reports_mpesa_transaction_date($value)
{
    if (preg_match('/^\d{14}$/', $value)) {
        return substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2) . ' ' . substr($value, 8, 2) . ':' . substr($value, 10, 2) . ':' . substr($value, 12, 2);
    }

    return '';
}

function reports_mpesa_status_label($status)
{
    switch ((int) $status) {
        case 1:
            return 'Pending';
        case 2:
            return 'Paid';
        case 3:
            return 'Failed';
        case 4:
            return 'Canceled';
        default:
            return 'Unknown';
    }
}

function reports_mpesa_status_class($status)
{
    switch ((int) $status) {
        case 1:
            return 'warning';
        case 2:
            return 'success';
        case 3:
            return 'danger';
        case 4:
            return 'default';
        default:
            return 'default';
    }
}
