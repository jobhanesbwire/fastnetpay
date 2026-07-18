<?php

/**
 * FASTNETPAY public, customer-safe API endpoints for MikroTik hotspot portals.
 *
 * These routes are intentionally separate from the authenticated admin API:
 * /?_route=api/hotspot/packages
 * /?_route=api/hotspot/pay
 * /?_route=api/hotspot/payment-status
 * /?_route=api/hotspot/voucher-login
 * /?_route=api/hotspot/reconnect
 * /?_route=api/hotspot/portal-file
 * /?_route=api/jovipay/callback
 * /?_route=api/saas/mpesa/callback
 */

$scope = $routes['1'] ?? '';
$action = $routes['2'] ?? '';

if ($scope === 'jovipay') {
    if ($action === 'callback') {
        JoviPay::handleCallback();
    }
    fnp_hotspot_json(['ok' => false, 'message' => 'Jovi-Pay API endpoint not found.'], 404);
}

if ($scope === 'saas') {
    if ($action === 'mpesa' && ($routes['3'] ?? '') === 'callback') {
        SaasBilling::handlePaymentCallback();
    }
    fnp_hotspot_json(['ok' => false, 'message' => 'SaaS payment API endpoint not found.'], 404);
}

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
JoviPay::installSchema();

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

    case 'session':
        $router = fnp_hotspot_authorize();
        fnp_hotspot_session_status($router);
        break;

    case 'voucher-login':
        $router = fnp_hotspot_authorize();
        fnp_hotspot_voucher_login($router);
        break;

    case 'reconnect':
        $router = fnp_hotspot_authorize();
        fnp_hotspot_reconnect($router);
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

function fnp_hotspot_tenant_id($router)
{
    return class_exists('Tenant') ? (int) ($router['tenant_id'] ?: Tenant::currentId()) : 0;
}

function fnp_hotspot_packages($router)
{
    $tenantId = fnp_hotspot_tenant_id($router);
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
    if (class_exists('Tenant') && $tenantId > 0 && Tenant::hasColumn('tbl_plans', 'tenant_id')) {
        $query->where('tbl_plans.tenant_id', $tenantId);
    }

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

    $tenantId = fnp_hotspot_tenant_id($router);
    $phone = trim(_post('phone'));
    $planId = (int) _post('plan_id');
    $mac = fnp_hotspot_clean(_post('mac'), 32);
    $ip = fnp_hotspot_clean(_post('ip'), 45);

    if (!RouterProvisioning::throttleHotspotAttempt('pay:' . $router['id'] . ':' . $ip . ':' . $mac, 6, 300)) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Too many payment attempts. Please wait a few minutes and try again.'], 429);
    }

    if (class_exists('JoviPay') && JoviPay::isEnabled($tenantId)) {
        try {
            fnp_hotspot_json(JoviPay::startHotspotPayment($router, $planId, $phone, $mac, $ip));
        } catch (Throwable $e) {
            fnp_hotspot_json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
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
    if ($plan && class_exists('Tenant') && Tenant::hasColumn('tbl_plans', 'tenant_id') && (int) ($plan['tenant_id'] ?? 0) !== $tenantId) {
        $plan = null;
    }
    if (!$plan) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Selected package is not available.'], 404);
    }

    $customer = fnp_hotspot_customer($router, $formattedPhone, $mac, $ip);
    $paymentToken = bin2hex(random_bytes(24));
    $payment = ORM::for_table('tbl_payment_gateway')->create();
    if (class_exists('Tenant')) {
        Tenant::stamp($payment, $tenantId, 'tbl_payment_gateway');
    }
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
    global $PAYMENTGATEWAY_PATH;

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
        if ($payment['gateway'] === 'mpesastkpush') {
            require_once $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'mpesastkpush.php';
            mpesastkpush_connect_hotspot_session($payment, $customer, _post('ip'), _post('mac'));
        }
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

function fnp_hotspot_reconnect($router)
{
    global $PAYMENTGATEWAY_PATH;

    $code = strtoupper(fnp_hotspot_clean(_post('transaction_code') ?: _post('mpesa_code') ?: _post('receipt'), 80));
    $phone = trim(_post('phone'));
    $mac = fnp_hotspot_clean(_post('mac'), 64);
    $ip = fnp_hotspot_clean(_post('ip'), 64);
    if (!RouterProvisioning::throttleHotspotAttempt('reconnect:' . $router['id'] . ':' . $ip . ':' . $mac . ':' . $code, 8, 300)) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Too many reconnect attempts. Please wait a few minutes and try again.'], 429);
    }

    $joviError = null;
    try {
        if (class_exists('JoviPay')) {
            fnp_hotspot_json(JoviPay::reconnect($router, $code, $phone, $mac, $ip));
        }
    } catch (Throwable $e) {
        $joviError = $e->getMessage();
    }

    require_once $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'mpesastkpush.php';
    try {
        fnp_hotspot_json(mpesastkpush_reconnect_hotspot_payment($router, $code, $phone, $mac, $ip));
    } catch (Throwable $e) {
        fnp_hotspot_json(['ok' => false, 'message' => $joviError && stripos($joviError, 'not found') === false ? $joviError : $e->getMessage()], 422);
    }
}

function fnp_hotspot_session_status($router)
{
    $mac = fnp_hotspot_clean(_post('mac'), 64);
    $ip = fnp_hotspot_clean(_post('ip'), 64);
    $customer = fnp_hotspot_find_customer_by_device($router, $mac, '', $ip);
    if (!$customer) {
        fnp_hotspot_json(['ok' => true, 'status' => 'none', 'message' => 'No active package found for this device.']);
    }
    fnp_hotspot_normalize_customer_identity($customer, $router, fnp_hotspot_username($router, $mac, $ip), $mac, $customer['phonenumber'] ?: '');
    $customer->save();

    $recharge = fnp_hotspot_latest_recharge($router, $customer);
    if (!$recharge) {
        fnp_hotspot_json(['ok' => true, 'status' => 'none', 'message' => 'No active package found for this device.']);
    }

    if (fnp_hotspot_recharge_expired($recharge)) {
        fnp_hotspot_disconnect_expired($recharge);
        fnp_hotspot_json(['ok' => true, 'status' => 'expired', 'message' => 'Your previous package has expired. Select a package to continue.']);
    }

    fnp_hotspot_connect_customer($router, $customer, $ip, $mac);
    fnp_hotspot_json([
        'ok' => true,
        'status' => 'active',
        'message' => 'Active package found. Connecting you now...',
        'username' => $customer['username'],
        'password' => $customer['password'],
    ]);
}

function fnp_hotspot_voucher_login($router)
{
    $tenantId = fnp_hotspot_tenant_id($router);
    $voucher = alphanumeric(_post('voucher'), '-_.');
    if ($voucher === '') {
        fnp_hotspot_json(['ok' => false, 'message' => 'Enter a voucher code.'], 422);
    }
    if (!RouterProvisioning::throttleHotspotAttempt('voucher:' . $router['id'] . ':' . $voucher, 8, 300)) {
        fnp_hotspot_json(['ok' => false, 'message' => 'Too many voucher attempts. Please wait and try again.'], 429);
    }

    $query = ORM::for_table('tbl_voucher')->whereRaw('BINARY code = ?', [$voucher])->where('status', 0);
    if (class_exists('Tenant') && Tenant::hasColumn('tbl_voucher', 'tenant_id')) {
        $query->where('tenant_id', $tenantId);
    }
    $row = $query->find_one();
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
    $tenantId = fnp_hotspot_tenant_id($router);
    $macKey = fnp_hotspot_mac_key($mac);
    $seed = $router['id'] . '|' . ($macKey ?: $mac) . '|' . $phone;
    $username = fnp_hotspot_username($router, $mac, $ip);
    $customer = fnp_hotspot_find_customer_by_device($router, $mac, $phone, $ip);
    if (!$customer) {
        $customer = ORM::for_table('tbl_customers')->create();
        if (class_exists('Tenant')) {
            Tenant::stamp($customer, $tenantId, 'tbl_customers');
        }
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
    fnp_hotspot_normalize_customer_identity($customer, $router, $username, $mac, $phone);
    $customer->phonenumber = $phone;
    if ($macKey !== '') {
        $customer->address = 'MikroTik hotspot ' . $router['name'] . ' device ' . $mac;
    }
    $customer->last_login = date('Y-m-d H:i:s');
    $customer->save();
    return $customer;
}

function fnp_hotspot_mac_key($mac)
{
    $mac = strtoupper(preg_replace('/[^A-F0-9]/i', '', (string) $mac));
    return strlen($mac) >= 10 ? substr($mac, -12) : '';
}

function fnp_hotspot_username($router, $mac, $ip = '')
{
    $macKey = fnp_hotspot_mac_key($mac);
    if ($macKey !== '') {
        return $macKey;
    }
    return 'HS' . strtoupper(substr(sha1((int) $router['id'] . '|' . $ip), 0, 14));
}

function fnp_hotspot_mac_display($mac)
{
    $macKey = fnp_hotspot_mac_key($mac);
    if ($macKey === '') {
        return fnp_hotspot_clean($mac, 64);
    }
    return implode(':', str_split($macKey, 2));
}

function fnp_hotspot_find_customer_by_device($router, $mac, $phone = '', $ip = '')
{
    $tenantId = fnp_hotspot_tenant_id($router);
    $username = fnp_hotspot_username($router, $mac, $ip);
    $macKey = fnp_hotspot_mac_key($mac);
    $formattedMac = fnp_hotspot_mac_display($mac);
    $oldUsername = $macKey !== '' ? 'hs_r' . (int) $router['id'] . '_' . strtolower(substr($macKey, -12)) : '';

    $candidates = array_values(array_unique(array_filter([$username, $oldUsername])));
    if ($candidates) {
        $query = ORM::for_table('tbl_customers')->where_in('username', $candidates);
        if (class_exists('Tenant') && Tenant::hasColumn('tbl_customers', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
        $customer = $query->order_by_desc('id')->find_one();
        if ($customer) {
            return $customer;
        }
    }

    if ($macKey !== '') {
        $query = ORM::for_table('tbl_customers')
            ->where_raw('(fullname LIKE ? OR address LIKE ?)', [$formattedMac . '-%', '%device ' . $formattedMac . '%'])
            ->order_by_desc('id');
        if (class_exists('Tenant') && Tenant::hasColumn('tbl_customers', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
        $customer = $query->find_one();
        if ($customer) {
            return $customer;
        }

        $payment = ORM::for_table('tbl_payment_gateway')
            ->where_raw('(pg_request LIKE ? OR pg_request LIKE ?)', ['%"mac":"' . $formattedMac . '"%', '%"mac":"' . $macKey . '"%'])
            ->where_gt('user_id', 0)
            ->order_by_desc('id')
            ->find_one();
        if ($payment) {
            $customer = ORM::for_table('tbl_customers')->find_one((int) $payment['user_id']);
            if ($customer) {
                return $customer;
            }
        }
    }

    return null;
}

function fnp_hotspot_normalize_customer_identity($customer, $router, $username, $mac, $phone)
{
    $tenantId = fnp_hotspot_tenant_id($router);
    $formattedMac = fnp_hotspot_mac_display($mac);
    $fullname = ($formattedMac !== '' ? $formattedMac : $username) . '-' . ($phone ?: 'unknown');

    if ($username !== '' && $customer['username'] !== $username) {
        $query = ORM::for_table('tbl_customers')->where('username', $username);
        if (class_exists('Tenant') && Tenant::hasColumn('tbl_customers', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
        $existing = $query->find_one();
        if (!$existing || (int) $existing['id'] === (int) $customer['id']) {
            $oldUsername = (string) $customer['username'];
            $customer->username = $username;
            fnp_hotspot_update_username_references($customer, $oldUsername, $username);
        }
    }

    $customer->fullname = substr($fullname, 0, 190);
    $customer->email = strtolower(preg_replace('/[^A-Za-z0-9_.-]/', '', $username)) . '@hotspot.local';
}

function fnp_hotspot_update_username_references($customer, $oldUsername, $newUsername)
{
    if ($oldUsername === '' || $oldUsername === $newUsername) {
        return;
    }
    foreach ([
        'tbl_user_recharges' => 'customer_id',
        'tbl_payment_gateway' => 'user_id',
        'tbl_transactions' => 'user_id',
        'jovipay_transactions' => 'customer_id',
    ] as $table => $idColumn) {
        try {
            if (class_exists('Tenant') && !Tenant::hasColumn($table, 'username')) {
                continue;
            }
            $rows = ORM::for_table($table)->where($idColumn, (int) $customer['id'])->find_many();
            foreach ($rows as $row) {
                $row->username = $newUsername;
                $row->save();
            }
        } catch (Throwable $ignored) {
        }
    }
}

function fnp_hotspot_latest_recharge($router, $customer)
{
    return ORM::for_table('tbl_user_recharges')
        ->where('customer_id', (int) $customer['id'])
        ->where('routers', $router['name'])
        ->where('type', 'Hotspot')
        ->where('status', 'on')
        ->order_by_desc('id')
        ->find_one();
}

function fnp_hotspot_recharge_expired($recharge)
{
    return strtotime($recharge['expiration'] . ' ' . $recharge['time']) <= time();
}

function fnp_hotspot_disconnect_expired($recharge)
{
    try {
        ExpiryWorker::disconnectRecharge(0, $recharge);
    } catch (Throwable $e) {
        _log('FASTNETPAY hotspot portal expiry disconnect failed for ' . $recharge['username'] . ': ' . $e->getMessage(), 'Cron', 0);
    }
}

function fnp_hotspot_connect_customer($router, $customer, $ip, $mac)
{
    global $DEVICE_PATH;
    $ip = fnp_hotspot_clean($ip, 64);
    $mac = fnp_hotspot_clean($mac, 64);
    if ($ip === '' || $mac === '') {
        return false;
    }
    try {
        $devicePath = $DEVICE_PATH . DIRECTORY_SEPARATOR . 'MikrotikHotspot.php';
        if (!file_exists($devicePath)) {
            return false;
        }
        require_once $devicePath;
        (new MikrotikHotspot())->connect_customer($customer, $ip, $mac, $router['name']);
        return true;
    } catch (Throwable $e) {
        _log('FASTNETPAY hotspot auto-session login pending for ' . $customer['username'] . ': ' . $e->getMessage(), 'Payment Gateway', 0);
        return false;
    }
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
