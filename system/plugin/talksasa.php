<?php

/**
 * FASTNETPAY TALKSASA SMS gateway plugin.
 */

// FASTNETPAY renders TALKSASA inside the SMS sidebar group in ui/ui/admin/header.tpl.

function talksasa()
{
    global $ui, $config;

    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'], true)) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }

    if (_post('save') === 'save') {
        if (class_exists('Csrf') && !Csrf::check(_post('csrf_token'))) {
            r2(U . 'plugin/talksasa', 'e', Lang::T('Invalid CSRF token. Please try again.'));
        }

        $endpoint = trim(_post('api_endpoint', talksasa_default_endpoint()));
        if (!talksasa_is_valid_endpoint($endpoint)) {
            r2(U . 'plugin/talksasa', 'e', Lang::T('TALKSASA API endpoint must be a valid HTTP or HTTPS URL.'));
        }

        $senderId = talksasa_clean_sender(_post('sender_id'));
        if ($senderId === '') {
            r2(U . 'plugin/talksasa', 'e', Lang::T('TALKSASA Sender ID is required.'));
        }

        $token = trim(_post('api_token'));
        if ($token === '' && talksasa_config('talksasa_api_token', '') === '') {
            r2(U . 'plugin/talksasa', 'e', Lang::T('TALKSASA API token is required.'));
        }

        talksasa_save_setting('talksasa_api_endpoint', $endpoint);
        talksasa_save_setting('talksasa_sender_id', $senderId);
        if ($token !== '') {
            talksasa_save_setting('talksasa_api_token', $token);
        }

        talksasa_save_setting('sms_gateway', 'talksasa');
        talksasa_save_setting('sms_url', 'talksasa');

        _log('[' . $admin['username'] . ']: TALKSASA SMS settings saved', 'Admin', $admin['id']);
        r2(U . 'plugin/talksasa', 's', Lang::T('Settings Saved Successfully'));
    }

    $settings = talksasa_settings();
    $settings['api_token_masked'] = $settings['api_token'] !== '' ? Text::maskText($settings['api_token']) : '';

    $ui->assign('_title', 'TALKSASA SMS Gateway');
    $ui->assign('_system_menu', '');
    $ui->assign('_admin', $admin);
    $ui->assign('_c', $config);
    $ui->assign('talksasa', $settings);
    $ui->assign('csrf_token', class_exists('Csrf') ? Csrf::generateAndStoreToken() : '');
    $ui->display('talksasa.tpl');
}

function talksasa_send_sms($recipient, $message)
{
    $endpoint = talksasa_config('talksasa_api_endpoint', talksasa_default_endpoint());
    $token = talksasa_config('talksasa_api_token', '');
    $senderId = talksasa_config('talksasa_sender_id', '');

    if (!talksasa_is_valid_endpoint($endpoint) || trim($token) === '' || trim($senderId) === '') {
        Message::logMessage('TALKSASA SMS', (string) $recipient, (string) $message, 'Error', 'TALKSASA SMS gateway is not fully configured.');
        return false;
    }

    $normalized = talksasa_normalize_recipients($recipient);
    if ($normalized === '') {
        Message::logMessage('TALKSASA SMS', (string) $recipient, (string) $message, 'Error', 'Invalid recipient phone number.');
        return false;
    }

    $payload = [
        'recipient' => $normalized,
        'sender_id' => talksasa_clean_sender($senderId),
        'type' => 'plain',
        'message' => trim(strip_tags((string) $message)),
    ];

    $response = talksasa_http_post($endpoint, $token, $payload);
    if ($response['ok']) {
        Message::logMessage('TALKSASA SMS', $normalized, $payload['message'], 'Success', 'Message accepted by TALKSASA.');
        return true;
    }

    Message::logMessage('TALKSASA SMS', $normalized, $payload['message'], 'Error', $response['message']);
    return false;
}

function talksasa_http_post($endpoint, $token, $payload)
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $endpoint,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);

    $raw = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($raw === false) {
        return ['ok' => false, 'message' => 'TALKSASA network error: ' . talksasa_safe_error($error)];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'message' => 'TALKSASA returned an invalid response. HTTP ' . $httpCode];
    }

    if (($data['status'] ?? '') === 'success') {
        return ['ok' => true, 'message' => 'success'];
    }

    return [
        'ok' => false,
        'message' => talksasa_safe_error($data['message'] ?? ('TALKSASA request failed. HTTP ' . $httpCode)),
    ];
}

function talksasa_settings()
{
    return [
        'api_token' => talksasa_config('talksasa_api_token', ''),
        'api_endpoint' => talksasa_config('talksasa_api_endpoint', talksasa_default_endpoint()),
        'sender_id' => talksasa_config('talksasa_sender_id', ''),
    ];
}

function talksasa_default_endpoint()
{
    return 'https://bulksms.talksasa.com/api/v3/sms/send';
}

function talksasa_config($key, $default = '')
{
    global $config;

    if (class_exists('Tenant')) {
        $tenantRow = ORM::for_table('tenant_settings')
            ->where('tenant_id', Tenant::currentId())
            ->where('namespace', 'sms')
            ->where('setting', $key)
            ->find_one();
        if ($tenantRow) {
            return (string) $tenantRow['value'];
        }
    }

    if (isset($config[$key]) && $config[$key] !== '') {
        return $config[$key];
    }

    $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
    return $row ? $row['value'] : $default;
}

function talksasa_save_setting($setting, $value)
{
    if (class_exists('Tenant') && Tenant::isTenantRequest()) {
        Tenant::saveSetting('sms', $setting, (string) $value, $setting === 'talksasa_api_token');
        return;
    }

    $row = ORM::for_table('tbl_appconfig')->where('setting', $setting)->find_one();
    if (!$row) {
        $row = ORM::for_table('tbl_appconfig')->create();
        $row->setting = $setting;
    }
    $row->value = (string) $value;
    $row->save();
}

function talksasa_normalize_recipients($recipients)
{
    $raw = is_array($recipients) ? implode(',', $recipients) : (string) $recipients;
    $parts = preg_split('/[,;]+/', $raw);
    $normalized = [];

    foreach ($parts as $part) {
        $phone = talksasa_normalize_phone($part);
        if ($phone !== '') {
            $normalized[$phone] = true;
        }
    }

    return implode(',', array_keys($normalized));
}

function talksasa_normalize_phone($phone)
{
    $phone = trim((string) $phone);
    $hadPlus = strpos($phone, '+') === 0;
    $digits = preg_replace('/\D+/', '', $phone);

    if ($digits === '') {
        return '';
    }

    if (preg_match('/^0(7|1)[0-9]{8}$/', $digits)) {
        return '254' . substr($digits, 1);
    }

    if (preg_match('/^(7|1)[0-9]{8}$/', $digits)) {
        return '254' . $digits;
    }

    if (preg_match('/^254(7|1)[0-9]{8}$/', $digits)) {
        return $digits;
    }

    if ($hadPlus && preg_match('/^[1-9][0-9]{7,14}$/', $digits)) {
        return $digits;
    }

    if (preg_match('/^[1-9][0-9]{7,14}$/', $digits)) {
        return $digits;
    }

    return '';
}

function talksasa_clean_sender($senderId)
{
    $senderId = strtoupper(trim(strip_tags((string) $senderId)));
    $senderId = preg_replace('/[^A-Z0-9_-]/', '', $senderId);
    return substr($senderId, 0, 20);
}

function talksasa_is_valid_endpoint($endpoint)
{
    if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower(parse_url($endpoint, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

function talksasa_safe_error($message)
{
    $message = trim(strip_tags((string) $message));
    $message = preg_replace('/\s+/', ' ', $message);
    return substr($message, 0, 220);
}
