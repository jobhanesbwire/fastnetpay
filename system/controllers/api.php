<?php

/**
 * FASTNETPAY public, customer-safe API endpoints for MikroTik hotspot portals.
 *
 * These routes are intentionally separate from the authenticated admin API:
 * /?_route=api/hotspot/packages
 * /?_route=api/hotspot/pay
 * /?_route=api/hotspot/payment-status
 * /?_route=api/hotspot/voucher-login
 * /?_route=api/hotspot/portal-file
 */

$scope = $routes['1'] ?? '';
$action = $routes['2'] ?? '';

if ($scope !== 'hotspot') {
    fnp_hotspot_json(['ok' => false, 'message' => 'API scope not found.'], 404);
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Private-Network: true');
header('Access-Control-Max-Age: 600');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

RouterProvisioning::installSchema();

if ($action === 'portal-file') {
    $router = fnp_hotspot_authorize();
    $file = _req('file', 'login.html');
    $content = RouterProvisioning::hotspotPortalFile($file, $router, _req('token'), _req('base'), _req('gateway', '192.168.90.1'), _req('portal', 'portal.fastnetpay.test'));
    if ($content === null) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
    $type = 'text/html; charset=utf-8';
    if (substr($file, -4) === '.css') {
        $type = 'text/css; charset=utf-8';
    } elseif (substr($file, -3) === '.js') {
        $type = 'application/javascript; charset=utf-8';
    } elseif (substr($file, -5) === '.json') {
        $type = 'application/captive+json; charset=utf-8';
    }
    header('Content-Type: ' . $type);
    header('Cache-Control: no-store');
    echo $content;
    exit;
}

switch ($action) {
    case 'packages':
        $router = fnp_hotspot_authorize();
        $plans = fnp_hotspot_packages($router);
        fnp_hotspot_json(['ok' => true, 'packages' => $plans]);
        break;

    case 'pay':
        $router = fnp_hotspot_authorize();
        fnp_hotspot_start_mpesa($router);
        break;

    case 'payment-status':
        fnp_hotspot_payment_status();
        break;

    case 'voucher-login':
        $router = fnp_hotspot_authorize();
        fnp_hotspot_voucher_login($router);
        break;

    default:
        fnp_hotspot_json(['ok' => false, 'message' => 'API endpoint not found.'], 404);
}

function fnp_hotspot_authorize()
{
    $routerId = (int) _req('router');
    $token = _req('token');
    if (!RouterProvisioning::validatePortalToken($routerId, $token)) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Invalid hotspot portal token. Re-run router provisioning to refresh portal files.'], 403);
    }
    $router = ORM::for_table('tbl_routers')->find_one($routerId);
    if (!$router) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Router not found.'], 404);
    }
    return $router;
}

function fnp_hotspot_packages($router)
{
    $query = ORM::for_table('tbl_plans')
        ->select('tbl_plans.id', 'id')
        ->select('tbl_plans.name_plan', 'name_plan')
        ->select('tbl_plans.price', 'price')
        ->select('tbl_plans.typebp', 'typebp')
        ->select('tbl_plans.limit_type', 'limit_type')
        ->select('tbl_plans.time_limit', 'time_limit')
        ->select('tbl_plans.time_unit', 'time_unit')
        ->select('tbl_plans.data_limit', 'data_limit')
        ->select('tbl_plans.data_unit', 'data_unit')
        ->select('tbl_plans.validity', 'validity')
        ->select('tbl_plans.validity_unit', 'validity_unit')
        ->select('tbl_bandwidth.name_bw', 'name_bw')
        ->select('tbl_bandwidth.rate_down', 'rate_down')
        ->select('tbl_bandwidth.rate_down_unit', 'rate_down_unit')
        ->select('tbl_bandwidth.rate_up', 'rate_up')
        ->select('tbl_bandwidth.rate_up_unit', 'rate_up_unit')
        ->left_outer_join('tbl_bandwidth', ['tbl_plans.id_bw', '=', 'tbl_bandwidth.id'])
        ->where('tbl_plans.enabled', '1')
        ->where('tbl_plans.type', 'Hotspot')
        ->order_by_asc('tbl_plans.price');

    $routerName = (string) $router['name'];
    $query->where_raw('(routers = ? OR routers = ? OR routers IS NULL)', [$routerName, '']);

    $plans = [];
    foreach ($query->find_array() as $plan) {
        $plans[] = [
            'id' => (int) $plan['id'],
            'name' => $plan['name_plan'],
            'price' => (float) $plan['price'],
            'validity' => trim($plan['validity'] . ' ' . $plan['validity_unit']),
            'bandwidth' => fnp_hotspot_bandwidth_label($plan),
            'limit' => fnp_hotspot_limit_label($plan),
        ];
    }
    return $plans;
}

function fnp_hotspot_bandwidth_label($plan)
{
    $down = trim((string) ($plan['rate_down'] ?? ''));
    $downUnit = trim((string) ($plan['rate_down_unit'] ?? ''));
    $up = trim((string) ($plan['rate_up'] ?? ''));
    $upUnit = trim((string) ($plan['rate_up_unit'] ?? ''));
    if ($down !== '' && $up !== '') {
        return $down . $downUnit . ' down / ' . $up . $upUnit . ' up';
    }
    if (!empty($plan['name_bw'])) {
        return (string) $plan['name_bw'];
    }
    return 'Best available speed';
}

function fnp_hotspot_limit_label($plan)
{
    $type = (string) ($plan['typebp'] ?? '');
    if (strcasecmp($type, 'Unlimited') === 0 || $type === '') {
        return 'Unlimited data';
    }
    $limitType = (string) ($plan['limit_type'] ?? '');
    $parts = [];
    if (($limitType === 'Time_Limit' || $limitType === 'Both_Limit') && (int) ($plan['time_limit'] ?? 0) > 0) {
        $parts[] = (int) $plan['time_limit'] . ' ' . $plan['time_unit'];
    }
    if (($limitType === 'Data_Limit' || $limitType === 'Both_Limit') && (int) ($plan['data_limit'] ?? 0) > 0) {
        $parts[] = (int) $plan['data_limit'] . ' ' . $plan['data_unit'];
    }
    return $parts ? implode(' + ', $parts) : 'Limited package';
}

function fnp_hotspot_start_mpesa($router)
{
    global $PAYMENTGATEWAY_PATH;

    $phone = trim(_post('phone'));
    $planId = (int) _post('plan_id');
    $mac = fnp_hotspot_clean(_post('mac'), 32);
    $ip = fnp_hotspot_clean(_post('ip'), 45);

    if (!RouterProvisioning::throttleHotspotAttempt('pay:' . $router['id'] . ':' . $ip . ':' . $mac, 6, 300)) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Too many payment attempts. Please wait a few minutes and try again.'], 429);
    }

    require_once $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'mpesastkpush.php';

    if (mpesastkpush_config('mpesastkpush_enabled', 'no') !== 'yes') {
        fnp_hotspot_json(['ok' => false, 'message' => 'M-Pesa STK Push is not enabled yet.'], 503);
    }

    $formattedPhone = mpesastkpush_format_phone($phone);
    if ($formattedPhone === false) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Enter a valid Safaricom number such as 07XXXXXXXX.'], 422);
    }

    $plan = ORM::for_table('tbl_plans')
        ->where('id', $planId)
        ->where('enabled', '1')
        ->where('type', 'Hotspot')
        ->find_one();
    if (!$plan) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Selected package is not available.'], 404);
    }

    $customer = fnp_hotspot_customer($router, $formattedPhone, $mac, $ip);
    $paymentToken = bin2hex(random_bytes(24));
    $payment = ORM::for_table('tbl_payment_gateway')->create();
    $payment->username = $customer['username'];
    $payment->user_id = (int) $customer['id'];
    $payment->gateway = 'mpesastkpush';
    $payment->gateway_trx_id = '';
    $payment->plan_id = (int) $plan['id'];
    $payment->plan_name = $plan['name_plan'];
    $payment->routers_id = (int) $router['id'];
    $payment->routers = $router['name'];
    $payment->price = $plan['price'];
    $payment->payment_method = 'M-Pesa';
    $payment->payment_channel = 'STK Push';
    $payment->created_date = date('Y-m-d H:i:s');
    $payment->expired_date = date('Y-m-d H:i:s', time() + mpesastkpush_timeout_seconds() + 600);
    $payment->status = 1;
    $payment->pg_request = json_encode([
        'payment_token_hash' => hash('sha256', $paymentToken),
        'phone' => $formattedPhone,
        'mac' => $mac,
        'ip' => $ip,
        'router_id' => (int) $router['id'],
        'status_note' => 'Hotspot portal STK Push requested',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $payment->save();

    $response = mpesastkpush_send_stk($payment, $formattedPhone, mpesastkpush_amount($payment['price']));
    $state = json_decode($payment['pg_request'], true) ?: [];
    if (!$response['ok']) {
        $payment->status = 3;
        $state['status_note'] = $response['message'];
        $payment->pg_request = json_encode($state);
        $payment->save();
        fnp_hotspot_json(['ok' => false, 'message' => $response['message']], 502);
    }

    $data = $response['data'];
    $state['merchant_request_id'] = isset($data['MerchantRequestID']) ? (string) $data['MerchantRequestID'] : '';
    $state['checkout_request_id'] = isset($data['CheckoutRequestID']) ? (string) $data['CheckoutRequestID'] : '';
    $state['status_note'] = 'Check your phone and enter your M-Pesa PIN.';
    $state['updated_at'] = date('Y-m-d H:i:s');
    $payment->gateway_trx_id = $state['checkout_request_id'];
    $payment->pg_request = json_encode($state);
    $payment->save();

    fnp_hotspot_json([
        'ok' => true,
        'status' => 'pending',
        'message' => 'Check your phone and enter your M-Pesa PIN.',
        'payment_id' => (int) $payment->id(),
        'payment_token' => $paymentToken,
    ]);
}

function fnp_hotspot_payment_status()
{
    $paymentId = (int) _post('payment_id');
    $paymentToken = trim(_post('payment_token'));
    $payment = ORM::for_table('tbl_payment_gateway')->find_one($paymentId);
    if (!$payment) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Payment not found.'], 404);
    }
    $state = json_decode($payment['pg_request'], true) ?: [];
    if (empty($state['payment_token_hash']) || !hash_equals($state['payment_token_hash'], hash('sha256', $paymentToken))) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Invalid payment status token.'], 403);
    }

    $customer = ORM::for_table('tbl_customers')->find_one((int) $payment['user_id']);
    if ((int) $payment['status'] === 2) {
        fnp_hotspot_json([
            'ok' => true,
            'status' => 'paid',
            'message' => 'Payment received. Your internet package is active.',
            'username' => $customer ? $customer['username'] : '',
            'password' => $customer ? $customer['password'] : '',
        ]);
    }

    if (in_array((int) $payment['status'], [3, 4], true)) {
        fnp_hotspot_json(['ok' => true, 'status' => 'failed', 'message' => 'Payment failed, cancelled, or timed out.']);
    }

    fnp_hotspot_json(['ok' => true, 'status' => 'pending', 'message' => 'Payment pending. Check your phone and enter your M-Pesa PIN.']);
}

function fnp_hotspot_voucher_login($router)
{
    $voucher = alphanumeric(_post('voucher'), '-_.');
    if ($voucher === '') {
        fnp_hotspot_json(['ok' => false, 'message' => 'Enter a voucher code.'], 422);
    }
    if (!RouterProvisioning::throttleHotspotAttempt('voucher:' . $router['id'] . ':' . $voucher, 8, 300)) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Too many voucher attempts. Please wait and try again.'], 429);
    }

    $row = ORM::for_table('tbl_voucher')->whereRaw('BINARY code = ?', [$voucher])->where('status', 0)->find_one();
    if (!$row) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Voucher is invalid or already used.'], 404);
    }

    if (!Package::rechargeUser(0, $row['routers'], $row['id_plan'], 'Voucher', $voucher)) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Voucher activation failed.'], 500);
    }

    $row->status = '1';
    $row->used_date = date('Y-m-d H:i:s');
    $row->user = $voucher;
    $row->save();

    fnp_hotspot_json([
        'ok' => true,
        'message' => 'Voucher activated. Logging you in...',
        'username' => $voucher,
        'password' => $voucher,
    ]);
}

function fnp_hotspot_customer($router, $phone, $mac, $ip)
{
    $seed = $router['id'] . '|' . $mac . '|' . $ip . '|' . $phone;
    $username = 'hs_' . substr(sha1($seed), 0, 16);
    $customer = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
    if (!$customer) {
        $customer = ORM::for_table('tbl_customers')->create();
        $customer->username = $username;
        $customer->password = substr(sha1($seed . microtime(true)), 0, 10);
        $customer->photo = '/user.default.jpg';
        $customer->pppoe_username = '';
        $customer->pppoe_password = '';
        $customer->pppoe_ip = '';
        $customer->fullname = 'Hotspot Guest ' . substr($phone, -4);
        $customer->address = 'MikroTik hotspot ' . $router['name'];
        $customer->email = $username . '@hotspot.local';
        $customer->account_type = 'Personal';
        $customer->balance = 0;
        $customer->service_type = 'Hotspot';
        $customer->auto_renewal = 0;
        $customer->status = 'Active';
        $customer->created_by = 0;
    }
    $customer->phonenumber = $phone;
    $customer->last_login = date('Y-m-d H:i:s');
    $customer->save();
    return $customer;
}

function fnp_hotspot_clean($value, $max = 64)
{
    return substr(preg_replace('/[^A-Za-z0-9_.: -]+/', '', trim((string) $value)), 0, $max);
}

function fnp_hotspot_json($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($data);
    exit;
}
