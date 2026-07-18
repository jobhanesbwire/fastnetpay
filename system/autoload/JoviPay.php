<?php

class JoviPay
{
    const SETTINGS_TABLE = 'jovipay_settings';
    const TX_TABLE = 'jovipay_transactions';
    const RECONNECT_TABLE = 'reconnection_attempts';
    const SCHEMA_VERSION = '2026-07-09-perf1';
    private static $settingsCache = [];

    public static function installSchema()
    {
        if (class_exists('FastnetpayRuntime') && FastnetpayRuntime::schemaFresh('jovipay', self::SCHEMA_VERSION, 86400)) {
            return;
        }

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS jovipay_settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            api_base_url VARCHAR(255) NULL,
            stk_endpoint VARCHAR(255) NULL,
            api_token_encrypted TEXT NULL,
            account_prefix VARCHAR(32) NOT NULL DEFAULT 'WIFI_',
            callback_url VARCHAR(255) NULL,
            callback_secret_encrypted TEXT NULL,
            mini_app_id VARCHAR(80) NULL,
            local_tunnel_url VARCHAR(255) NULL,
            production_callback_url VARCHAR(255) NULL,
            callback_mode VARCHAR(32) NOT NULL DEFAULT 'local_tunnel',
            gateway_label VARCHAR(80) NOT NULL DEFAULT 'M-Pesa STK Push',
            support_phone VARCHAR(40) NULL,
            support_whatsapp VARCHAR(255) NULL,
            payment_timeout_seconds INT UNSIGNED NOT NULL DEFAULT 300,
            polling_interval_seconds INT UNSIGNED NOT NULL DEFAULT 5,
            allowed_ips TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS jovipay_transactions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            payment_gateway_id INT UNSIGNED NULL,
            account_reference VARCHAR(160) NOT NULL,
            prefix VARCHAR(32) NULL,
            router_id INT UNSIGNED NULL,
            package_id INT UNSIGNED NULL,
            customer_id INT UNSIGNED NULL,
            username VARCHAR(190) NULL,
            mac_address VARCHAR(64) NULL,
            ip_address VARCHAR(64) NULL,
            phone VARCHAR(32) NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            mpesa_receipt_number VARCHAR(80) NULL,
            checkout_request_id VARCHAR(160) NULL,
            merchant_request_id VARCHAR(160) NULL,
            transaction_date VARCHAR(64) NULL,
            raw_payload MEDIUMTEXT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            activation_status VARCHAR(32) NOT NULL DEFAULT 'pending',
            callback_received_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY account_reference_unique (account_reference),
            INDEX receipt_idx (mpesa_receipt_number),
            INDEX payment_gateway_idx (payment_gateway_id),
            INDEX status_idx (status),
            INDEX router_idx (router_id),
            INDEX checkout_idx (checkout_request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS reconnection_attempts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            transaction_code VARCHAR(80) NOT NULL,
            phone VARCHAR(32) NULL,
            mac_address VARCHAR(64) NULL,
            username VARCHAR(190) NULL,
            router_id INT UNSIGNED NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            message TEXT NULL,
            created_at DATETIME NOT NULL,
            INDEX transaction_code_idx (transaction_code),
            INDEX router_idx (router_id),
            INDEX created_at_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensureColumn(self::TX_TABLE, 'payment_gateway_id', 'INT UNSIGNED NULL');
        self::ensureColumn(self::SETTINGS_TABLE, 'gateway_label', "VARCHAR(80) NOT NULL DEFAULT 'M-Pesa STK Push'");
        self::ensureColumn(self::SETTINGS_TABLE, 'support_whatsapp', 'VARCHAR(255) NULL');
        self::ensureColumn(self::SETTINGS_TABLE, 'callback_mode', "VARCHAR(32) NOT NULL DEFAULT 'local_tunnel'");
        self::ensureColumn(self::SETTINGS_TABLE, 'allowed_ips', 'TEXT NULL');
        self::ensureColumn(self::SETTINGS_TABLE, 'mini_app_id', 'VARCHAR(80) NULL');
        self::ensureColumn(self::SETTINGS_TABLE, 'tenant_id', 'INT UNSIGNED NULL');
        self::ensureColumn(self::TX_TABLE, 'tenant_id', 'INT UNSIGNED NULL');
        self::ensureColumn(self::RECONNECT_TABLE, 'tenant_id', 'INT UNSIGNED NULL');
        if (class_exists('FastnetpayRuntime')) {
            FastnetpayRuntime::markSchemaFresh('jovipay', self::SCHEMA_VERSION);
        }
    }

    public static function defaults()
    {
        return [
            'enabled' => '0',
            'api_base_url' => '',
            'stk_endpoint' => '/api/stk-push',
            'api_token' => '',
            'account_prefix' => 'WIFI_',
            'callback_url' => self::defaultCallbackUrl(),
            'callback_secret' => '',
            'mini_app_id' => '',
            'local_tunnel_url' => '',
            'production_callback_url' => self::defaultCallbackUrl(true),
            'callback_mode' => 'local_tunnel',
            'gateway_label' => 'M-Pesa STK Push',
            'support_phone' => '',
            'support_whatsapp' => '',
            'payment_timeout_seconds' => '300',
            'polling_interval_seconds' => '5',
            'allowed_ips' => '',
        ];
    }

    public static function settings($tenantId = null)
    {
        self::installSchema();
        $defaults = self::defaults();
        $tenantId = class_exists('Tenant') ? (int) ($tenantId ?: Tenant::currentId()) : 0;
        if (isset(self::$settingsCache[$tenantId])) {
            return self::$settingsCache[$tenantId];
        }
        $query = ORM::for_table(self::SETTINGS_TABLE)->order_by_asc('id');
        if (class_exists('Tenant') && Tenant::hasColumn(self::SETTINGS_TABLE, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
        $row = $query->find_one();
        if (!$row) {
            self::$settingsCache[$tenantId] = self::overlayTenantGatewaySettings($defaults, $tenantId);
            return self::$settingsCache[$tenantId];
        }

        self::$settingsCache[$tenantId] = self::overlayTenantGatewaySettings([
            'enabled' => (string) (int) $row['enabled'],
            'api_base_url' => (string) $row['api_base_url'],
            'stk_endpoint' => (string) $row['stk_endpoint'],
            'api_token' => self::decryptSecret($row['api_token_encrypted']),
            'account_prefix' => (string) ($row['account_prefix'] ?: 'WIFI_'),
            'callback_url' => (string) ($row['callback_url'] ?: self::defaultCallbackUrl()),
            'callback_secret' => self::decryptSecret($row['callback_secret_encrypted']),
            'mini_app_id' => (string) ($row['mini_app_id'] ?? ''),
            'local_tunnel_url' => (string) $row['local_tunnel_url'],
            'production_callback_url' => (string) ($row['production_callback_url'] ?: self::defaultCallbackUrl(true)),
            'callback_mode' => (string) ($row['callback_mode'] ?: 'local_tunnel'),
            'gateway_label' => (string) ($row['gateway_label'] ?: 'M-Pesa STK Push'),
            'support_phone' => (string) $row['support_phone'],
            'support_whatsapp' => (string) $row['support_whatsapp'],
            'payment_timeout_seconds' => (string) max(30, (int) $row['payment_timeout_seconds']),
            'polling_interval_seconds' => (string) max(3, (int) $row['polling_interval_seconds']),
            'allowed_ips' => (string) $row['allowed_ips'],
        ], $tenantId);
        return self::$settingsCache[$tenantId];
    }

    public static function publicSettings()
    {
        $settings = self::settings();
        $apiToken = $settings['api_token'];
        $callbackSecret = $settings['callback_secret'];
        unset($settings['api_token'], $settings['callback_secret']);
        $settings['api_token_masked'] = self::maskSecret($apiToken);
        $settings['callback_secret_masked'] = self::maskSecret($callbackSecret);
        $settings['effective_callback_url'] = self::effectiveCallbackUrl($settings);
        return $settings;
    }

    public static function saveFromPost($admin)
    {
        self::installSchema();
        $tenantId = class_exists('Tenant') ? Tenant::currentId() : 0;
        $settings = self::settings($tenantId);
        $apiBase = self::cleanUrl(_post('api_base_url'));
        $stkEndpoint = trim(_post('stk_endpoint', '/api/stk-push'));
        if ($apiBase === '' && _post('enabled') === 'yes') {
            throw new Exception('Jovi-Pay API Base URL is required when the integration is enabled.');
        }
        if ($apiBase !== '' && !filter_var($apiBase, FILTER_VALIDATE_URL)) {
            throw new Exception('Jovi-Pay API Base URL must be a valid URL.');
        }
        if ($stkEndpoint === '') {
            $stkEndpoint = '/api/stk-push';
        }

        $prefix = self::cleanPrefix(_post('account_prefix', 'WIFI_'));
        $callbackMode = in_array(_post('callback_mode'), ['local_tunnel', 'production'], true) ? _post('callback_mode') : 'local_tunnel';
        $callbackUrl = self::cleanUrl(_post('callback_url', self::defaultCallbackUrl()));
        $localTunnel = self::cleanUrl(_post('local_tunnel_url'));
        $productionUrl = self::cleanUrl(_post('production_callback_url', self::defaultCallbackUrl(true)));
        $timeout = max(30, min(1200, (int) _post('payment_timeout_seconds', 300)));
        $polling = max(3, min(60, (int) _post('polling_interval_seconds', 5)));

        $query = ORM::for_table(self::SETTINGS_TABLE)->order_by_asc('id');
        if (class_exists('Tenant') && Tenant::hasColumn(self::SETTINGS_TABLE, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
        $row = $query->find_one();
        if (!$row) {
            $row = ORM::for_table(self::SETTINGS_TABLE)->create();
            if (class_exists('Tenant')) {
                Tenant::stamp($row, $tenantId, self::SETTINGS_TABLE);
            }
            $row->created_at = date('Y-m-d H:i:s');
        }

        $row->enabled = _post('enabled') === 'yes' ? 1 : 0;
        $row->api_base_url = rtrim($apiBase, '/');
        $row->stk_endpoint = $stkEndpoint;
        if (trim(_post('api_token')) !== '') {
            $row->api_token_encrypted = self::encryptSecret(trim(_post('api_token')));
        } elseif (empty($row['api_token_encrypted']) && $settings['api_token'] !== '') {
            $row->api_token_encrypted = self::encryptSecret($settings['api_token']);
        }
        $row->account_prefix = $prefix;
        $row->callback_url = $callbackUrl ?: self::defaultCallbackUrl();
        if (trim(_post('callback_secret')) !== '') {
            $row->callback_secret_encrypted = self::encryptSecret(trim(_post('callback_secret')));
        } elseif (empty($row['callback_secret_encrypted']) && $settings['callback_secret'] !== '') {
            $row->callback_secret_encrypted = self::encryptSecret($settings['callback_secret']);
        }
        $row->mini_app_id = self::cleanText(_post('mini_app_id'), 80);
        $row->local_tunnel_url = $localTunnel;
        $row->production_callback_url = $productionUrl ?: self::defaultCallbackUrl(true);
        $row->callback_mode = $callbackMode;
        $row->gateway_label = self::cleanText(_post('gateway_label', 'M-Pesa STK Push'), 80);
        $row->support_phone = self::cleanText(_post('support_phone'), 40);
        $row->support_whatsapp = self::cleanUrl(_post('support_whatsapp'));
        $row->payment_timeout_seconds = $timeout;
        $row->polling_interval_seconds = $polling;
        $row->allowed_ips = self::cleanLines(_post('allowed_ips'));
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        self::$settingsCache = [];
        if (class_exists('Tenant')) {
            Tenant::saveSetting('jovipay', 'account_prefix', $prefix, false, $tenantId);
            Tenant::audit('tenant.payment_settings_changed', 'Jovi-Pay settings changed.', 'jovipay_settings', (string) $row->id(), $tenantId, (int) ($admin['id'] ?? 0));
        }

        _log('[' . ($admin['username'] ?? 'admin') . ']: Jovi-Pay integration settings saved', 'Admin', $admin['id'] ?? 0);
    }

    public static function isEnabled($tenantId = null)
    {
        $settings = self::settings($tenantId);
        return $settings['enabled'] === '1' || $settings['enabled'] === 'yes';
    }

    public static function startHotspotPayment($router, $planId, $phone, $mac, $ip)
    {
        self::installSchema();
        $tenantId = class_exists('Tenant') ? (int) ($router['tenant_id'] ?: Tenant::currentId()) : 0;
        $settings = self::settings($tenantId);
        if (!self::isEnabled($tenantId)) {
            throw new Exception('Jovi-Pay integration is not enabled yet.');
        }

        $formattedPhone = self::formatPhone($phone);
        if ($formattedPhone === false) {
            throw new Exception('Enter a valid Safaricom number such as 07XXXXXXXX.');
        }

        $plan = ORM::for_table('tbl_plans')
            ->where('id', (int) $planId)
            ->where('enabled', '1')
            ->where('type', 'Hotspot')
            ->find_one();
        if ($plan && class_exists('Tenant') && Tenant::hasColumn('tbl_plans', 'tenant_id') && (int) ($plan['tenant_id'] ?? 0) !== $tenantId) {
            $plan = null;
        }
        if (!$plan) {
            throw new Exception('Selected package is not available.');
        }

        $amount = self::amount($plan['price']);
        $customer = self::hotspotCustomer($router, $formattedPhone, $mac, $ip);
        $paymentToken = bin2hex(random_bytes(24));
        $reference = self::accountReference($settings['account_prefix'], (int) $router['id'], (int) $plan['id'], $mac, $ip);
        $callbackUrl = self::effectiveCallbackUrl($settings);

        $payment = ORM::for_table('tbl_payment_gateway')->create();
        if (class_exists('Tenant')) {
            Tenant::stamp($payment, $tenantId, 'tbl_payment_gateway');
        }
        $payment->username = $customer['username'];
        $payment->user_id = (int) $customer['id'];
        $payment->gateway = 'jovipay';
        $payment->gateway_trx_id = '';
        $payment->plan_id = (int) $plan['id'];
        $payment->plan_name = $plan['name_plan'];
        $payment->routers_id = (int) $router['id'];
        $payment->routers = $router['name'];
        $payment->price = $amount;
        $payment->payment_method = 'M-Pesa';
        $payment->payment_channel = 'Jovi-Pay STK Push';
        $payment->created_date = date('Y-m-d H:i:s');
        $payment->expired_date = date('Y-m-d H:i:s', time() + (int) $settings['payment_timeout_seconds'] + 600);
        $payment->status = 1;
        $payment->pg_request = json_encode([
            'payment_token_hash' => hash('sha256', $paymentToken),
            'provider' => 'jovipay',
            'phone' => $formattedPhone,
            'mac' => $mac,
            'ip' => $ip,
            'router_id' => (int) $router['id'],
            'account_reference' => $reference,
            'callback_url' => $callbackUrl,
            'status_note' => 'Jovi-Pay STK Push requested',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $payment->save();

        $tx = ORM::for_table(self::TX_TABLE)->create();
        if (class_exists('Tenant')) {
            Tenant::stamp($tx, $tenantId, self::TX_TABLE);
        }
        $tx->payment_gateway_id = (int) $payment->id();
        $tx->account_reference = $reference;
        $tx->prefix = $settings['account_prefix'];
        $tx->router_id = (int) $router['id'];
        $tx->package_id = (int) $plan['id'];
        $tx->customer_id = (int) $customer['id'];
        $tx->username = $customer['username'];
        $tx->mac_address = self::clean($mac, 64);
        $tx->ip_address = self::clean($ip, 64);
        $tx->phone = $formattedPhone;
        $tx->amount = $amount;
        $tx->status = 'pending';
        $tx->activation_status = 'pending';
        $tx->raw_payload = json_encode(['created_by' => 'FASTNETPAY captive portal']);
        $tx->created_at = date('Y-m-d H:i:s');
        $tx->updated_at = date('Y-m-d H:i:s');
        $tx->save();

        $response = self::sendStk($settings, $reference, $formattedPhone, $amount, $callbackUrl, [
            'router_id' => (int) $router['id'],
            'package_id' => (int) $plan['id'],
            'customer_id' => (int) $customer['id'],
            'payment_id' => (int) $payment->id(),
            'mac_address' => self::clean($mac, 64),
            'ip_address' => self::clean($ip, 64),
        ]);

        $state = json_decode($payment['pg_request'], true) ?: [];
        $state['stk_provider'] = $response['provider'] ?? 'jovipay';
        $state['stk_uncertain'] = !empty($response['uncertain']);
        $state['stk_endpoint'] = $response['endpoint'] ?? '';
        $state['jovipay_response'] = self::safePayload($response['data'] ?? []);
        $state['updated_at'] = date('Y-m-d H:i:s');
        if (!$response['ok']) {
            $state['status_note'] = $response['message'];
            $payment->status = 3;
            $payment->pg_request = json_encode($state);
            $payment->save();
            $tx->status = 'failed';
            $tx->activation_status = 'failed';
            $tx->raw_payload = json_encode($state['jovipay_response']);
            $tx->updated_at = date('Y-m-d H:i:s');
            $tx->save();
            throw new Exception($response['message']);
        }

        $ids = self::extractRequestIds($response['data']);
        $state['merchant_request_id'] = $ids['merchant_request_id'];
        $state['checkout_request_id'] = $ids['checkout_request_id'];
        $state['status_note'] = !empty($response['uncertain'])
            ? 'STK request was sent but the upstream response timed out. Keep checking your phone, or retry if no prompt appears.'
            : 'Check your phone and enter your M-Pesa PIN.';
        $payment->gateway_trx_id = $ids['checkout_request_id'];
        $payment->pg_request = json_encode($state);
        $payment->save();
        $tx->checkout_request_id = $ids['checkout_request_id'];
        $tx->merchant_request_id = $ids['merchant_request_id'];
        $tx->raw_payload = json_encode($state['jovipay_response']);
        $tx->updated_at = date('Y-m-d H:i:s');
        $tx->save();

        return [
            'ok' => true,
            'status' => 'pending',
            'message' => $state['status_note'],
            'payment_id' => (int) $payment->id(),
            'payment_token' => $paymentToken,
            'account_reference' => $reference,
            'polling_interval' => (int) $settings['polling_interval_seconds'],
            'provider' => $response['provider'] ?? 'jovipay',
        ];
    }

    public static function handleCallback()
    {
        self::installSchema();
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            self::callbackFailed('Invalid JSON payload.', 400);
        }

        $tenantId = self::tenantIdFromPayload($payload);
        $settings = self::settings($tenantId);
        if (!self::validateIpAllowlist($settings)) {
            self::callbackFailed('Callback source is not allowed.', 403);
        }
        if (!self::validateSignature($raw, $settings)) {
            self::callbackFailed('Invalid signature', 401);
        }
        if (!self::validateMiniApp($payload, $settings)) {
            self::callbackFailed('Invalid Jovi-Pay mini-app id.', 401);
        }

        $normalized = self::normalizeCallback($payload);
        if ($normalized['account_reference'] === '') {
            $matched = self::findTransaction($normalized, $tenantId);
            if ($matched) {
                $normalized['account_reference'] = (string) $matched['account_reference'];
            } else {
                self::callbackFailed('Missing account reference.', 422);
            }
        }

        $prefix = $settings['account_prefix'] ?: 'WIFI_';
        if (!self::referenceMatchesPrefix($normalized['account_reference'], $prefix)) {
            $matched = self::findTransaction($normalized, $tenantId);
            if ($matched) {
                $normalized['account_reference'] = (string) $matched['account_reference'];
            } else {
                self::storeIgnored($normalized, $payload, $prefix, $tenantId);
                self::callbackFailed('Account prefix is not registered for FASTNETPAY.', 200, [
                    'reference' => $normalized['account_reference'],
                    'fastnetpay_status' => 'ignored',
                ]);
            }
        }

        $tx = self::findTransaction($normalized, $tenantId);
        if (!$tx) {
            $direct = self::settleDirectMpesaGateway($normalized, $payload, $tenantId);
            if ($direct) {
                self::callbackSuccess($direct['message'], $normalized['account_reference'], [
                    'fastnetpay_status' => $direct['status'],
                    'receipt' => $normalized['receipt'],
                ]);
            }
            $tx = self::createUnmatchedTransaction($normalized, $payload, $prefix, $tenantId);
        }

        if ($tx['status'] === 'success' && $tx['activation_status'] === 'activated') {
            self::callbackSuccess('Duplicate callback accepted.', $normalized['account_reference'], [
                'fastnetpay_status' => 'already_processed',
            ]);
        }

        self::updateTransactionFromCallback($tx, $normalized, $payload);

        if ($normalized['paid']) {
            $fresh = ORM::for_table(self::TX_TABLE)->find_one($tx['id']);
            $activation = self::activateTransaction($fresh, $normalized);
            if (in_array(($activation['status'] ?? ''), ['success', 'already_processed'], true)) {
                self::callbackSuccess($activation['message'] ?? 'Payment settled successfully', $normalized['account_reference'], [
                    'fastnetpay_status' => $activation['status'] ?? 'success',
                    'receipt' => $normalized['receipt'],
                ]);
            }
            self::callbackFailed($activation['message'] ?? 'Payment received, but package activation failed.', 200, [
                'reference' => $normalized['account_reference'],
                'fastnetpay_status' => $activation['status'] ?? 'failed',
            ]);
        }

        self::markFailed($tx, $normalized);
        self::callbackSuccess('Payment failure callback acknowledged.', $normalized['account_reference'], [
            'fastnetpay_status' => 'failed',
        ]);
    }

    public static function reconnect($router, $code, $phone = '', $mac = '', $ip = '')
    {
        self::installSchema();
        $code = strtoupper(self::clean($code, 80));
        if ($code === '') {
            throw new Exception('Enter your M-Pesa transaction code.');
        }

        $row = ORM::for_table(self::TX_TABLE)
            ->where('mpesa_receipt_number', $code)
            ->where('status', 'success')
            ->find_one();

        if (!$row) {
            self::logReconnect($router, $code, $phone, $mac, '', 'not_found', 'Transaction not found yet.');
            throw new Exception('Transaction not found yet. Wait a few seconds or contact support.');
        }

        if ((int) $row['router_id'] !== (int) $router['id']) {
            self::logReconnect($router, $code, $phone, $mac, $row['username'], 'wrong_router', 'Transaction belongs to another router.');
            throw new Exception('Payment found, but it belongs to another router/site.');
        }

        if ($row['activation_status'] !== 'activated') {
            $activation = self::activateTransaction($row, ['receipt' => $code]);
            if (($activation['status'] ?? '') !== 'success') {
                self::logReconnect($router, $code, $phone, $mac, $row['username'], 'activation_failed', $activation['message'] ?? 'Activation failed.');
                throw new Exception($activation['message'] ?? 'Payment found, but package activation failed.');
            }
            $row = ORM::for_table(self::TX_TABLE)->find_one($row['id']);
        }

        $customer = ORM::for_table('tbl_customers')->find_one((int) $row['customer_id']);
        if (!$customer) {
            self::logReconnect($router, $code, $phone, $mac, $row['username'], 'customer_missing', 'Customer record missing.');
            throw new Exception('Payment found, but the linked customer record is missing.');
        }

        $active = self::activeRecharge($row);
        if (!$active) {
            self::logReconnect($router, $code, $phone, $mac, $customer['username'], 'expired', 'Payment found but package has expired.');
            throw new Exception('Payment found but package has expired. Please buy again.');
        }

        self::connectHotspotSession($router, $customer, $ip ?: $row['ip_address'], $mac ?: $row['mac_address']);

        $row->status = 'reconnected';
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        self::logReconnect($router, $code, $phone, $mac, $customer['username'], 'success', 'Reconnected from receipt.');

        return [
            'ok' => true,
            'message' => 'Payment found. Logging you in...',
            'username' => $customer['username'],
            'password' => $customer['password'],
        ];
    }

    public static function transactions($status = 'all', $q = '', $limit = 200)
    {
        self::installSchema();
        $query = ORM::for_table(self::TX_TABLE)->order_by_desc('id');
        if (class_exists('Tenant') && Tenant::isTenantRequest() && Tenant::hasColumn(self::TX_TABLE, 'tenant_id')) {
            $query->where('tenant_id', Tenant::currentId());
        }
        if ($status !== 'all' && $status !== '') {
            if ($status === 'unmatched') {
                $query->where_null('payment_gateway_id');
            } else {
                $query->where('status', $status);
            }
        }
        $q = trim((string) $q);
        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where_raw('(account_reference LIKE ? OR phone LIKE ? OR mpesa_receipt_number LIKE ? OR mac_address LIKE ? OR username LIKE ?)', [$like, $like, $like, $like, $like]);
        }
        return $query->limit((int) $limit)->find_many();
    }

    public static function summary()
    {
        self::installSchema();
        $scope = function ($query) {
            if (class_exists('Tenant') && Tenant::isTenantRequest() && Tenant::hasColumn(self::TX_TABLE, 'tenant_id')) {
                $query->where('tenant_id', Tenant::currentId());
            }
            return $query;
        };
        return [
            'pending' => (int) $scope(ORM::for_table(self::TX_TABLE)->where('status', 'pending'))->count(),
            'success' => (int) $scope(ORM::for_table(self::TX_TABLE)->where('status', 'success'))->count(),
            'failed' => (int) $scope(ORM::for_table(self::TX_TABLE)->where('status', 'failed'))->count(),
            'reconnected' => (int) $scope(ORM::for_table(self::TX_TABLE)->where('status', 'reconnected'))->count(),
            'unmatched' => (int) $scope(ORM::for_table(self::TX_TABLE)->where_null('payment_gateway_id'))->count(),
        ];
    }

    private static function overlayTenantGatewaySettings($settings, $tenantId)
    {
        if (!class_exists('SaasBilling') || !method_exists('SaasBilling', 'tenantGatewayPublicSettings')) {
            return $settings;
        }
        try {
            $gateway = SaasBilling::tenantGatewayPublicSettings((int) $tenantId);
            if (!$gateway) {
                return $settings;
            }
            foreach (['enabled', 'account_prefix', 'callback_url', 'callback_secret', 'api_base_url', 'stk_endpoint', 'api_token', 'mini_app_id', 'gateway_label', 'support_phone'] as $key) {
                if (array_key_exists($key, $gateway) && (string) $gateway[$key] !== '') {
                    $settings[$key] = (string) $gateway[$key];
                }
            }
        } catch (Throwable $e) {
            _log('FASTNETPAY tenant gateway overlay skipped: ' . $e->getMessage(), 'Payment Gateway', 0);
        }
        return $settings;
    }

    private static function sendStk($settings, $reference, $phone, $amount, $callbackUrl, $metadata)
    {
        $endpoint = self::endpointUrl($settings['api_base_url'], $settings['stk_endpoint']);
        $token = trim($settings['api_token']);

        if ($endpoint === '' || $token === '') {
            $direct = self::sendDirectMpesaStk($reference, $phone, $amount, $callbackUrl, $metadata);
            if ($direct['ok'] || $direct['configured']) {
                return $direct;
            }

            return [
                'ok' => false,
                'provider' => 'jovipay',
                'endpoint' => self::safeEndpoint($endpoint),
                'message' => $endpoint === ''
                    ? 'Jovi-Pay STK endpoint is not configured.'
                    : 'Jovi-Pay API token is not configured, and direct MPESA STK credentials are not configured.',
                'data' => [],
            ];
        }

        $payload = [
            'phone' => $phone,
            'phone_number' => $phone,
            'msisdn' => $phone,
            'amount' => $amount,
            'trans_amount' => $amount,
            'account_reference' => $reference,
            'reference' => $reference,
            'transaction_reference' => $reference,
            'external_reference' => $reference,
            'bill_ref_number' => $reference,
            'BillRefNumber' => $reference,
            'AccountReference' => $reference,
            'callback_url' => $callbackUrl,
            'confirmation_url' => $callbackUrl,
            'description' => 'FASTNETPAY internet package payment',
            'transaction_desc' => 'FASTNETPAY internet package payment',
            'metadata' => $metadata,
        ];
        if (!empty($settings['mini_app_id'])) {
            $payload['mini_app_id'] = $settings['mini_app_id'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'X-Idempotency-Key: ' . $reference,
                'X-Account-Reference: ' . $reference,
                'X-Callback-URL: ' . $callbackUrl,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $raw = curl_exec($curl);
        $error = curl_error($curl);
        $errno = (int) curl_errno($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($raw === false) {
            if (in_array($errno, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT], true) || stripos($error, 'timed out') !== false) {
                return [
                    'ok' => true,
                    'uncertain' => true,
                    'provider' => 'jovipay',
                    'endpoint' => self::safeEndpoint($endpoint),
                    'message' => 'Jovi-Pay STK request timed out before a response. FASTNETPAY kept the payment pending for callback reconciliation.',
                    'data' => [
                        'status' => 'pending',
                        'network_warning' => self::safeError($error),
                        'account_reference' => $reference,
                    ],
                ];
            }

            return [
                'ok' => false,
                'provider' => 'jovipay',
                'endpoint' => self::safeEndpoint($endpoint),
                'message' => 'Jovi-Pay network error: ' . self::safeError($error),
                'data' => [],
            ];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [
                'ok' => false,
                'provider' => 'jovipay',
                'endpoint' => self::safeEndpoint($endpoint),
                'message' => 'Jovi-Pay returned invalid JSON. HTTP ' . $httpCode,
                'data' => ['http_code' => $httpCode],
            ];
        }

        $status = strtolower((string) ($data['status'] ?? $data['result'] ?? ''));
        $ok = $httpCode >= 200 && $httpCode < 300 && !in_array($status, ['error', 'failed', 'failure'], true);
        if (!$ok && in_array($httpCode, [404, 405], true)) {
            $direct = self::sendDirectMpesaStk($reference, $phone, $amount, $callbackUrl, $metadata);
            if ($direct['ok'] || $direct['configured']) {
                $direct['jovipay_status'] = $httpCode;
                $direct['jovipay_message'] = $data['message'] ?? 'Jovi-Pay STK endpoint was not available.';
                return $direct;
            }
            return [
                'ok' => false,
                'provider' => 'jovipay',
                'endpoint' => self::safeEndpoint($endpoint),
                'message' => 'Jovi-Pay STK endpoint is not available (' . $httpCode . '), and direct MPESA STK credentials are not configured.',
                'data' => $data,
            ];
        }

        return [
            'ok' => $ok,
            'provider' => 'jovipay',
            'endpoint' => self::safeEndpoint($endpoint),
            'message' => $ok ? 'Jovi-Pay STK request accepted.' : self::safeError($data['message'] ?? ('Jovi-Pay request failed. HTTP ' . $httpCode)),
            'data' => $data,
        ];
    }

    private static function sendDirectMpesaStk($reference, $phone, $amount, $callbackUrl, $metadata)
    {
        $cfg = self::directMpesaSettings();
        if (!$cfg['configured']) {
            return [
                'ok' => false,
                'configured' => false,
                'provider' => 'direct_mpesa',
                'message' => 'Direct MPESA STK credentials are not configured.',
                'data' => [],
            ];
        }

        $token = self::directMpesaAccessToken($cfg);
        if (!$token['ok']) {
            return [
                'ok' => false,
                'configured' => true,
                'provider' => 'direct_mpesa',
                'message' => $token['message'],
                'data' => self::safePayload($token['data'] ?? []),
            ];
        }

        $timestamp = date('YmdHis');
        $transactionType = $cfg['shortcode_type'] === 'till' ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline';
        $payload = [
            'BusinessShortCode' => $cfg['shortcode'],
            'Password' => base64_encode($cfg['shortcode'] . $cfg['passkey'] . $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => $transactionType,
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $cfg['shortcode'],
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $reference,
            'TransactionDesc' => 'FASTNETPAY internet package payment',
        ];

        $url = $cfg['api_base'] . '/mpesa/stkpush/v1/processrequest';
        $response = self::httpJson(
            $url,
            'POST',
            [
                'Authorization: Bearer ' . $token['access_token'],
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            $payload
        );

        if (!$response['ok']) {
            return [
                'ok' => false,
                'configured' => true,
                'provider' => 'direct_mpesa',
                'endpoint' => self::safeEndpoint($url),
                'message' => $response['message'] ?: 'M-Pesa rejected the STK Push request.',
                'data' => self::safePayload($response['data'] ?? []),
            ];
        }

        $data = $response['data'];
        $responseCode = isset($data['ResponseCode']) ? (string) $data['ResponseCode'] : '';
        if ($responseCode !== '0') {
            return [
                'ok' => false,
                'configured' => true,
                'provider' => 'direct_mpesa',
                'endpoint' => self::safeEndpoint($url),
                'message' => self::safeError($data['ResponseDescription'] ?? $data['errorMessage'] ?? 'M-Pesa rejected the STK Push request.'),
                'data' => self::safePayload($data),
            ];
        }

        return [
            'ok' => true,
            'configured' => true,
            'provider' => 'direct_mpesa',
            'endpoint' => self::safeEndpoint($url),
            'message' => 'M-Pesa STK Push request sent directly with the FASTNETPAY WIFI reference.',
            'data' => self::safePayload($data + [
                'account_reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ]),
        ];
    }

    private static function directMpesaAccessToken($cfg)
    {
        $response = self::httpJson(
            $cfg['api_base'] . '/oauth/v1/generate?grant_type=client_credentials',
            'GET',
            ['Authorization: Basic ' . base64_encode($cfg['consumer_key'] . ':' . $cfg['consumer_secret'])]
        );
        if (!$response['ok'] || empty($response['data']['access_token'])) {
            return [
                'ok' => false,
                'message' => 'Unable to authenticate with M-Pesa Daraja. Check the MPESA STK Push gateway credentials.',
                'data' => self::safePayload($response['data'] ?? []),
            ];
        }
        return [
            'ok' => true,
            'access_token' => (string) $response['data']['access_token'],
        ];
    }

    private static function directMpesaSettings()
    {
        $shortcode = self::appConfig('mpesastkpush_shortcode');
        $consumerKey = self::appConfig('mpesastkpush_consumer_key');
        $consumerSecret = self::appConfig('mpesastkpush_consumer_secret');
        $passkey = self::appConfig('mpesastkpush_passkey');
        $environment = self::appConfig('mpesastkpush_environment') ?: 'sandbox';
        $shortcodeType = self::appConfig('mpesastkpush_shortcode_type') ?: 'paybill';

        if ($shortcode === '' || $consumerKey === '' || $consumerSecret === '' || $passkey === '') {
            return ['configured' => false];
        }

        return [
            'configured' => true,
            'environment' => $environment === 'live' ? 'live' : 'sandbox',
            'api_base' => $environment === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke',
            'shortcode_type' => $shortcodeType === 'till' ? 'till' : 'paybill',
            'shortcode' => $shortcode,
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
            'passkey' => $passkey,
        ];
    }

    private static function appConfig($key)
    {
        $value = self::readAppConfig($key);
        if ($value !== '') {
            return $value;
        }
        if (strpos($key, 'mpesastkpush_') === 0) {
            return self::readAppConfig('mpesa_stk_push_' . substr($key, strlen('mpesastkpush_')));
        }
        return '';
    }

    private static function readAppConfig($key)
    {
        global $config;
        if (isset($config[$key]) && trim((string) $config[$key]) !== '') {
            return trim((string) $config[$key]);
        }
        try {
            $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
            return $row ? trim((string) $row['value']) : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    private static function httpJson($url, $method = 'GET', $headers = [], $payload = null)
    {
        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }
        curl_setopt_array($curl, $options);
        $raw = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($raw === false) {
            return ['ok' => false, 'message' => self::safeError($error), 'data' => []];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Invalid JSON response. HTTP ' . $httpCode, 'data' => ['http_code' => $httpCode]];
        }
        return [
            'ok' => $httpCode >= 200 && $httpCode < 300,
            'message' => self::safeError($data['errorMessage'] ?? $data['ResponseDescription'] ?? $data['message'] ?? ''),
            'data' => $data,
        ];
    }

    private static function activateTransaction($tx, $normalized = [])
    {
        global $trx;
        if (!$tx) {
            return ['status' => 'failed', 'message' => 'Transaction not found.'];
        }
        if ($tx['activation_status'] === 'activated') {
            return ['status' => 'already_processed', 'message' => 'Payment was already activated.'];
        }

        $plan = ORM::for_table('tbl_plans')->find_one((int) $tx['package_id']);
        $router = ORM::for_table('tbl_routers')->find_one((int) $tx['router_id']);
        $customer = ORM::for_table('tbl_customers')->find_one((int) $tx['customer_id']);
        if (!$plan || !$router || !$customer) {
            self::setActivationFailed($tx, 'Missing plan, router, or customer record.');
            return ['status' => 'failed', 'message' => 'Missing plan, router, or customer record.'];
        }
        if (class_exists('Tenant') && Tenant::hasColumn(self::TX_TABLE, 'tenant_id')) {
            $tenantId = (int) ($tx['tenant_id'] ?? 0);
            foreach (['plan' => $plan, 'router' => $router, 'customer' => $customer] as $label => $row) {
                if ($tenantId > 0 && isset($row['tenant_id']) && (int) $row['tenant_id'] !== $tenantId) {
                    self::setActivationFailed($tx, 'Cross-tenant ' . $label . ' access blocked.');
                    Tenant::audit('tenant.payment_cross_access_blocked', 'Jovi-Pay activation blocked due to tenant mismatch.', $label, (string) $row['id'], $tenantId);
                    return ['status' => 'failed', 'message' => 'Payment could not be activated for this tenant.'];
                }
            }
        }

        $expected = self::amount($plan['price']);
        $paid = self::amount($normalized['amount'] ?? $tx['amount']);
        if ($paid !== $expected) {
            self::setActivationFailed($tx, 'Paid amount does not match package price.');
            return ['status' => 'failed', 'message' => 'Paid amount does not match package price.'];
        }

        $payment = $tx['payment_gateway_id'] ? ORM::for_table('tbl_payment_gateway')->find_one((int) $tx['payment_gateway_id']) : null;
        if ($payment && (int) $payment['status'] === 2) {
            $tx->activation_status = 'activated';
            $tx->updated_at = date('Y-m-d H:i:s');
            $tx->save();
            self::connectHotspotSession($router, $customer, $tx['ip_address'], $tx['mac_address']);
            return ['status' => 'already_processed', 'message' => 'Payment was already activated.'];
        }

        $trx = $payment ?: $tx;
        $invoice = Package::rechargeUser((int) $customer['id'], $router['name'], (int) $plan['id'], 'jovipay', 'Jovi-Pay M-Pesa STK Push', 'Jovi-Pay receipt ' . ($normalized['receipt'] ?? $tx['mpesa_receipt_number']));
        if (!$invoice) {
            self::setActivationFailed($tx, 'Package activation failed.');
            return ['status' => 'failed', 'message' => 'Payment received, but package activation failed.'];
        }

        if ($payment) {
            $payment->pg_paid_response = $tx['raw_payload'];
            $payment->payment_method = 'M-Pesa';
            $payment->payment_channel = 'Jovi-Pay STK Push';
            $payment->paid_date = date('Y-m-d H:i:s');
            $payment->trx_invoice = $invoice;
            $payment->status = 2;
            if (!empty($tx['checkout_request_id'])) {
                $payment->gateway_trx_id = $tx['checkout_request_id'];
            }
            $payment->save();
        }

        $tx->status = 'success';
        $tx->activation_status = 'activated';
        $tx->updated_at = date('Y-m-d H:i:s');
        $tx->save();

        self::connectHotspotSession($router, $customer, $tx['ip_address'], $tx['mac_address']);

        return ['status' => 'success', 'message' => 'Payment confirmed and internet package activated.'];
    }

    private static function updateTransactionFromCallback($tx, $normalized, $payload)
    {
        $tx->phone = $normalized['phone'] ?: $tx['phone'];
        $tx->amount = $normalized['amount'] ?: $tx['amount'];
        $tx->mpesa_receipt_number = $normalized['receipt'] ?: $tx['mpesa_receipt_number'];
        $tx->checkout_request_id = $normalized['checkout_request_id'] ?: $tx['checkout_request_id'];
        $tx->merchant_request_id = $normalized['merchant_request_id'] ?: $tx['merchant_request_id'];
        $tx->transaction_date = $normalized['transaction_date'] ?: $tx['transaction_date'];
        $tx->raw_payload = json_encode(self::safePayload($payload));
        $tx->status = $normalized['paid'] ? 'success' : 'failed';
        $tx->callback_received_at = date('Y-m-d H:i:s');
        $tx->updated_at = date('Y-m-d H:i:s');
        $tx->save();

        if ($tx['payment_gateway_id']) {
            $payment = ORM::for_table('tbl_payment_gateway')->find_one((int) $tx['payment_gateway_id']);
            if ($payment) {
                $state = json_decode($payment['pg_request'], true) ?: [];
                $state['jovipay_callback_received_at'] = date('Y-m-d H:i:s');
                $state['status_note'] = $normalized['paid'] ? 'Jovi-Pay callback received.' : 'Jovi-Pay payment failed.';
                $payment->pg_request = json_encode($state);
                $payment->pg_paid_response = json_encode(self::safePayload($payload));
                if (!$normalized['paid']) {
                    $payment->status = 3;
                }
                $payment->save();
            }
        }
    }

    private static function markFailed($tx, $normalized)
    {
        $tx->status = 'failed';
        $tx->activation_status = 'failed';
        $tx->updated_at = date('Y-m-d H:i:s');
        $tx->save();
        if ($tx['payment_gateway_id']) {
            $payment = ORM::for_table('tbl_payment_gateway')->find_one((int) $tx['payment_gateway_id']);
            if ($payment) {
                $payment->status = 3;
                $payment->save();
            }
        }
    }

    private static function setActivationFailed($tx, $message)
    {
        $tx->activation_status = 'failed';
        $tx->updated_at = date('Y-m-d H:i:s');
        $tx->save();
        _log('Jovi-Pay activation failed for ' . $tx['account_reference'] . ': ' . $message, 'Payment Gateway', 0);
    }

    private static function tenantIdFromPayload($payload)
    {
        if (!class_exists('Tenant')) {
            return 0;
        }
        $ref = '';
        if (isset($payload['transaction']['bill_ref_number'])) {
            $ref = (string) $payload['transaction']['bill_ref_number'];
        } elseif (isset($payload['raw_payload']['BillRefNumber'])) {
            $ref = (string) $payload['raw_payload']['BillRefNumber'];
        } elseif (isset($payload['BillRefNumber'])) {
            $ref = (string) $payload['BillRefNumber'];
        } elseif (isset($payload['AccountReference'])) {
            $ref = (string) $payload['AccountReference'];
        }
        if ($ref !== '') {
            $tenant = Tenant::tenantFromPrefix($ref);
            if ($tenant) {
                return (int) $tenant['id'];
            }
        }
        return Tenant::currentId();
    }

    private static function findTransaction($normalized, $tenantId = 0)
    {
        if (!empty($normalized['account_reference'])) {
            $query = ORM::for_table(self::TX_TABLE)->where('account_reference', $normalized['account_reference']);
            if (class_exists('Tenant') && Tenant::hasColumn(self::TX_TABLE, 'tenant_id') && $tenantId > 0) {
                $query->where('tenant_id', (int) $tenantId);
            }
            $tx = $query->find_one();
            if ($tx) {
                return $tx;
            }
        }

        foreach (['checkout_request_id', 'merchant_request_id', 'receipt'] as $field) {
            if (!empty($normalized[$field])) {
                $column = $field === 'receipt' ? 'mpesa_receipt_number' : $field;
                $query = ORM::for_table(self::TX_TABLE)->where($column, $normalized[$field]);
                if (class_exists('Tenant') && Tenant::hasColumn(self::TX_TABLE, 'tenant_id') && $tenantId > 0) {
                    $query->where('tenant_id', (int) $tenantId);
                }
                $tx = $query->find_one();
                if ($tx) {
                    return $tx;
                }
            }
        }

        $fallback = self::findRecentPendingTransaction($normalized, $tenantId);
        if ($fallback) {
            return $fallback;
        }

        return null;
    }

    private static function findRecentPendingTransaction($normalized, $tenantId = 0)
    {
        $settings = self::settings($tenantId);
        $prefix = self::cleanPrefix($settings['account_prefix'] ?? 'WIFI_');
        $reference = self::cleanReference($normalized['account_reference'] ?? '');
        $referenceCompact = preg_replace('/[^A-Z0-9]/', '', $reference);
        $prefixCompact = preg_replace('/[^A-Z0-9]/', '', $prefix);

        if ($reference !== '' && $prefixCompact !== '' && strpos($referenceCompact, $prefixCompact) !== 0) {
            return null;
        }

        $routerId = self::routerIdFromReference($reference, $prefix);
        $query = ORM::for_table(self::TX_TABLE)
            ->where_in('status', ['pending', 'success'])
            ->where_raw("created_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)")
            ->order_by_desc('id');

        if (class_exists('Tenant') && Tenant::hasColumn(self::TX_TABLE, 'tenant_id') && $tenantId > 0) {
            $query->where('tenant_id', (int) $tenantId);
        }
        if (!empty($normalized['phone'])) {
            $query->where('phone', $normalized['phone']);
        }
        if (!empty($normalized['amount'])) {
            $query->where('amount', self::amount($normalized['amount']));
        }
        if ($routerId > 0) {
            $query->where('router_id', $routerId);
        }

        return $query->find_one();
    }

    private static function settleDirectMpesaGateway($normalized, $payload, $tenantId = 0)
    {
        if (empty($normalized['paid'])) {
            return null;
        }

        $payment = self::findDirectMpesaPayment($normalized, $tenantId);
        if (!$payment) {
            return null;
        }

        global $PAYMENTGATEWAY_PATH;
        $gatewayPath = $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'mpesastkpush.php';
        if (!file_exists($gatewayPath)) {
            return null;
        }
        require_once $gatewayPath;

        $safeResponse = [
            'ResultCode' => 0,
            'ResultDesc' => 'Jovi-Pay C2B confirmation received.',
            'MpesaReceiptNumber' => $normalized['receipt'],
            'Amount' => $normalized['amount'],
            'PhoneNumber' => $normalized['phone'],
            'TransactionDate' => $normalized['transaction_date'],
            'AccountReference' => $normalized['account_reference'],
        ];

        if ((int) $payment['status'] !== 2) {
            $invoice = mpesastkpush_activate_transaction($payment);
            if (!$invoice) {
                $state = json_decode($payment['pg_request'], true) ?: [];
                $state['status_note'] = 'Jovi-Pay C2B payment received, package activation pending';
                $state['jovipay_callback_received_at'] = date('Y-m-d H:i:s');
                $payment->pg_request = json_encode($state);
                $payment->pg_paid_response = json_encode(self::safePayload($payload));
                $payment->save();
                _log('Jovi-Pay C2B received but direct M-Pesa activation failed for transaction #' . $payment['id'], 'Payment Gateway', 0);
                return ['status' => 'activation_pending', 'message' => 'Payment received, but package activation is pending.'];
            }

            $payment->payment_method = 'M-Pesa';
            $payment->payment_channel = 'Jovi-Pay C2B Forward';
            $payment->paid_date = date('Y-m-d H:i:s');
            $payment->trx_invoice = $invoice;
            $payment->status = 2;
        }

        $state = json_decode($payment['pg_request'], true) ?: [];
        $state['status_note'] = 'Jovi-Pay C2B payment confirmed and package activated';
        $state['jovipay_callback_received_at'] = date('Y-m-d H:i:s');
        $state['jovipay_account_reference'] = $normalized['account_reference'];
        $state['jovipay_receipt'] = $normalized['receipt'];
        $payment->pg_request = json_encode($state);
        $payment->pg_paid_response = json_encode($safeResponse);
        $payment->save();

        $customer = ORM::for_table('tbl_customers')->find_one((int) $payment['user_id']);
        $loginOk = mpesastkpush_connect_hotspot_session($payment, $customer);
        $state['hotspot_login_status'] = $loginOk ? 'connected' : 'pending';
        $state['hotspot_login_attempted_at'] = date('Y-m-d H:i:s');
        $payment->pg_request = json_encode($state);
        $payment->save();

        return ['status' => 'direct_mpesastkpush_settled', 'message' => 'Payment settled and package activated.'];
    }

    private static function findDirectMpesaPayment($normalized, $tenantId = 0)
    {
        $reference = self::cleanReference($normalized['account_reference'] ?? '');
        $paymentId = self::directPaymentIdFromReference($reference);
        if ($paymentId > 0) {
            $query = ORM::for_table('tbl_payment_gateway')
                ->where('id', $paymentId)
                ->where('gateway', 'mpesastkpush')
                ->where_in('status', [1, 2]);
            if (class_exists('Tenant') && Tenant::hasColumn('tbl_payment_gateway', 'tenant_id') && $tenantId > 0) {
                $query->where('tenant_id', (int) $tenantId);
            }
            $payment = $query->find_one();
            if ($payment) {
                return $payment;
            }
        }

        $query = ORM::for_table('tbl_payment_gateway')
            ->where('gateway', 'mpesastkpush')
            ->where_in('status', [1, 2])
            ->where_raw("created_date >= DATE_SUB(NOW(), INTERVAL 6 HOUR)")
            ->order_by_desc('id');
        if (!empty($normalized['amount'])) {
            $query->where('price', self::amount($normalized['amount']));
        }
        if (!empty($normalized['phone'])) {
            $query->where_like('pg_request', '%"phone":"' . $normalized['phone'] . '"%');
        }
        if (class_exists('Tenant') && Tenant::hasColumn('tbl_payment_gateway', 'tenant_id') && $tenantId > 0) {
            $query->where('tenant_id', (int) $tenantId);
        }

        return $query->find_one();
    }

    private static function directPaymentIdFromReference($reference)
    {
        $reference = self::cleanReference($reference);
        if ($reference === '') {
            return 0;
        }
        $prefixes = ['FASTNETPAY', 'WIFI', 'FNP'];
        try {
            $configured = ORM::for_table('tbl_appconfig')->where('setting', 'mpesastkpush_account_prefix')->find_one();
            if ($configured && trim((string) $configured['value']) !== '') {
                array_unshift($prefixes, strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $configured['value'])));
            }
        } catch (Throwable $ignored) {
        }
        foreach (array_unique(array_filter($prefixes)) as $prefix) {
            if (strpos($reference, $prefix) === 0) {
                $tail = substr($reference, strlen($prefix));
                return ctype_digit($tail) ? (int) $tail : 0;
            }
        }
        return 0;
    }

    private static function createUnmatchedTransaction($normalized, $payload, $prefix, $tenantId = 0)
    {
        $tx = ORM::for_table(self::TX_TABLE)->create();
        if (class_exists('Tenant')) {
            Tenant::stamp($tx, $tenantId ?: Tenant::currentId(), self::TX_TABLE);
        }
        $tx->account_reference = $normalized['account_reference'];
        $tx->prefix = $prefix;
        $tx->phone = $normalized['phone'];
        $tx->amount = $normalized['amount'];
        $tx->mpesa_receipt_number = $normalized['receipt'] ?: null;
        $tx->checkout_request_id = $normalized['checkout_request_id'];
        $tx->merchant_request_id = $normalized['merchant_request_id'];
        $tx->transaction_date = $normalized['transaction_date'];
        $tx->raw_payload = json_encode(self::safePayload($payload));
        $tx->status = $normalized['paid'] ? 'success' : 'failed';
        $tx->activation_status = 'failed';
        $tx->callback_received_at = date('Y-m-d H:i:s');
        $tx->created_at = date('Y-m-d H:i:s');
        $tx->updated_at = date('Y-m-d H:i:s');
        $tx->save();
        return $tx;
    }

    private static function storeIgnored($normalized, $payload, $prefix, $tenantId = 0)
    {
        try {
            $ref = $normalized['account_reference'];
            if ($ref === '') {
                return;
            }
            $query = ORM::for_table(self::TX_TABLE)->where('account_reference', $ref);
            if (class_exists('Tenant') && Tenant::hasColumn(self::TX_TABLE, 'tenant_id') && $tenantId > 0) {
                $query->where('tenant_id', (int) $tenantId);
            }
            $exists = $query->find_one();
            if ($exists) {
                return;
            }
            $tx = ORM::for_table(self::TX_TABLE)->create();
            if (class_exists('Tenant')) {
                Tenant::stamp($tx, $tenantId ?: Tenant::currentId(), self::TX_TABLE);
            }
            $tx->account_reference = $ref;
            $tx->prefix = $prefix;
            $tx->phone = $normalized['phone'];
            $tx->amount = $normalized['amount'];
            $tx->mpesa_receipt_number = $normalized['receipt'] ?: null;
            $tx->raw_payload = json_encode(self::safePayload($payload));
            $tx->status = 'ignored';
            $tx->activation_status = 'failed';
            $tx->callback_received_at = date('Y-m-d H:i:s');
            $tx->created_at = date('Y-m-d H:i:s');
            $tx->updated_at = date('Y-m-d H:i:s');
            $tx->save();
        } catch (Throwable $e) {
            _log('Jovi-Pay ignored callback could not be stored: ' . $e->getMessage(), 'Payment Gateway', 0);
        }
    }

    private static function activeRecharge($tx)
    {
        if (empty($tx['customer_id']) || empty($tx['router_id'])) {
            return null;
        }
        $router = ORM::for_table('tbl_routers')->find_one((int) $tx['router_id']);
        if (!$router) {
            return null;
        }
        return ORM::for_table('tbl_user_recharges')
            ->where('customer_id', (int) $tx['customer_id'])
            ->where('routers', $router['name'])
            ->where('status', 'on')
            ->where_raw("UNIX_TIMESTAMP(CONCAT(`expiration`,' ',`time`)) >= UNIX_TIMESTAMP(NOW())")
            ->find_one();
    }

    private static function hotspotCustomer($router, $phone, $mac, $ip)
    {
        $tenantId = class_exists('Tenant') ? (int) ($router['tenant_id'] ?: Tenant::currentId()) : 0;
        $macKey = self::macKey($mac);
        $seed = $router['id'] . '|' . ($macKey ?: $mac) . '|' . $phone;
        $username = self::hotspotUsername($router, $mac, $ip);
        $customer = self::findHotspotCustomerByDevice($router, $mac, $phone, $ip);
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
        self::normalizeHotspotCustomerIdentity($customer, $router, $username, $mac, $phone);
        $customer->phonenumber = $phone;
        if ($macKey !== '') {
            $customer->address = 'MikroTik hotspot ' . $router['name'] . ' device ' . $mac;
        }
        $customer->last_login = date('Y-m-d H:i:s');
        $customer->save();
        return $customer;
    }

    private static function hotspotUsername($router, $mac, $ip = '')
    {
        $macKey = self::macKey($mac);
        if ($macKey !== '') {
            return $macKey;
        }
        return 'HS' . strtoupper(substr(sha1((int) $router['id'] . '|' . $ip), 0, 14));
    }

    private static function macDisplay($mac)
    {
        $macKey = self::macKey($mac);
        if ($macKey === '') {
            return self::clean($mac, 64);
        }
        return implode(':', str_split($macKey, 2));
    }

    private static function findHotspotCustomerByDevice($router, $mac, $phone = '', $ip = '')
    {
        $tenantId = class_exists('Tenant') ? (int) ($router['tenant_id'] ?: Tenant::currentId()) : 0;
        $username = self::hotspotUsername($router, $mac, $ip);
        $macKey = self::macKey($mac);
        $formattedMac = self::macDisplay($mac);
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

    private static function normalizeHotspotCustomerIdentity($customer, $router, $username, $mac, $phone)
    {
        $tenantId = class_exists('Tenant') ? (int) ($router['tenant_id'] ?: Tenant::currentId()) : 0;
        $formattedMac = self::macDisplay($mac);
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
                self::updateHotspotUsernameReferences($customer, $oldUsername, $username);
            }
        }

        $customer->fullname = substr($fullname, 0, 190);
        $customer->email = strtolower(preg_replace('/[^A-Za-z0-9_.-]/', '', $username)) . '@hotspot.local';
    }

    private static function updateHotspotUsernameReferences($customer, $oldUsername, $newUsername)
    {
        if ($oldUsername === '' || $oldUsername === $newUsername) {
            return;
        }
        foreach ([
            'tbl_user_recharges' => 'customer_id',
            'tbl_payment_gateway' => 'user_id',
            'tbl_transactions' => 'user_id',
            self::TX_TABLE => 'customer_id',
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

    private static function connectHotspotSession($router, $customer, $ip, $mac)
    {
        global $DEVICE_PATH;

        $ip = self::clean($ip, 64);
        $mac = self::clean($mac, 64);
        if ($ip === '' || $mac === '' || !$router || !$customer) {
            return false;
        }

        $devicePath = $DEVICE_PATH . DIRECTORY_SEPARATOR . 'MikrotikHotspot.php';
        try {
            if (!file_exists($devicePath)) {
                throw new Exception('MikrotikHotspot device adapter is missing.');
            }
            require_once $devicePath;
            (new MikrotikHotspot())->connect_customer($customer, $ip, $mac, $router['name']);
            _log('Jovi-Pay hotspot session connected for ' . $customer['username'] . ' on ' . $router['name'], 'Payment Gateway', 0);
            return true;
        } catch (Throwable $e) {
            _log('Jovi-Pay hotspot session login pending for ' . $customer['username'] . ': ' . self::safeError($e->getMessage()), 'Payment Gateway', 0);
            return false;
        }
    }

    private static function normalizeCallback($payload)
    {
        $resultCode = self::recursiveValue($payload, ['ResultCode', 'result_code', 'code']);
        $receipt = self::recursiveValue($payload, ['MpesaReceiptNumber', 'mpesa_receipt_number', 'receipt', 'transaction_code', 'TransID', 'trans_id']);
        $status = strtolower((string) self::recursiveValue($payload, ['status', 'ResultDesc', 'result_desc', 'event']));
        $paid = ((string) $resultCode === '0') || $receipt !== '' || in_array($status, ['success', 'paid', 'completed'], true);
        if ($status === 'mpesa.c2b.confirmed') {
            $paid = true;
        }
        if (in_array($status, ['failed', 'cancelled', 'canceled', 'timeout', 'error'], true) || ((string) $resultCode !== '' && (string) $resultCode !== '0')) {
            $paid = false;
        }
        return [
            'account_reference' => self::cleanReference(self::recursiveValue($payload, ['account_reference', 'AccountReference', 'BillRefNumber', 'bill_ref_number', 'reference', 'account', 'AccountNo'])),
            'phone' => self::formatPhone(self::recursiveValue($payload, ['PhoneNumber', 'phone', 'MSISDN', 'msisdn'])) ?: '',
            'amount' => self::amount(self::recursiveValue($payload, ['Amount', 'amount', 'TransAmount', 'trans_amount'])),
            'receipt' => strtoupper(self::clean((string) $receipt, 80)),
            'checkout_request_id' => self::clean(self::recursiveValue($payload, ['CheckoutRequestID', 'checkout_request_id']), 160),
            'merchant_request_id' => self::clean(self::recursiveValue($payload, ['MerchantRequestID', 'merchant_request_id']), 160),
            'transaction_date' => self::clean(self::recursiveValue($payload, ['TransactionDate', 'transaction_date', 'TransTime', 'trans_time']), 64),
            'paid' => $paid,
        ];
    }

    private static function recursiveValue($payload, $keys)
    {
        if (!is_array($payload)) {
            return '';
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && !is_array($payload[$key])) {
                return (string) $payload[$key];
            }
        }
        foreach ($payload as $value) {
            if (is_array($value)) {
                if (isset($value['Name'], $value['Value']) && in_array($value['Name'], $keys, true)) {
                    return (string) $value['Value'];
                }
                $found = self::recursiveValue($value, $keys);
                if ($found !== '') {
                    return $found;
                }
            }
        }
        return '';
    }

    private static function extractRequestIds($data)
    {
        return [
            'checkout_request_id' => self::clean(self::recursiveValue($data, ['CheckoutRequestID', 'checkout_request_id', 'checkoutRequestId']), 160),
            'merchant_request_id' => self::clean(self::recursiveValue($data, ['MerchantRequestID', 'merchant_request_id', 'merchantRequestId']), 160),
        ];
    }

    private static function validateSignature($raw, $settings)
    {
        $secret = trim($settings['callback_secret']);
        if ($secret === '') {
            return true;
        }
        $provided = self::headerValue(['X-Jovi-Signature', 'X-JoviPay-Signature', 'X-Fastnetpay-Signature', 'X-Signature']);
        $timestamp = self::headerValue(['X-Jovi-Timestamp']);
        if ($provided !== '') {
            $provided = preg_replace('/^sha256=/i', '', $provided);
            $joviExpected = $timestamp !== '' ? hash_hmac('sha256', $raw . $timestamp, $secret) : '';
            if ($joviExpected !== '' && hash_equals($joviExpected, $provided)) {
                return true;
            }

            $legacyExpected = hash_hmac('sha256', $raw, $secret);
            return hash_equals($legacyExpected, $provided);
        }
        $shared = self::headerValue(['X-Jovi-Secret', 'X-JoviPay-Secret']) ?: ($_GET['secret'] ?? '');
        return $shared !== '' && hash_equals($secret, (string) $shared);
    }

    private static function validateMiniApp($payload, $settings)
    {
        $expected = trim((string) ($settings['mini_app_id'] ?? ''));
        if ($expected === '') {
            return true;
        }
        $headerAppId = self::headerValue(['X-Jovi-App-ID']);
        $payloadAppId = '';
        if (isset($payload['mini_app']) && is_array($payload['mini_app']) && isset($payload['mini_app']['id'])) {
            $payloadAppId = (string) $payload['mini_app']['id'];
        }
        if ($payloadAppId === '') {
            $payloadAppId = self::recursiveValue($payload, ['mini_app_id', 'app_id']);
        }
        if ($headerAppId !== '' && hash_equals($expected, $headerAppId)) {
            return true;
        }
        return $payloadAppId !== '' && hash_equals($expected, $payloadAppId);
    }

    private static function headerValue($names)
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            foreach ((array) getallheaders() as $key => $value) {
                $headers[strtolower((string) $key)] = (string) $value;
            }
        }
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }
        foreach ((array) $names as $name) {
            $key = strtolower((string) $name);
            if (isset($headers[$key])) {
                return trim((string) $headers[$key]);
            }
        }
        return '';
    }

    private static function validateIpAllowlist($settings)
    {
        $lines = preg_split('/[\r\n,]+/', (string) ($settings['allowed_ips'] ?? ''));
        $ips = array_filter(array_map('trim', $lines));
        if (!$ips) {
            return true;
        }
        $remote = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
        $remote = trim(explode(',', $remote)[0]);
        return in_array($remote, $ips, true);
    }

    private static function logReconnect($router, $code, $phone, $mac, $username, $status, $message)
    {
        $row = ORM::for_table(self::RECONNECT_TABLE)->create();
        if (class_exists('Tenant')) {
            Tenant::stamp($row, (int) (($router['tenant_id'] ?? 0) ?: Tenant::currentId()), self::RECONNECT_TABLE);
        }
        $row->transaction_code = $code;
        $row->phone = self::formatPhone($phone) ?: self::clean($phone, 32);
        $row->mac_address = self::clean($mac, 64);
        $row->username = self::clean($username, 190);
        $row->router_id = (int) ($router['id'] ?? 0) ?: null;
        $row->status = self::clean($status, 32);
        $row->message = self::cleanText($message, 500);
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
    }

    private static function accountReference($prefix, $routerId, $planId, $mac, $ip)
    {
        $macKey = self::macKey($mac);
        $device = $macKey !== '' ? substr($macKey, -8) : strtoupper(substr(sha1($ip), 0, 8));
        $session = strtoupper(substr(sha1($mac . '|' . $ip . '|' . microtime(true)), 0, 6));
        return self::cleanReference($prefix . (int) $routerId . '_' . $device . '_' . (int) $planId . '_' . $session);
    }

    private static function routerIdFromReference($reference, $prefix)
    {
        $reference = self::cleanReference($reference);
        if ($reference === '') {
            return 0;
        }
        $prefix = self::cleanPrefix($prefix);
        $prefixes = array_unique([$prefix, rtrim($prefix, '_'), preg_replace('/[^A-Z0-9]/', '', $prefix)]);
        foreach ($prefixes as $candidate) {
            if ($candidate !== '' && strpos($reference, $candidate) === 0) {
                $tail = substr($reference, strlen($candidate));
                if (preg_match('/^_?([0-9]+)/', $tail, $match)) {
                    return (int) $match[1];
                }
            }
        }
        return 0;
    }

    private static function referenceMatchesPrefix($reference, $prefix)
    {
        $reference = self::cleanReference($reference);
        $prefix = self::cleanPrefix($prefix);
        if ($reference === '') {
            return false;
        }
        if (strpos($reference, $prefix) === 0 || strpos($reference, rtrim($prefix, '_')) === 0) {
            return true;
        }
        $referenceCompact = preg_replace('/[^A-Z0-9]/', '', $reference);
        $prefixCompact = preg_replace('/[^A-Z0-9]/', '', $prefix);
        return $prefixCompact !== '' && strpos($referenceCompact, $prefixCompact) === 0;
    }

    private static function macKey($mac)
    {
        $mac = strtoupper(preg_replace('/[^A-F0-9]/i', '', (string) $mac));
        return strlen($mac) >= 10 ? substr($mac, -12) : '';
    }

    private static function endpointUrl($base, $endpoint)
    {
        $base = rtrim((string) $base, '/');
        $endpoint = trim((string) $endpoint);
        if ($endpoint === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $endpoint)) {
            return $endpoint;
        }
        return $base . '/' . ltrim($endpoint, '/');
    }

    private static function effectiveCallbackUrl($settings)
    {
        if (($settings['callback_mode'] ?? 'local_tunnel') === 'production') {
            return $settings['production_callback_url'] ?: self::defaultCallbackUrl(true);
        }
        return $settings['local_tunnel_url'] ?: ($settings['callback_url'] ?: self::defaultCallbackUrl());
    }

    private static function defaultCallbackUrl($production = false)
    {
        $base = $production ? rtrim(APP_URL, '/') : rtrim(APP_URL, '/');
        return $base . '/?_route=api/jovipay/callback';
    }

    private static function encryptSecret($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }
        if (function_exists('openssl_encrypt')) {
            $iv = random_bytes(16);
            $cipher = openssl_encrypt($value, 'AES-256-CBC', self::secretKey(), OPENSSL_RAW_DATA, $iv);
            if ($cipher !== false) {
                return 'enc:' . base64_encode($iv . $cipher);
            }
        }
        return 'b64:' . base64_encode($value);
    }

    private static function decryptSecret($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }
        if (strpos($value, 'enc:') === 0 && function_exists('openssl_decrypt')) {
            $raw = base64_decode(substr($value, 4), true);
            if ($raw !== false && strlen($raw) > 16) {
                $iv = substr($raw, 0, 16);
                $cipher = substr($raw, 16);
                $plain = openssl_decrypt($cipher, 'AES-256-CBC', self::secretKey(), OPENSSL_RAW_DATA, $iv);
                return $plain === false ? '' : $plain;
            }
        }
        if (strpos($value, 'b64:') === 0) {
            return (string) base64_decode(substr($value, 4));
        }
        return $value;
    }

    private static function secretKey()
    {
        global $config;
        $seed = ($config['api_key'] ?? '') . '|' . __DIR__ . '|FASTNETPAY_JOVIPAY';
        return hash('sha256', $seed, true);
    }

    private static function maskSecret($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }
        if (class_exists('Text') && method_exists('Text', 'maskText')) {
            return Text::maskText($value);
        }
        return substr($value, 0, 3) . str_repeat('*', max(4, strlen($value) - 6)) . substr($value, -3);
    }

    private static function amount($amount)
    {
        return max(0, (int) ceil((float) $amount));
    }

    private static function formatPhone($phone)
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 10 && substr($digits, 0, 1) === '0') {
            $digits = '254' . substr($digits, 1);
        } elseif (strlen($digits) === 9 && in_array(substr($digits, 0, 1), ['7', '1'], true)) {
            $digits = '254' . $digits;
        } elseif (strlen($digits) === 12 && substr($digits, 0, 3) === '254') {
            $digits = $digits;
        } else {
            return false;
        }
        return preg_match('/^254(7|1)[0-9]{8}$/', $digits) ? $digits : false;
    }

    private static function cleanPrefix($value)
    {
        $value = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', (string) $value));
        if ($value === '') {
            return 'WIFI_';
        }
        if (substr($value, -1) !== '_') {
            $value .= '_';
        }
        return substr($value, 0, 32);
    }

    private static function cleanReference($value)
    {
        return substr(strtoupper(preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $value)), 0, 160);
    }

    private static function clean($value, $max = 64)
    {
        return substr(preg_replace('/[^A-Za-z0-9_.: -]+/', '', trim((string) $value)), 0, $max);
    }

    private static function cleanText($value, $max = 255)
    {
        return substr(preg_replace('/\s+/', ' ', trim(strip_tags((string) $value))), 0, $max);
    }

    private static function cleanUrl($value)
    {
        $value = trim(strip_tags((string) $value));
        if ($value === '') {
            return '';
        }
        return filter_var($value, FILTER_VALIDATE_URL) ? substr($value, 0, 255) : '';
    }

    private static function cleanLines($value)
    {
        $lines = [];
        foreach (preg_split('/[\r\n,]+/', (string) $value) as $line) {
            $line = trim($line);
            if ($line !== '' && preg_match('/^[A-Za-z0-9:._\\/-]+$/', $line)) {
                $lines[] = $line;
            }
        }
        return implode("\n", array_unique($lines));
    }

    private static function safeEndpoint($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return self::cleanText($url, 255);
        }
        $safe = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        if (!empty($parts['path'])) {
            $safe .= $parts['path'];
        }
        return self::cleanText($safe, 255);
    }

    private static function safePayload($payload)
    {
        $json = json_encode($payload);
        $json = preg_replace('/("?(token|secret|password|passkey|authorization)"?\\s*[:=]\\s*)("[^"]+"|[^,}\\s]+)/i', '$1"***"', (string) $json);
        return json_decode($json, true) ?: [];
    }

    private static function safeError($message)
    {
        return self::cleanText(preg_replace('/(Bearer\\s+)[A-Za-z0-9._-]+/i', '$1***', (string) $message), 300);
    }

    private static function ensureColumn($table, $column, $definition)
    {
        try {
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
            $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
            $exists = ORM::for_table($table)->raw_query("SHOW COLUMNS FROM `$table` LIKE ?", [$column])->find_one();
            if (!$exists) {
                ORM::raw_execute("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        } catch (Throwable $e) {
            _log('FASTNETPAY Jovi-Pay schema check failed: ' . $e->getMessage());
        }
    }

    private static function callbackSuccess($message, $reference = '', $extra = [])
    {
        $payload = [
            'status' => 'success',
            'message' => $message ?: 'Payment settled successfully',
            'reference' => $reference ?: 'FASTNETPAY',
            'ok' => true,
        ];
        self::json($payload + $extra);
    }

    private static function callbackFailed($message, $status = 200, $extra = [])
    {
        $payload = [
            'status' => 'failed',
            'message' => $message ?: 'Payment could not be settled.',
            'ok' => false,
        ];
        self::json($payload + $extra, $status);
    }

    private static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo json_encode($data);
        exit;
    }
}
