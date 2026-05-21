<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * FASTNETPAY M-Pesa STK Push payment gateway.
 *
 * This plugin follows the PHPNuxBill function-based payment gateway contract:
 * - mpesastkpush_validate_config()
 * - mpesastkpush_show_config()
 * - mpesastkpush_save_config()
 * - mpesastkpush_create_transaction($trx, $user)
 * - mpesastkpush_payment_notification()
 * - mpesastkpush_get_status($trx, $user)
 */

function mpesastkpush_validate_config()
{
    if (mpesastkpush_config('mpesastkpush_enabled', 'no') !== 'yes') {
        r2(U . 'order/package', 'w', Lang::T('M-Pesa STK Push is not enabled. Please contact support.'));
    }

    $required = [
        'mpesastkpush_shortcode' => 'M-Pesa shortcode',
        'mpesastkpush_consumer_key' => 'M-Pesa consumer key',
        'mpesastkpush_consumer_secret' => 'M-Pesa consumer secret',
        'mpesastkpush_passkey' => 'M-Pesa passkey',
    ];

    foreach ($required as $key => $label) {
        if (trim(mpesastkpush_config($key, '')) === '') {
            sendTelegram('M-Pesa STK Push payment gateway is missing ' . $label);
            r2(U . 'order/package', 'w', Lang::T('M-Pesa STK Push is not fully configured. Please contact support.'));
        }
    }
}

function mpesastkpush_show_config()
{
    global $ui, $config;

    $settings = mpesastkpush_settings();
    $settings['consumer_secret_masked'] = mpesastkpush_mask($settings['consumer_secret']);
    $settings['passkey_masked'] = mpesastkpush_mask($settings['passkey']);
    $settings['callback_url_value'] = $settings['callback_url'] ?: mpesastkpush_default_callback_url();
    $settings['walled_garden_lines'] = preg_split('/\r\n|\r|\n/', $settings['walled_garden_domains']);

    $ui->assign('_title', 'M-Pesa STK Push - Payment Gateway - ' . $config['CompanyName']);
    $ui->assign('mpesa', $settings);
    $ui->assign('csrf_token', Csrf::generateAndStoreToken());
    $ui->display('mpesastkpush.tpl');
}

function mpesastkpush_save_config()
{
    global $admin, $_L;

    if (!Csrf::check(_post('csrf_token'))) {
        r2(U . 'paymentgateway/mpesastkpush', 'e', Lang::T('Invalid CSRF token. Please try again.'));
    }

    $environment = strtolower(_post('environment', 'sandbox'));
    if (!in_array($environment, ['sandbox', 'live'], true)) {
        $environment = 'sandbox';
    }

    $shortcodeType = strtolower(_post('shortcode_type', 'paybill'));
    if (!in_array($shortcodeType, ['paybill', 'till'], true)) {
        $shortcodeType = 'paybill';
    }

    $shortcode = Text::numeric(_post('shortcode'));
    if ($shortcode === '') {
        r2(U . 'paymentgateway/mpesastkpush', 'e', Lang::T('M-Pesa shortcode is required.'));
    }

    $consumerKey = trim(_post('consumer_key'));
    if ($consumerKey === '') {
        r2(U . 'paymentgateway/mpesastkpush', 'e', Lang::T('M-Pesa consumer key is required.'));
    }

    $consumerSecret = trim(_post('consumer_secret'));
    if ($consumerSecret === '' && mpesastkpush_config('mpesastkpush_consumer_secret', '') === '') {
        r2(U . 'paymentgateway/mpesastkpush', 'e', Lang::T('M-Pesa consumer secret is required.'));
    }

    $passkey = trim(_post('passkey'));
    if ($passkey === '' && mpesastkpush_config('mpesastkpush_passkey', '') === '') {
        r2(U . 'paymentgateway/mpesastkpush', 'e', Lang::T('M-Pesa passkey is required.'));
    }

    $callbackUrl = trim(_post('callback_url'));
    if ($callbackUrl !== '' && !filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
        r2(U . 'paymentgateway/mpesastkpush', 'e', Lang::T('Callback URL must be a valid URL.'));
    }

    $testPhone = trim(_post('test_phone'));
    if ($testPhone !== '') {
        $formattedTestPhone = mpesastkpush_format_phone($testPhone);
        if ($formattedTestPhone === false) {
            r2(U . 'paymentgateway/mpesastkpush', 'e', Lang::T('Test phone number must be a valid Kenyan Safaricom number.'));
        }
        $testPhone = $formattedTestPhone;
    }

    $timeoutSeconds = (int) Text::numeric(_post('timeout_seconds', '300'));
    if ($timeoutSeconds < 30 || $timeoutSeconds > 900) {
        $timeoutSeconds = 300;
    }

    $accountPrefix = mpesastkpush_clean_reference(_post('account_prefix', 'FASTNETPAY'), 12);
    if ($accountPrefix === '') {
        $accountPrefix = 'FASTNETPAY';
    }

    $transactionDesc = mpesastkpush_clean_text(_post('transaction_desc', 'Internet Package Payment'), 80);
    if ($transactionDesc === '') {
        $transactionDesc = 'Internet Package Payment';
    }

    $logoUrl = trim(_post('portal_logo_url'));
    if ($logoUrl !== '' && !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
        r2(U . 'paymentgateway/mpesastkpush', 'e', Lang::T('Logo URL must be a valid URL.'));
    }

    $primaryColor = mpesastkpush_clean_hex_color(_post('portal_primary_color', '#41a146'), '#41a146');
    $secondaryColor = mpesastkpush_clean_hex_color(_post('portal_secondary_color', '#f9c02b'), '#f9c02b');

    mpesastkpush_save_setting('mpesastkpush_enabled', _post('enabled') === 'yes' ? 'yes' : 'no');
    mpesastkpush_save_setting('mpesastkpush_environment', $environment);
    mpesastkpush_save_setting('mpesastkpush_shortcode_type', $shortcodeType);
    mpesastkpush_save_setting('mpesastkpush_shortcode', $shortcode);
    mpesastkpush_save_setting('mpesastkpush_consumer_key', $consumerKey);
    mpesastkpush_save_sensitive_setting('mpesastkpush_consumer_secret', $consumerSecret);
    mpesastkpush_save_sensitive_setting('mpesastkpush_passkey', $passkey);
    mpesastkpush_save_setting('mpesastkpush_callback_url', $callbackUrl);
    mpesastkpush_save_setting('mpesastkpush_account_prefix', $accountPrefix);
    mpesastkpush_save_setting('mpesastkpush_transaction_desc', $transactionDesc);
    mpesastkpush_save_setting('mpesastkpush_timeout_seconds', (string) $timeoutSeconds);
    mpesastkpush_save_setting('mpesastkpush_test_phone', $testPhone);
    mpesastkpush_save_setting('mpesastkpush_walled_garden_domains', mpesastkpush_clean_walled_garden(_post('walled_garden_domains', mpesastkpush_default_walled_garden_domains())));

    mpesastkpush_save_setting('mpesastkpush_portal_title', mpesastkpush_clean_text(_post('portal_title', 'FASTNETPAY WiFi'), 80));
    mpesastkpush_save_setting('mpesastkpush_portal_welcome', mpesastkpush_clean_text(_post('portal_welcome', 'Pay securely with M-Pesa STK Push.'), 160));
    mpesastkpush_save_setting('mpesastkpush_portal_logo_url', $logoUrl);
    mpesastkpush_save_setting('mpesastkpush_support_phone', mpesastkpush_clean_text(_post('portal_support_phone', ''), 32));
    mpesastkpush_save_setting('mpesastkpush_portal_primary_color', $primaryColor);
    mpesastkpush_save_setting('mpesastkpush_portal_secondary_color', $secondaryColor);
    mpesastkpush_save_setting('mpesastkpush_portal_footer_text', mpesastkpush_clean_text(_post('portal_footer_text', 'Powered by FASTNETPAY'), 120));

    _log('[' . $admin['username'] . ']: M-Pesa STK Push ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);
    r2(U . 'paymentgateway/mpesastkpush', 's', $_L['Settings_Saved_Successfully']);
}

function mpesastkpush_create_transaction($trx, $user)
{
    mpesastkpush_validate_config();

    $payment = ORM::for_table('tbl_payment_gateway')->find_one($trx['id']);
    if (!$payment) {
        r2(U . 'order/package', 'e', Lang::T('Payment transaction was not found.'));
    }

    $token = bin2hex(random_bytes(32));
    $timeoutSeconds = mpesastkpush_timeout_seconds();
    $defaultPhone = '';
    if (!empty($user['phonenumber'])) {
        $formatted = mpesastkpush_format_phone($user['phonenumber']);
        $defaultPhone = $formatted ?: '';
    }

    $state = [
        'state_hash' => hash('sha256', $token),
        'amount' => mpesastkpush_amount($payment['price']),
        'default_phone' => $defaultPhone,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'status_note' => 'Awaiting customer phone number',
        'gateway_version' => '1.0.0',
    ];

    $paymentUrl = mpesastkpush_build_url([
        'action' => 'pay',
        'trx' => $payment['id'],
        'token' => $token,
    ]);

    $payment->gateway_trx_id = '';
    $payment->pg_url_payment = $paymentUrl;
    $payment->payment_method = 'M-Pesa';
    $payment->payment_channel = 'STK Push';
    $payment->pg_request = json_encode($state);
    $payment->expired_date = date('Y-m-d H:i:s', time() + $timeoutSeconds + 600);
    $payment->save();

    r2($paymentUrl, 's', Lang::T('Enter your M-Pesa phone number to receive the STK Push prompt.'));
}

function mpesastkpush_payment_notification()
{
    $action = strtolower(_req('action', ''));

    if ($action === 'pay' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        mpesastkpush_handle_pay_page();
        return;
    }

    if ($action === 'init' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        mpesastkpush_handle_init();
        return;
    }

    if ($action === 'status' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        mpesastkpush_handle_status();
        return;
    }

    mpesastkpush_handle_callback();
}

function mpesastkpush_get_status($trx, $user)
{
    mpesastkpush_validate_config();

    if ((int) $trx['status'] === 2) {
        r2(U . 'order/view/' . $trx['id'], 's', Lang::T('Transaction successful.'));
    }

    if ((int) $trx['status'] === 3 || (int) $trx['status'] === 4) {
        r2(U . 'order/view/' . $trx['id'], 'e', Lang::T('Transaction failed or was cancelled.'));
    }

    if (empty($trx['gateway_trx_id'])) {
        r2($trx['pg_url_payment'], 'w', Lang::T('Open the M-Pesa payment page and enter your phone number.'));
    }

    $query = mpesastkpush_query_stk($trx['gateway_trx_id']);
    if (!$query['ok']) {
        r2(U . 'order/view/' . $trx['id'], 'w', Lang::T('M-Pesa payment is still pending.'));
    }

    $data = $query['data'];
    $resultCode = isset($data['ResultCode']) ? (string) $data['ResultCode'] : '';
    $safeResponse = mpesastkpush_safe_status_response($data);

    $payment = ORM::for_table('tbl_payment_gateway')->find_one($trx['id']);
    if (!$payment) {
        r2(U . 'order/package', 'e', Lang::T('Payment transaction was not found.'));
    }

    if ($resultCode === '0') {
        $invoice = mpesastkpush_activate_transaction($payment);
        if (!$invoice) {
            $payment->pg_paid_response = json_encode($safeResponse);
            $payment->save();
            r2(U . 'order/view/' . $trx['id'], 'w', Lang::T('Payment was received, but package activation is pending. Please contact support.'));
        }

        $payment->pg_paid_response = json_encode($safeResponse);
        $payment->payment_method = 'M-Pesa';
        $payment->payment_channel = 'STK Push';
        $payment->paid_date = date('Y-m-d H:i:s');
        $payment->trx_invoice = $invoice;
        $payment->status = 2;
        $payment->save();

        r2(U . 'order/view/' . $trx['id'], 's', Lang::T('Transaction successful.'));
    }

    if ($resultCode !== '' && $resultCode !== '0') {
        $payment->pg_paid_response = json_encode($safeResponse);
        $payment->status = 3;
        $payment->save();
        r2(U . 'order/view/' . $trx['id'], 'e', Lang::T('M-Pesa payment failed or was cancelled.'));
    }

    r2(U . 'order/view/' . $trx['id'], 'w', Lang::T('M-Pesa payment is still pending.'));
}

function mpesastkpush_handle_pay_page()
{
    list($payment, $state) = mpesastkpush_load_public_payment(_get('trx'), _get('token'));
    if (!$payment) {
        mpesastkpush_render_payment_page(null, [], 'Invalid or expired payment link.', 'failed');
        return;
    }

    if ((int) $payment['status'] === 2) {
        mpesastkpush_render_payment_page($payment, $state, 'Payment already confirmed. Your package is active.', 'success');
        return;
    }

    if ((int) $payment['status'] === 3 || (int) $payment['status'] === 4) {
        mpesastkpush_render_payment_page($payment, $state, 'This payment was not completed. Please start a new order.', 'failed');
        return;
    }

    mpesastkpush_render_payment_page($payment, $state, '', !empty($state['checkout_request_id']) ? 'pending' : 'ready');
}

function mpesastkpush_handle_init()
{
    list($payment, $state) = mpesastkpush_load_public_payment(_post('trx'), _post('token'));
    if (!$payment) {
        mpesastkpush_render_payment_page(null, [], 'Invalid or expired payment link.', 'failed');
        return;
    }

    if ((int) $payment['status'] !== 1) {
        mpesastkpush_render_payment_page($payment, $state, 'This transaction is no longer pending.', ((int) $payment['status'] === 2) ? 'success' : 'failed');
        return;
    }

    $phone = mpesastkpush_format_phone(_post('phone'));
    if ($phone === false) {
        mpesastkpush_render_payment_page($payment, $state, 'Enter a valid Safaricom number such as 07XXXXXXXX or 2547XXXXXXXX.', 'ready');
        return;
    }

    $amount = mpesastkpush_amount($payment['price']);
    if ($amount < 1) {
        mpesastkpush_render_payment_page($payment, $state, 'The package amount must be at least KES 1 for M-Pesa STK Push.', 'failed');
        return;
    }

    $response = mpesastkpush_send_stk($payment, $phone, $amount);
    $state['phone'] = $phone;
    $state['amount'] = $amount;
    $state['updated_at'] = date('Y-m-d H:i:s');

    if (!$response['ok']) {
        $state['status_note'] = $response['message'];
        $state['last_error_code'] = $response['code'];
        $payment->pg_request = json_encode($state);
        $payment->save();
        mpesastkpush_render_payment_page($payment, $state, $response['message'], 'ready');
        return;
    }

    $data = $response['data'];
    $state['merchant_request_id'] = isset($data['MerchantRequestID']) ? (string) $data['MerchantRequestID'] : '';
    $state['checkout_request_id'] = isset($data['CheckoutRequestID']) ? (string) $data['CheckoutRequestID'] : '';
    $state['response_code'] = isset($data['ResponseCode']) ? (string) $data['ResponseCode'] : '';
    $state['response_description'] = isset($data['ResponseDescription']) ? (string) $data['ResponseDescription'] : '';
    $state['status_note'] = 'STK Push sent to customer phone';

    $payment->gateway_trx_id = $state['checkout_request_id'];
    $payment->pg_request = json_encode($state);
    $payment->expired_date = date('Y-m-d H:i:s', time() + mpesastkpush_timeout_seconds() + 600);
    $payment->save();

    _log('M-Pesa STK Push initiated for transaction #' . $payment['id'] . ' checkout ' . $state['checkout_request_id'], 'Payment Gateway', 0);
    mpesastkpush_render_payment_page($payment, $state, 'Check your phone and enter your M-Pesa PIN.', 'pending');
}

function mpesastkpush_handle_status()
{
    header('Content-Type: application/json');

    list($payment, $state) = mpesastkpush_load_public_payment(_get('trx'), _get('token'));
    if (!$payment) {
        echo json_encode(['status' => 'invalid', 'message' => 'Invalid payment link.']);
        return;
    }

    if ((int) $payment['status'] === 2) {
        echo json_encode(['status' => 'paid', 'message' => 'Payment confirmed.', 'redirect' => U . 'order/view/' . $payment['id']]);
        return;
    }

    if ((int) $payment['status'] === 3) {
        $response = mpesastkpush_decode_json($payment['pg_paid_response']);
        echo json_encode(['status' => 'failed', 'message' => $response['ResultDesc'] ?? 'Payment failed or was cancelled.']);
        return;
    }

    if ((int) $payment['status'] === 4) {
        echo json_encode(['status' => 'cancelled', 'message' => 'Payment was cancelled.']);
        return;
    }

    echo json_encode(['status' => 'pending', 'message' => $state['status_note'] ?? 'Waiting for M-Pesa confirmation.']);
}

function mpesastkpush_handle_callback()
{
    header('Content-Type: application/json');

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON payload']);
        return;
    }

    $callback = $payload['Body']['stkCallback'] ?? null;
    if (!is_array($callback)) {
        http_response_code(400);
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid STK callback payload']);
        return;
    }

    $merchantRequestId = isset($callback['MerchantRequestID']) ? (string) $callback['MerchantRequestID'] : '';
    $checkoutRequestId = isset($callback['CheckoutRequestID']) ? (string) $callback['CheckoutRequestID'] : '';
    $resultCode = isset($callback['ResultCode']) ? (int) $callback['ResultCode'] : 9999;
    $resultDesc = mpesastkpush_clean_text($callback['ResultDesc'] ?? 'Unknown M-Pesa result', 180);
    $metadata = mpesastkpush_extract_callback_metadata($callback['CallbackMetadata']['Item'] ?? []);

    $payment = ORM::for_table('tbl_payment_gateway')
        ->where('gateway', 'mpesastkpush')
        ->where('gateway_trx_id', $checkoutRequestId)
        ->find_one();

    if (!$payment) {
        _log('M-Pesa STK Push callback received for unknown checkout ' . $checkoutRequestId, 'Payment Gateway', 0);
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        return;
    }

    $state = mpesastkpush_decode_json($payment['pg_request']);
    $state['merchant_request_id'] = $merchantRequestId;
    $state['checkout_request_id'] = $checkoutRequestId;
    $state['result_code'] = $resultCode;
    $state['result_desc'] = $resultDesc;
    $state['updated_at'] = date('Y-m-d H:i:s');

    $safeResponse = [
        'ResultCode' => $resultCode,
        'ResultDesc' => $resultDesc,
        'MerchantRequestID' => $merchantRequestId,
        'CheckoutRequestID' => $checkoutRequestId,
        'MpesaReceiptNumber' => $metadata['MpesaReceiptNumber'] ?? '',
        'Amount' => $metadata['Amount'] ?? '',
        'PhoneNumber' => $metadata['PhoneNumber'] ?? '',
        'TransactionDate' => $metadata['TransactionDate'] ?? '',
    ];

    if ((int) $payment['status'] === 2) {
        $payment->pg_request = json_encode($state);
        $payment->save();
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        return;
    }

    if ($resultCode === 0) {
        $invoice = mpesastkpush_activate_transaction($payment);
        if (!$invoice) {
            $state['status_note'] = 'Payment received, package activation pending';
            $payment->pg_request = json_encode($state);
            $payment->pg_paid_response = json_encode($safeResponse);
            $payment->save();
            _log('M-Pesa STK Push payment received but activation failed for transaction #' . $payment['id'], 'Payment Gateway', 0);
            echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            return;
        }

        $state['status_note'] = 'Payment confirmed and package activated';
        $payment->pg_request = json_encode($state);
        $payment->pg_paid_response = json_encode($safeResponse);
        $payment->payment_method = 'M-Pesa';
        $payment->payment_channel = 'STK Push';
        $payment->paid_date = date('Y-m-d H:i:s');
        $payment->trx_invoice = $invoice;
        $payment->status = 2;
        $payment->save();
        _log('M-Pesa STK Push payment successful for transaction #' . $payment['id'] . ' receipt ' . ($metadata['MpesaReceiptNumber'] ?? ''), 'Payment Gateway', 0);
    } else {
        $state['status_note'] = $resultDesc;
        $payment->pg_request = json_encode($state);
        $payment->pg_paid_response = json_encode($safeResponse);
        $payment->status = 3;
        $payment->save();
        _log('M-Pesa STK Push payment failed for transaction #' . $payment['id'] . ' result ' . $resultCode, 'Payment Gateway', 0);
    }

    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
}

function mpesastkpush_activate_transaction($payment)
{
    global $trx;

    if ((int) $payment['status'] === 2) {
        return $payment['trx_invoice'] ?: true;
    }

    $user = null;
    if (!empty($payment['user_id'])) {
        $user = ORM::for_table('tbl_customers')->find_one($payment['user_id']);
    }
    if (!$user && !empty($payment['username'])) {
        $user = ORM::for_table('tbl_customers')->where('username', $payment['username'])->find_one();
    }
    if (!$user) {
        return false;
    }

    $trx = $payment;
    return Package::rechargeUser($user['id'], $payment['routers'], $payment['plan_id'], $payment['gateway'], 'M-Pesa STK Push', 'M-Pesa STK Push payment');
}

function mpesastkpush_send_stk($payment, $phone, $amount)
{
    $token = mpesastkpush_access_token();
    if (!$token['ok']) {
        return $token;
    }

    $timestamp = date('YmdHis');
    $shortcode = mpesastkpush_config('mpesastkpush_shortcode');
    $passkey = mpesastkpush_config('mpesastkpush_passkey');
    $accountReference = mpesastkpush_account_reference($payment['id']);
    $transactionType = mpesastkpush_config('mpesastkpush_shortcode_type', 'paybill') === 'till'
        ? 'CustomerBuyGoodsOnline'
        : 'CustomerPayBillOnline';

    $payload = [
        'BusinessShortCode' => $shortcode,
        'Password' => base64_encode($shortcode . $passkey . $timestamp),
        'Timestamp' => $timestamp,
        'TransactionType' => $transactionType,
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => mpesastkpush_callback_url(),
        'AccountReference' => $accountReference,
        'TransactionDesc' => mpesastkpush_config('mpesastkpush_transaction_desc', 'Internet Package Payment'),
    ];

    $response = mpesastkpush_http_request(
        mpesastkpush_api_base() . '/mpesa/stkpush/v1/processrequest',
        'POST',
        [
            'Authorization: Bearer ' . $token['data']['access_token'],
            'Content-Type: application/json',
        ],
        json_encode($payload)
    );

    if (!$response['ok']) {
        return [
            'ok' => false,
            'code' => $response['code'],
            'message' => 'Unable to send the M-Pesa STK Push request. Please try again.',
            'data' => $response['data'],
        ];
    }

    $responseCode = isset($response['data']['ResponseCode']) ? (string) $response['data']['ResponseCode'] : '';
    if ($responseCode !== '0') {
        return [
            'ok' => false,
            'code' => $responseCode ?: 'mpesa_error',
            'message' => $response['data']['ResponseDescription'] ?? 'M-Pesa rejected the STK Push request.',
            'data' => $response['data'],
        ];
    }

    return [
        'ok' => true,
        'code' => '0',
        'message' => 'STK Push request sent.',
        'data' => $response['data'],
    ];
}

function mpesastkpush_query_stk($checkoutRequestId)
{
    $token = mpesastkpush_access_token();
    if (!$token['ok']) {
        return $token;
    }

    $timestamp = date('YmdHis');
    $shortcode = mpesastkpush_config('mpesastkpush_shortcode');
    $payload = [
        'BusinessShortCode' => $shortcode,
        'Password' => base64_encode($shortcode . mpesastkpush_config('mpesastkpush_passkey') . $timestamp),
        'Timestamp' => $timestamp,
        'CheckoutRequestID' => $checkoutRequestId,
    ];

    return mpesastkpush_http_request(
        mpesastkpush_api_base() . '/mpesa/stkpushquery/v1/query',
        'POST',
        [
            'Authorization: Bearer ' . $token['data']['access_token'],
            'Content-Type: application/json',
        ],
        json_encode($payload)
    );
}

function mpesastkpush_access_token()
{
    $credentials = base64_encode(mpesastkpush_config('mpesastkpush_consumer_key') . ':' . mpesastkpush_config('mpesastkpush_consumer_secret'));
    $response = mpesastkpush_http_request(
        mpesastkpush_api_base() . '/oauth/v1/generate?grant_type=client_credentials',
        'GET',
        ['Authorization: Basic ' . $credentials]
    );

    if (!$response['ok'] || empty($response['data']['access_token'])) {
        return [
            'ok' => false,
            'code' => $response['code'] ?: 'oauth_failed',
            'message' => 'Unable to authenticate with M-Pesa Daraja. Please check the gateway credentials.',
            'data' => [],
        ];
    }

    return [
        'ok' => true,
        'code' => '0',
        'message' => 'Authenticated.',
        'data' => ['access_token' => $response['data']['access_token']],
    ];
}

function mpesastkpush_http_request($url, $method = 'GET', $headers = [], $body = null)
{
    $curl = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $headers,
    ];

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $body;
    }

    curl_setopt_array($curl, $options);
    $raw = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($raw === false) {
        return ['ok' => false, 'code' => 'curl_error', 'data' => [], 'message' => $error];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'code' => 'invalid_json', 'data' => [], 'message' => 'Invalid JSON response from M-Pesa.'];
    }

    return [
        'ok' => $httpCode >= 200 && $httpCode < 300,
        'code' => (string) $httpCode,
        'data' => $data,
        'message' => $data['errorMessage'] ?? $data['ResponseDescription'] ?? '',
    ];
}

function mpesastkpush_render_payment_page($payment, $state, $message = '', $status = 'ready')
{
    $portal = mpesastkpush_portal_settings();
    $title = mpesastkpush_escape($portal['title']);
    $welcome = mpesastkpush_escape($portal['welcome']);
    $primary = mpesastkpush_escape($portal['primary_color']);
    $secondary = mpesastkpush_escape($portal['secondary_color']);
    $footer = mpesastkpush_escape($portal['footer_text']);
    $supportPhone = mpesastkpush_escape($portal['support_phone']);
    $logoUrl = mpesastkpush_escape($portal['logo_url']);
    $messageHtml = mpesastkpush_escape($message);

    $trxId = $payment ? (int) $payment['id'] : 0;
    $token = mpesastkpush_escape(_req('token'));
    $packageName = $payment ? mpesastkpush_escape($payment['plan_name']) : 'Payment unavailable';
    $amount = $payment ? mpesastkpush_escape(Lang::moneyFormat($payment['price'])) : '';
    $defaultPhone = mpesastkpush_escape($state['phone'] ?? $state['default_phone'] ?? mpesastkpush_config('mpesastkpush_test_phone', ''));
    $payAction = mpesastkpush_build_url(['action' => 'init']);
    $statusUrl = mpesastkpush_build_url(['action' => 'status', 'trx' => $trxId, 'token' => _req('token')]);
    $orderUrl = $payment ? U . 'order/view/' . $payment['id'] : U . 'order/package';
    $packageUrl = U . 'order/package';
    $canPay = $payment && (int) $payment['status'] === 1 && $status !== 'failed';
    $statusClass = in_array($status, ['success', 'failed', 'pending'], true) ? $status : 'ready';

    echo '<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . $title . ' - M-Pesa STK Push</title>
    <style>
        :root {
            --primary: ' . $primary . ';
            --secondary: ' . $secondary . ';
            --bg: #f1f1f1;
            --text: #1f2933;
            --muted: #6b7280;
            --white: #ffffff;
            --danger: #dc3545;
            --info: #0ea5e9;
            --border: #d8e0d2;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
        }
        .wrap {
            width: min(100%, 520px);
            margin: 0 auto;
            padding: 26px 16px;
        }
        .brand {
            text-align: center;
            margin-bottom: 18px;
        }
        .logo {
            max-width: 128px;
            max-height: 64px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .brand h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
        }
        .brand p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
        }
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(31, 41, 51, .10);
            overflow: hidden;
        }
        .card-head {
            padding: 18px 20px;
            background: var(--primary);
            color: var(--white);
        }
        .card-head h2 {
            margin: 0;
            font-size: 18px;
        }
        .card-body {
            padding: 20px;
        }
        .summary {
            margin: 0 0 18px;
            padding: 0;
            list-style: none;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        .summary li {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
        }
        .summary li:last-child { border-bottom: 0; }
        .summary span:first-child { color: var(--muted); }
        .summary strong { text-align: right; }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
        }
        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 14px;
            font-size: 16px;
            outline: none;
        }
        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(249, 192, 43, .32);
        }
        .help {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary {
            width: 100%;
            background: var(--primary);
            color: var(--white);
            margin-top: 16px;
        }
        .btn-secondary {
            background: var(--secondary);
            color: #1f2933;
        }
        .btn-light {
            background: #eef3ec;
            color: var(--text);
        }
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 18px;
        }
        .notice {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 700;
        }
        .notice.ready { background: #eef8ef; color: #256b2b; }
        .notice.pending { background: #fff3c4; color: #7c5a00; }
        .notice.success { background: #e8f7ed; color: #176b2c; }
        .notice.failed { background: #fde8e8; color: #9b1c1c; }
        .spinner {
            width: 36px;
            height: 36px;
            margin: 4px auto 14px;
            border: 4px solid #e5e7eb;
            border-top-color: var(--primary);
            border-radius: 999px;
            animation: spin 1s linear infinite;
        }
        .pending-box {
            text-align: center;
            padding: 10px 0 4px;
        }
        .footer {
            margin-top: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 420px) {
            .wrap { padding: 18px 12px; }
            .card-body { padding: 16px; }
            .actions { grid-template-columns: 1fr; }
            .summary li { flex-direction: column; gap: 3px; }
            .summary strong { text-align: left; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <div class="brand">';
    if ($logoUrl !== '') {
        echo '<img class="logo" src="' . $logoUrl . '" alt="' . $title . ' logo">';
    }
    echo '          <h1>' . $title . '</h1>
            <p>' . $welcome . '</p>
        </div>
        <section class="card">
            <div class="card-head"><h2>M-Pesa STK Push</h2></div>
            <div class="card-body">';

    if ($messageHtml !== '') {
        echo '<p id="payment-message" class="notice ' . $statusClass . '">' . $messageHtml . '</p>';
    } else {
        echo '<p id="payment-message" class="notice ready">Pay securely through M-Pesa before connecting to the internet.</p>';
    }

    echo '          <ul class="summary">
                    <li><span>Package</span><strong>' . $packageName . '</strong></li>
                    <li><span>Amount</span><strong>' . $amount . '</strong></li>
                    <li><span>Transaction</span><strong>#' . $trxId . '</strong></li>
                </ul>';

    if ($status === 'pending') {
        echo '      <div class="pending-box">
                        <div class="spinner" aria-hidden="true"></div>
                        <strong>Check your phone and enter your M-Pesa PIN.</strong>
                        <p class="help">This page will update automatically when M-Pesa confirms the payment.</p>
                    </div>';
    }

    if ($canPay && $status !== 'pending') {
        echo '      <form method="post" action="' . mpesastkpush_escape($payAction) . '">
                        <input type="hidden" name="trx" value="' . $trxId . '">
                        <input type="hidden" name="token" value="' . $token . '">
                        <label for="phone">M-Pesa phone number</label>
                        <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="07XXXXXXXX" value="' . $defaultPhone . '" required>
                        <p class="help">Use a Safaricom number in the format 07XXXXXXXX, 01XXXXXXXX, 2547XXXXXXXX, or 2541XXXXXXXX.</p>
                        <button class="btn btn-primary" type="submit">Pay with M-Pesa</button>
                    </form>';
    }

    echo '          <div class="actions">
                    <a class="btn btn-secondary" href="' . mpesastkpush_escape($orderUrl) . '">View order</a>
                    <a class="btn btn-light" href="' . mpesastkpush_escape($packageUrl) . '">Choose package</a>
                </div>';

    if ($supportPhone !== '') {
        echo '<p class="help">Need help? Contact support: ' . $supportPhone . '</p>';
    }

    echo '      </div>
        </section>
        <div class="footer">' . $footer . '</div>
    </main>';

    if ($status === 'pending') {
        echo '<script>
            (function () {
                var statusUrl = ' . json_encode($statusUrl) . ';
                var orderUrl = ' . json_encode($orderUrl) . ';
                var message = document.getElementById("payment-message");
                function poll() {
                    fetch(statusUrl, { credentials: "same-origin" })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (!message) return;
                            message.textContent = data.message || "Waiting for M-Pesa confirmation.";
                            message.className = "notice " + (data.status === "paid" ? "success" : (data.status === "failed" || data.status === "cancelled" ? "failed" : "pending"));
                            if (data.status === "paid") {
                                window.location.href = data.redirect || orderUrl;
                            }
                        })
                        .catch(function () {});
                }
                setInterval(poll, 5000);
                setTimeout(poll, 1200);
            }());
        </script>';
    }

    echo '</body></html>';
}

function mpesastkpush_load_public_payment($id, $token)
{
    $paymentId = (int) Text::numeric((string) $id);
    $token = trim((string) $token);
    if ($paymentId <= 0 || $token === '') {
        return [null, []];
    }

    $payment = ORM::for_table('tbl_payment_gateway')
        ->where('gateway', 'mpesastkpush')
        ->find_one($paymentId);

    if (!$payment) {
        return [null, []];
    }

    $state = mpesastkpush_decode_json($payment['pg_request']);
    if (empty($state['state_hash']) || !hash_equals($state['state_hash'], hash('sha256', $token))) {
        return [null, []];
    }

    return [$payment, $state];
}

function mpesastkpush_extract_callback_metadata($items)
{
    $metadata = [];
    if (!is_array($items)) {
        return $metadata;
    }

    foreach ($items as $item) {
        if (!isset($item['Name'])) {
            continue;
        }
        $metadata[$item['Name']] = isset($item['Value']) ? $item['Value'] : '';
    }

    return $metadata;
}

function mpesastkpush_safe_status_response($data)
{
    return [
        'ResponseCode' => $data['ResponseCode'] ?? '',
        'ResponseDescription' => $data['ResponseDescription'] ?? '',
        'MerchantRequestID' => $data['MerchantRequestID'] ?? '',
        'CheckoutRequestID' => $data['CheckoutRequestID'] ?? '',
        'ResultCode' => $data['ResultCode'] ?? '',
        'ResultDesc' => $data['ResultDesc'] ?? '',
    ];
}

function mpesastkpush_settings()
{
    return [
        'enabled' => mpesastkpush_config('mpesastkpush_enabled', 'no'),
        'environment' => mpesastkpush_config('mpesastkpush_environment', 'sandbox'),
        'shortcode_type' => mpesastkpush_config('mpesastkpush_shortcode_type', 'paybill'),
        'shortcode' => mpesastkpush_config('mpesastkpush_shortcode', ''),
        'consumer_key' => mpesastkpush_config('mpesastkpush_consumer_key', ''),
        'consumer_secret' => mpesastkpush_config('mpesastkpush_consumer_secret', ''),
        'passkey' => mpesastkpush_config('mpesastkpush_passkey', ''),
        'callback_url' => mpesastkpush_config('mpesastkpush_callback_url', ''),
        'account_prefix' => mpesastkpush_config('mpesastkpush_account_prefix', 'FASTNETPAY'),
        'transaction_desc' => mpesastkpush_config('mpesastkpush_transaction_desc', 'Internet Package Payment'),
        'timeout_seconds' => mpesastkpush_config('mpesastkpush_timeout_seconds', '300'),
        'test_phone' => mpesastkpush_config('mpesastkpush_test_phone', ''),
        'walled_garden_domains' => mpesastkpush_config('mpesastkpush_walled_garden_domains', mpesastkpush_default_walled_garden_domains()),
        'portal_title' => mpesastkpush_config('mpesastkpush_portal_title', 'FASTNETPAY WiFi'),
        'portal_welcome' => mpesastkpush_config('mpesastkpush_portal_welcome', 'Pay securely with M-Pesa STK Push.'),
        'portal_logo_url' => mpesastkpush_config('mpesastkpush_portal_logo_url', ''),
        'portal_support_phone' => mpesastkpush_config('mpesastkpush_support_phone', ''),
        'portal_primary_color' => mpesastkpush_config('mpesastkpush_portal_primary_color', '#41a146'),
        'portal_secondary_color' => mpesastkpush_config('mpesastkpush_portal_secondary_color', '#f9c02b'),
        'portal_footer_text' => mpesastkpush_config('mpesastkpush_portal_footer_text', 'Powered by FASTNETPAY'),
    ];
}

function mpesastkpush_portal_settings()
{
    return [
        'title' => mpesastkpush_config('mpesastkpush_portal_title', 'FASTNETPAY WiFi'),
        'welcome' => mpesastkpush_config('mpesastkpush_portal_welcome', 'Pay securely with M-Pesa STK Push.'),
        'logo_url' => mpesastkpush_config('mpesastkpush_portal_logo_url', ''),
        'support_phone' => mpesastkpush_config('mpesastkpush_support_phone', ''),
        'primary_color' => mpesastkpush_clean_hex_color(mpesastkpush_config('mpesastkpush_portal_primary_color', '#41a146'), '#41a146'),
        'secondary_color' => mpesastkpush_clean_hex_color(mpesastkpush_config('mpesastkpush_portal_secondary_color', '#f9c02b'), '#f9c02b'),
        'footer_text' => mpesastkpush_config('mpesastkpush_portal_footer_text', 'Powered by FASTNETPAY'),
    ];
}

function mpesastkpush_config($key, $default = '')
{
    global $config;

    if (isset($config[$key]) && $config[$key] !== '') {
        return $config[$key];
    }

    $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
    if ($row) {
        return $row['value'];
    }

    if (strpos($key, 'mpesastkpush_') === 0) {
        $legacyKey = 'mpesa_stk_push_' . substr($key, strlen('mpesastkpush_'));
        if (isset($config[$legacyKey]) && $config[$legacyKey] !== '') {
            return $config[$legacyKey];
        }

        $legacyRow = ORM::for_table('tbl_appconfig')->where('setting', $legacyKey)->find_one();
        if ($legacyRow) {
            return $legacyRow['value'];
        }
    }

    return $default;
}

function mpesastkpush_save_setting($setting, $value)
{
    $row = ORM::for_table('tbl_appconfig')->where('setting', $setting)->find_one();
    if (!$row) {
        $row = ORM::for_table('tbl_appconfig')->create();
        $row->setting = $setting;
    }
    $row->value = (string) $value;
    $row->save();
}

function mpesastkpush_save_sensitive_setting($setting, $value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return;
    }
    mpesastkpush_save_setting($setting, $value);
}

function mpesastkpush_mask($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    return Text::maskText($value);
}

function mpesastkpush_api_base()
{
    return mpesastkpush_config('mpesastkpush_environment', 'sandbox') === 'live'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke';
}

function mpesastkpush_default_callback_url()
{
    return Text::url('callback/mpesastkpush');
}

function mpesastkpush_callback_url()
{
    $configured = trim(mpesastkpush_config('mpesastkpush_callback_url', ''));
    return $configured !== '' ? $configured : mpesastkpush_default_callback_url();
}

function mpesastkpush_build_url($params = [])
{
    $base = Text::url('callback/mpesastkpush');
    if (!$params) {
        return $base;
    }
    return $base . (strpos($base, '?') === false ? '?' : '&') . http_build_query($params);
}

function mpesastkpush_default_walled_garden_domains()
{
    return "safaricom.co.ke\n*.safaricom.co.ke\napi.safaricom.co.ke\nsandbox.safaricom.co.ke";
}

function mpesastkpush_account_reference($paymentId)
{
    $prefix = mpesastkpush_clean_reference(mpesastkpush_config('mpesastkpush_account_prefix', 'FASTNETPAY'), 12);
    $reference = $prefix . (string) $paymentId;
    return substr($reference, 0, 12);
}

function mpesastkpush_timeout_seconds()
{
    $timeout = (int) mpesastkpush_config('mpesastkpush_timeout_seconds', '300');
    if ($timeout < 30 || $timeout > 900) {
        return 300;
    }
    return $timeout;
}

function mpesastkpush_amount($amount)
{
    return max(0, (int) ceil((float) $amount));
}

function mpesastkpush_format_phone($phone)
{
    $digits = Text::numeric((string) $phone);

    if (strlen($digits) === 10 && substr($digits, 0, 1) === '0') {
        $digits = '254' . substr($digits, 1);
    } elseif (strlen($digits) === 9 && in_array(substr($digits, 0, 1), ['7', '1'], true)) {
        $digits = '254' . $digits;
    } elseif (strlen($digits) === 12 && substr($digits, 0, 3) === '254') {
        $digits = $digits;
    } else {
        return false;
    }

    if (!preg_match('/^254(7|1)[0-9]{8}$/', $digits)) {
        return false;
    }

    return $digits;
}

function mpesastkpush_clean_reference($value, $maxLength = 12)
{
    $value = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
    return substr($value, 0, $maxLength);
}

function mpesastkpush_clean_text($value, $maxLength = 120)
{
    $value = trim(strip_tags((string) $value));
    $value = preg_replace('/\s+/', ' ', $value);
    return substr($value, 0, $maxLength);
}

function mpesastkpush_clean_hex_color($value, $fallback)
{
    $value = trim((string) $value);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
        return strtolower($value);
    }
    return $fallback;
}

function mpesastkpush_clean_walled_garden($value)
{
    $lines = preg_split('/\r\n|\r|\n/', (string) $value);
    $domains = [];

    foreach ($lines as $line) {
        $line = strtolower(trim(strip_tags($line)));
        $line = preg_replace('/\s+/', '', $line);
        if ($line === '') {
            continue;
        }
        if (!preg_match('/^(\*\.)?[a-z0-9.-]+$/', $line)) {
            continue;
        }
        $domains[$line] = true;
    }

    return implode("\n", array_keys($domains));
}

function mpesastkpush_decode_json($value)
{
    $data = json_decode((string) $value, true);
    return is_array($data) ? $data : [];
}

function mpesastkpush_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
