<?php

/**
 * FASTNETPAY SaaS billing, suspension, analytics, and SuperAdmin 2FA helper.
 *
 * This is intentionally additive: it creates its own tables, reads tenant-owned
 * usage from existing PHPNuxBill tables, and only marks tenant/router state
 * instead of deleting or rewriting ISP data.
 */
class SaasBilling
{
    const SCHEMA_VERSION = '2026-07-09-payments-perf1';
    private static $settingsCache = [];
    private static $paymentSettingsCache = [];

    public static function installSchema()
    {
        if (class_exists('FastnetpayRuntime') && FastnetpayRuntime::schemaFresh('saas_billing', self::SCHEMA_VERSION, 86400)) {
            return;
        }

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_billing_settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            setting VARCHAR(120) NOT NULL,
            value MEDIUMTEXT NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY setting_unique (setting)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_billing_bands (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            service_type VARCHAR(32) NOT NULL,
            name VARCHAR(120) NOT NULL,
            min_users INT UNSIGNED NOT NULL DEFAULT 0,
            max_users INT UNSIGNED NULL,
            base_price DECIMAL(15,2) NOT NULL DEFAULT 0,
            included_users INT UNSIGNED NOT NULL DEFAULT 0,
            extra_user_price DECIMAL(15,2) NOT NULL DEFAULT 0,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX service_idx (service_type),
            INDEX enabled_idx (enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_invoices (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            invoice_number VARCHAR(64) NOT NULL,
            billing_month CHAR(7) NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            due_date DATE NOT NULL,
            grace_until DATE NOT NULL,
            status ENUM('draft','issued','paid','overdue','void') NOT NULL DEFAULT 'issued',
            hotspot_users INT UNSIGNED NOT NULL DEFAULT 0,
            pppoe_users INT UNSIGNED NOT NULL DEFAULT 0,
            routers_count INT UNSIGNED NOT NULL DEFAULT 0,
            subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
            total_due DECIMAL(15,2) NOT NULL DEFAULT 0,
            paid_at DATETIME NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY tenant_month_unique (tenant_id, billing_month),
            UNIQUE KEY invoice_unique (invoice_number),
            INDEX tenant_idx (tenant_id),
            INDEX status_idx (status),
            INDEX due_idx (due_date),
            INDEX grace_idx (grace_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_invoice_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            invoice_id BIGINT UNSIGNED NOT NULL,
            tenant_id INT UNSIGNED NOT NULL,
            item_type VARCHAR(64) NOT NULL,
            description VARCHAR(255) NOT NULL,
            quantity DECIMAL(15,2) NOT NULL DEFAULT 1,
            unit_price DECIMAL(15,2) NOT NULL DEFAULT 0,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            metadata MEDIUMTEXT NULL,
            created_at DATETIME NULL,
            INDEX invoice_idx (invoice_id),
            INDEX tenant_idx (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenant_suspensions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            invoice_id BIGINT UNSIGNED NULL,
            reason VARCHAR(160) NOT NULL,
            status ENUM('active','restored') NOT NULL DEFAULT 'active',
            message TEXT NULL,
            metadata MEDIUMTEXT NULL,
            suspended_at DATETIME NULL,
            restored_at DATETIME NULL,
            created_by INT UNSIGNED NULL,
            INDEX tenant_idx (tenant_id),
            INDEX invoice_idx (invoice_id),
            INDEX status_idx (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS superadmin_2fa_settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            remember_days INT UNSIGNED NOT NULL DEFAULT 0,
            recovery_codes MEDIUMTEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY admin_unique (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS superadmin_2fa_otps (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NOT NULL,
            otp_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified_at DATETIME NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            ip VARCHAR(80) NULL,
            created_at DATETIME NOT NULL,
            INDEX admin_idx (admin_id),
            INDEX expires_idx (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenant_billing_snapshots (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            billing_month CHAR(7) NOT NULL,
            active_hotspot INT UNSIGNED NOT NULL DEFAULT 0,
            active_pppoe INT UNSIGNED NOT NULL DEFAULT 0,
            routers_count INT UNSIGNED NOT NULL DEFAULT 0,
            amount_due DECIMAL(15,2) NOT NULL DEFAULT 0,
            invoice_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            UNIQUE KEY tenant_month_unique (tenant_id, billing_month),
            INDEX tenant_idx (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_payment_settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(64) NOT NULL DEFAULT 'jovipay',
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            shortcode VARCHAR(80) NULL,
            paybill_number VARCHAR(80) NULL,
            till_number VARCHAR(80) NULL,
            account_prefix VARCHAR(80) NOT NULL DEFAULT 'FASTNETPAY_',
            callback_url VARCHAR(255) NULL,
            callback_secret_encrypted TEXT NULL,
            confirmation_url VARCHAR(255) NULL,
            validation_url VARCHAR(255) NULL,
            support_phone VARCHAR(60) NULL,
            instructions TEXT NULL,
            auto_settle TINYINT(1) NOT NULL DEFAULT 1,
            auto_restore TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenant_payment_gateways (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            router_id INT UNSIGNED NULL,
            gateway_name VARCHAR(140) NOT NULL,
            gateway_type VARCHAR(64) NOT NULL,
            shortcode VARCHAR(80) NULL,
            till_number VARCHAR(80) NULL,
            paybill_number VARCHAR(80) NULL,
            bank_name VARCHAR(140) NULL,
            settlement_account_name VARCHAR(160) NULL,
            account_prefix VARCHAR(100) NOT NULL,
            callback_url VARCHAR(255) NULL,
            validation_url VARCHAR(255) NULL,
            confirmation_url VARCHAR(255) NULL,
            credentials_encrypted MEDIUMTEXT NULL,
            public_instructions TEXT NULL,
            payment_label VARCHAR(140) NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX tenant_idx (tenant_id),
            INDEX router_idx (router_id),
            INDEX prefix_idx (account_prefix),
            INDEX enabled_idx (is_enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_invoice_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            invoice_id BIGINT UNSIGNED NULL,
            transaction_code VARCHAR(120) NOT NULL,
            phone VARCHAR(40) NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            account_reference VARCHAR(160) NULL,
            payment_provider VARCHAR(64) NULL,
            raw_payload MEDIUMTEXT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'received',
            matched_status VARCHAR(32) NOT NULL DEFAULT 'pending',
            received_at DATETIME NULL,
            created_at DATETIME NULL,
            UNIQUE KEY transaction_unique (transaction_code),
            INDEX tenant_idx (tenant_id),
            INDEX invoice_idx (invoice_id),
            INDEX status_idx (status),
            INDEX reference_idx (account_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenant_customer_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            router_id INT UNSIGNED NULL,
            customer_id INT UNSIGNED NULL,
            package_id INT UNSIGNED NULL,
            session_id VARCHAR(120) NULL,
            transaction_code VARCHAR(120) NOT NULL,
            phone VARCHAR(40) NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            account_reference VARCHAR(160) NULL,
            gateway_id BIGINT UNSIGNED NULL,
            raw_payload MEDIUMTEXT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'received',
            activation_status VARCHAR(32) NOT NULL DEFAULT 'pending',
            received_at DATETIME NULL,
            created_at DATETIME NULL,
            UNIQUE KEY transaction_unique (transaction_code),
            INDEX tenant_idx (tenant_id),
            INDEX router_idx (router_id),
            INDEX reference_idx (account_reference),
            INDEX gateway_idx (gateway_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS unmatched_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            source VARCHAR(80) NOT NULL,
            account_reference VARCHAR(160) NULL,
            transaction_code VARCHAR(120) NULL,
            phone VARCHAR(40) NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            raw_payload MEDIUMTEXT NULL,
            reason VARCHAR(255) NULL,
            resolved_by INT UNSIGNED NULL,
            resolved_at DATETIME NULL,
            created_at DATETIME NULL,
            INDEX source_idx (source),
            INDEX reference_idx (account_reference),
            INDEX resolved_idx (resolved_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::migratePaymentSchema();
        self::seedSettings();
        self::seedBands();
        self::seedPaymentSettings();
        if (class_exists('FastnetpayRuntime')) {
            FastnetpayRuntime::markSchemaFresh('saas_billing', self::SCHEMA_VERSION);
        }
    }

    public static function seedSettings()
    {
        $defaults = [
            'configuration_fee' => '1000',
            'first_month_payment' => '500',
            'billing_day' => '23',
            'grace_day' => '28',
            'auto_suspend_unpaid' => 'yes',
            'auto_disconnect_vpn' => 'no',
            'invoice_generation_mode' => 'manual',
            'reminder_days_before_due' => '3,1',
            'suspension_message' => 'Your FASTNETPAY SaaS account is suspended because an invoice is overdue. Please settle the invoice or contact support.',
            'allow_tenant_invoice_preview' => 'yes',
            'superadmin_2fa_enabled' => 'no',
        ];
        foreach ($defaults as $key => $value) {
            if (!ORM::for_table('saas_billing_settings')->where('setting', $key)->find_one()) {
                self::saveSetting($key, $value);
            }
        }
    }

    public static function seedBands()
    {
        $bands = [
            ['hotspot', 'Starter', 0, 50, 500, 0, 0, 10],
            ['hotspot', 'Growth', 51, 300, 1000, 0, 0, 20],
            ['hotspot', 'Intermediate', 301, 700, 1500, 0, 0, 30],
            ['hotspot', 'Scale 1', 701, 1300, 2000, 0, 0, 40],
            ['hotspot', 'Scale 2', 1301, 2000, 2500, 0, 0, 50],
            ['hotspot', 'Scale 3', 2001, 3000, 3000, 0, 0, 60],
            ['hotspot', 'Enterprise', 3001, null, 3500, 0, 0, 70],
            ['pppoe', 'PPPoE Starter', 0, 25, 500, 25, 0, 10],
            ['pppoe', 'PPPoE Master', 26, null, 500, 25, 20, 20],
        ];
        foreach ($bands as $band) {
            $exists = ORM::for_table('saas_billing_bands')
                ->where('service_type', $band[0])
                ->where('name', $band[1])
                ->find_one();
            if ($exists) {
                continue;
            }
            $row = ORM::for_table('saas_billing_bands')->create();
            $row->service_type = $band[0];
            $row->name = $band[1];
            $row->min_users = $band[2];
            $row->max_users = $band[3];
            $row->base_price = $band[4];
            $row->included_users = $band[5];
            $row->extra_user_price = $band[6];
            $row->enabled = 1;
            $row->sort_order = $band[7];
            $row->created_at = date('Y-m-d H:i:s');
            $row->updated_at = date('Y-m-d H:i:s');
            $row->save();
        }
    }

    public static function seedPaymentSettings()
    {
        if (!ORM::for_table('saas_payment_settings')->find_one()) {
            $row = ORM::for_table('saas_payment_settings')->create();
            $row->provider = 'jovipay';
            $row->enabled = 0;
            $row->account_prefix = 'FASTNETPAY_';
            $row->callback_url = self::defaultSaasCallbackUrl();
            $row->instructions = 'Pay your FASTNETPAY SaaS invoice using the account reference shown on your billing screen.';
            $row->auto_settle = 1;
            $row->auto_restore = 1;
            $row->created_at = date('Y-m-d H:i:s');
            $row->updated_at = date('Y-m-d H:i:s');
            $row->save();
        }
    }

    public static function paymentSettings($showSecret = false)
    {
        self::installSchema();
        $cacheKey = $showSecret ? 'secret' : 'public';
        if (isset(self::$paymentSettingsCache[$cacheKey])) {
            return self::$paymentSettingsCache[$cacheKey];
        }
        $row = ORM::for_table('saas_payment_settings')->order_by_asc('id')->find_one();
        if (!$row) {
            self::seedPaymentSettings();
            $row = ORM::for_table('saas_payment_settings')->order_by_asc('id')->find_one();
        }
        $secret = self::decryptSecret($row['callback_secret_encrypted'] ?? '');
        $settings = [
            'id' => (int) $row['id'],
            'enabled' => (int) $row['enabled'],
            'provider' => (string) ($row['provider'] ?: 'jovipay'),
            'shortcode' => (string) $row['shortcode'],
            'paybill_number' => (string) $row['paybill_number'],
            'till_number' => (string) $row['till_number'],
            'account_prefix' => (string) ($row['account_prefix'] ?: 'FASTNETPAY_'),
            'callback_url' => (string) ($row['callback_url'] ?: self::defaultSaasCallbackUrl()),
            'callback_secret' => $showSecret ? $secret : '',
            'callback_secret_masked' => self::maskSecret($secret),
            'confirmation_url' => (string) $row['confirmation_url'],
            'validation_url' => (string) $row['validation_url'],
            'support_phone' => (string) $row['support_phone'],
            'instructions' => (string) $row['instructions'],
            'auto_settle' => (int) $row['auto_settle'],
            'auto_restore' => (int) $row['auto_restore'],
        ];
        self::$paymentSettingsCache[$cacheKey] = $settings;
        return $settings;
    }

    public static function savePaymentSettingsFromPost($adminId)
    {
        self::installSchema();
        $row = ORM::for_table('saas_payment_settings')->order_by_asc('id')->find_one();
        if (!$row) {
            $row = ORM::for_table('saas_payment_settings')->create();
            $row->created_at = date('Y-m-d H:i:s');
        }
        $provider = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) _post('provider', 'jovipay')));
        if (!in_array($provider, ['mpesa_c2b', 'jovipay', 'manual', 'bank_paybill', 'till'], true)) {
            $provider = 'jovipay';
        }
        $callbackUrl = self::cleanUrl(_post('callback_url', self::defaultSaasCallbackUrl()));
        if ($callbackUrl !== '' && !filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Callback URL must be a valid URL.');
        }
        $row->enabled = _post('enabled') === '1' ? 1 : 0;
        $row->provider = $provider;
        $row->shortcode = self::clean((string) _post('shortcode'), 80);
        $row->paybill_number = self::clean((string) _post('paybill_number'), 80);
        $row->till_number = self::clean((string) _post('till_number'), 80);
        $row->account_prefix = self::cleanPrefix(_post('account_prefix', 'FASTNETPAY_'), 'FASTNETPAY_');
        $row->callback_url = $callbackUrl ?: self::defaultSaasCallbackUrl();
        $secret = trim((string) _post('callback_secret'));
        if ($secret !== '') {
            $row->callback_secret_encrypted = self::encryptSecret($secret);
        }
        $row->confirmation_url = self::cleanUrl(_post('confirmation_url'));
        $row->validation_url = self::cleanUrl(_post('validation_url'));
        $row->support_phone = self::clean((string) _post('support_phone'), 60);
        $row->instructions = trim(strip_tags((string) _post('instructions')));
        $row->auto_settle = _post('auto_settle') === '0' ? 0 : 1;
        $row->auto_restore = _post('auto_restore') === '0' ? 0 : 1;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        self::$paymentSettingsCache = [];
        Tenant::audit('saas.payment_settings_changed', 'SaaS payment collection settings changed.', 'saas_payment_settings', (string) $row->id(), Tenant::currentId(), $adminId);
    }

    public static function tenantGateways($tenantId = 0)
    {
        self::installSchema();
        $query = ORM::for_table('tenant_payment_gateways')->order_by_desc('is_default')->order_by_desc('id');
        if ((int) $tenantId > 0) {
            $query->where('tenant_id', (int) $tenantId);
        }
        $rows = [];
        foreach ($query->find_array() as $row) {
            $tenant = ORM::for_table('tenants')->find_one((int) $row['tenant_id']);
            $row['tenant_name'] = $tenant ? (string) $tenant['name'] : ('Tenant #' . $row['tenant_id']);
            $row['credentials_masked'] = self::maskGatewayCredentials($row['credentials_encrypted']);
            $rows[] = $row;
        }
        return $rows;
    }

    public static function tenantGateway($id)
    {
        self::installSchema();
        return ORM::for_table('tenant_payment_gateways')->find_one((int) $id);
    }

    public static function saveTenantGatewayFromPost($adminId)
    {
        self::installSchema();
        $id = (int) _post('gateway_id');
        $tenantId = (int) _post('tenant_id');
        $tenant = ORM::for_table('tenants')->find_one($tenantId);
        if (!$tenant || (string) $tenant['slug'] === 'main') {
            throw new Exception('Select a valid ISP tenant.');
        }
        $row = $id > 0 ? ORM::for_table('tenant_payment_gateways')->find_one($id) : ORM::for_table('tenant_payment_gateways')->create();
        if (!$row) {
            throw new Exception('Tenant payment gateway not found.');
        }

        $type = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) _post('gateway_type', 'jovipay')));
        $allowed = ['mpesa_paybill_c2b', 'bank_mpesa_paybill', 'mpesa_till', 'jovipay_prefix', 'manual', 'other'];
        if (!in_array($type, $allowed, true)) {
            $type = 'jovipay_prefix';
        }
        $prefix = self::cleanPrefix(_post('account_prefix', 'WIFI_' . strtoupper((string) $tenant['slug']) . '_'), 'WIFI_' . strtoupper((string) $tenant['slug']) . '_');
        $callbackUrl = self::cleanUrl(_post('callback_url'));
        if ($callbackUrl !== '' && !filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Callback URL must be a valid URL.');
        }

        $credentials = self::gatewayCredentialsFromPost($row ? (string) $row['credentials_encrypted'] : '');
        $row->tenant_id = $tenantId;
        $row->router_id = (int) _post('router_id') ?: null;
        $row->gateway_name = substr(trim(strip_tags((string) _post('gateway_name'))), 0, 140) ?: 'M-Pesa Gateway';
        $row->gateway_type = $type;
        $row->shortcode = self::clean((string) _post('shortcode'), 80);
        $row->till_number = self::clean((string) _post('till_number'), 80);
        $row->paybill_number = self::clean((string) _post('paybill_number'), 80);
        $row->bank_name = substr(trim(strip_tags((string) _post('bank_name'))), 0, 140);
        $row->settlement_account_name = substr(trim(strip_tags((string) _post('settlement_account_name'))), 0, 160);
        $row->account_prefix = $prefix;
        $row->callback_url = $callbackUrl;
        $row->validation_url = self::cleanUrl(_post('validation_url'));
        $row->confirmation_url = self::cleanUrl(_post('confirmation_url'));
        $row->credentials_encrypted = $credentials ? self::encryptSecret(json_encode($credentials)) : (string) $row['credentials_encrypted'];
        $row->public_instructions = trim(strip_tags((string) _post('public_instructions')));
        $row->payment_label = substr(trim(strip_tags((string) _post('payment_label', 'M-Pesa'))), 0, 140);
        $row->is_default = _post('is_default') === '1' ? 1 : 0;
        $row->is_enabled = _post('is_enabled') === '0' ? 0 : 1;
        if (!$id) {
            $row->created_at = date('Y-m-d H:i:s');
        }
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();

        if ((int) $row['is_default'] === 1) {
            ORM::raw_execute('UPDATE tenant_payment_gateways SET is_default = 0 WHERE tenant_id = ? AND id <> ?', [$tenantId, (int) $row->id()]);
            self::syncTenantGatewayToPublicSettings($row, $tenantId);
        }
        Tenant::audit('tenant.gateway_saved', 'Tenant customer payment gateway saved: ' . $row['gateway_name'], 'tenant_payment_gateway', (string) $row->id(), $tenantId, $adminId);
        return $row;
    }

    public static function tenantGatewayPublicSettings($tenantId)
    {
        self::installSchema();
        $gateway = self::activeTenantGateway((int) $tenantId);
        if (!$gateway) {
            return [];
        }
        $credentials = self::decryptGatewayCredentials((string) $gateway['credentials_encrypted']);
        return [
            'enabled' => (int) $gateway['is_enabled'] === 1 ? '1' : '0',
            'account_prefix' => (string) $gateway['account_prefix'],
            'callback_url' => (string) $gateway['callback_url'],
            'callback_secret' => (string) ($credentials['callback_secret'] ?? ''),
            'api_base_url' => (string) ($credentials['api_base_url'] ?? ''),
            'stk_endpoint' => (string) ($credentials['stk_endpoint'] ?? ''),
            'api_token' => (string) ($credentials['api_token'] ?? ''),
            'mini_app_id' => (string) ($credentials['mini_app_id'] ?? ''),
            'gateway_label' => (string) ($gateway['payment_label'] ?: $gateway['gateway_name']),
            'support_phone' => Tenant::setting('payment', 'support_phone', '', (int) $tenantId),
        ];
    }

    public static function activeTenantGateway($tenantId, $routerId = 0)
    {
        $query = ORM::for_table('tenant_payment_gateways')
            ->where('tenant_id', (int) $tenantId)
            ->where('is_enabled', 1)
            ->order_by_desc('is_default')
            ->order_by_desc('id');
        if ((int) $routerId > 0) {
            $query->where_raw('(router_id = ? OR router_id IS NULL)', [(int) $routerId]);
        }
        return $query->find_one();
    }

    public static function setting($key, $default = '')
    {
        if (array_key_exists($key, self::$settingsCache)) {
            return self::$settingsCache[$key] === null ? $default : self::$settingsCache[$key];
        }
        $row = ORM::for_table('saas_billing_settings')->where('setting', $key)->find_one();
        self::$settingsCache[$key] = $row ? (string) $row['value'] : null;
        return $row ? (string) $row['value'] : $default;
    }

    public static function saveSetting($key, $value)
    {
        $row = ORM::for_table('saas_billing_settings')->where('setting', $key)->find_one();
        if (!$row) {
            $row = ORM::for_table('saas_billing_settings')->create();
            $row->setting = $key;
        }
        $row->value = (string) $value;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        unset(self::$settingsCache[$key]);
        self::$paymentSettingsCache = [];
    }

    public static function settings()
    {
        self::installSchema();
        $keys = ['configuration_fee', 'first_month_payment', 'billing_day', 'grace_day', 'auto_suspend_unpaid', 'auto_disconnect_vpn', 'invoice_generation_mode', 'reminder_days_before_due', 'suspension_message', 'allow_tenant_invoice_preview', 'superadmin_2fa_enabled'];
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = self::setting($key, '');
        }
        $settings['sms_ready'] = self::smsReady();
        return $settings;
    }

    public static function saveSettingsFromPost($adminId)
    {
        $pairs = [
            'configuration_fee' => self::money(_post('configuration_fee', '1000')),
            'first_month_payment' => self::money(_post('first_month_payment', '500')),
            'billing_day' => (string) max(1, min(28, (int) _post('billing_day', 23))),
            'grace_day' => (string) max(1, min(31, (int) _post('grace_day', 28))),
            'auto_suspend_unpaid' => _post('auto_suspend_unpaid') === 'yes' ? 'yes' : 'no',
            'auto_disconnect_vpn' => _post('auto_disconnect_vpn') === 'yes' ? 'yes' : 'no',
            'invoice_generation_mode' => _post('invoice_generation_mode') === 'automatic' ? 'automatic' : 'manual',
            'reminder_days_before_due' => preg_replace('/[^0-9,]/', '', _post('reminder_days_before_due', '3,1')),
            'suspension_message' => trim(strip_tags((string) _post('suspension_message', ''))),
            'allow_tenant_invoice_preview' => _post('allow_tenant_invoice_preview') === 'no' ? 'no' : 'yes',
        ];
        foreach ($pairs as $key => $value) {
            self::saveSetting($key, $value);
        }
        Tenant::audit('saas.billing_settings_changed', 'SaaS billing settings updated.', 'billing', 'settings', Tenant::currentId(), $adminId);
    }

    public static function bands($serviceType = '')
    {
        self::installSchema();
        $query = ORM::for_table('saas_billing_bands')->order_by_asc('service_type')->order_by_asc('sort_order')->order_by_asc('min_users');
        if ($serviceType !== '') {
            $query->where('service_type', $serviceType);
        }
        return $query->find_many();
    }

    public static function saveBandFromPost($adminId)
    {
        $id = (int) _post('band_id');
        $service = strtolower(_post('service_type', 'hotspot'));
        if (!in_array($service, ['hotspot', 'pppoe'], true)) {
            throw new Exception('Invalid billing service type.');
        }
        $row = $id > 0 ? ORM::for_table('saas_billing_bands')->find_one($id) : ORM::for_table('saas_billing_bands')->create();
        if (!$row) {
            throw new Exception('Billing band not found.');
        }
        $row->service_type = $service;
        $row->name = substr(trim(strip_tags((string) _post('name'))), 0, 120);
        if ($row->name === '') {
            throw new Exception('Billing band name is required.');
        }
        $row->min_users = max(0, (int) _post('min_users', 0));
        $max = trim((string) _post('max_users'));
        $row->max_users = $max === '' ? null : max(0, (int) $max);
        $row->base_price = self::money(_post('base_price', '0'));
        $row->included_users = max(0, (int) _post('included_users', 0));
        $row->extra_user_price = self::money(_post('extra_user_price', '0'));
        $row->enabled = _post('enabled') === '0' ? 0 : 1;
        $row->sort_order = max(0, (int) _post('sort_order', 0));
        if (!$id) {
            $row->created_at = date('Y-m-d H:i:s');
        }
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        Tenant::audit('saas.billing_band_changed', 'SaaS billing band saved: ' . $row['name'], 'billing_band', (string) $row->id(), Tenant::currentId(), $adminId);
    }

    public static function usage($tenantId)
    {
        $tenantId = (int) $tenantId;
        $today = date('Y-m-d');
        $hotspot = ORM::for_table('tbl_user_recharges')
            ->where('tenant_id', $tenantId)
            ->where('status', 'on')
            ->where('type', 'Hotspot')
            ->where_gte('expiration', $today)
            ->count();
        $pppoe = ORM::for_table('tbl_user_recharges')
            ->where('tenant_id', $tenantId)
            ->where('status', 'on')
            ->where_in('type', ['PPPOE', 'PPPoE'])
            ->where_gte('expiration', $today)
            ->count();
        $routers = ORM::for_table('tbl_routers')->where('tenant_id', $tenantId)->count();
        return ['hotspot' => (int) $hotspot, 'pppoe' => (int) $pppoe, 'routers' => (int) $routers];
    }

    public static function previewInvoice($tenantId, $billingMonth = null)
    {
        self::installSchema();
        $tenant = ORM::for_table('tenants')->find_one((int) $tenantId);
        if (!$tenant) {
            throw new Exception('Tenant not found.');
        }
        if (!empty($tenant['billing_exempt'])) {
            return [
                'tenant' => $tenant,
                'billing_month' => $billingMonth ?: date('Y-m'),
                'period_start' => ($billingMonth ?: date('Y-m')) . '-01',
                'period_end' => date('Y-m-t', strtotime(($billingMonth ?: date('Y-m')) . '-01')),
                'due_date' => ($billingMonth ?: date('Y-m')) . '-' . str_pad((string) max(1, min(28, (int) self::setting('billing_day', '23'))), 2, '0', STR_PAD_LEFT),
                'grace_until' => ($billingMonth ?: date('Y-m')) . '-' . str_pad((string) max(1, min(31, (int) self::setting('grace_day', '28'))), 2, '0', STR_PAD_LEFT),
                'usage' => self::usage((int) $tenant['id']),
                'hotspot_band' => null,
                'pppoe_band' => null,
                'lines' => [self::line('billing_exempt', 'Billing exempt tenant: ' . ((string) $tenant['exemption_reason'] ?: 'internal/non-billable'), 1, 0, 0)],
                'subtotal' => 0,
                'total_due' => 0,
                'first_invoice' => false,
            ];
        }
        $month = $billingMonth ?: date('Y-m');
        $periodStart = $month . '-01';
        $periodEnd = date('Y-m-t', strtotime($periodStart));
        $billingDay = max(1, min(28, (int) self::setting('billing_day', '23')));
        $graceDay = max($billingDay, min(31, (int) self::setting('grace_day', '28')));
        $dueDate = $month . '-' . str_pad((string) $billingDay, 2, '0', STR_PAD_LEFT);
        $graceDate = $month . '-' . str_pad((string) min($graceDay, (int) date('t', strtotime($periodStart))), 2, '0', STR_PAD_LEFT);
        $usage = self::usage((int) $tenant['id']);
        $lines = [];

        $hotspotBand = self::bandFor('hotspot', $usage['hotspot']);
        if ($hotspotBand) {
            $amount = (float) $hotspotBand['base_price'];
            $lines[] = self::line('hotspot', 'Hotspot SaaS band: ' . $hotspotBand['name'] . ' (' . $usage['hotspot'] . ' active users)', 1, $amount, $amount, $hotspotBand);
        }

        $pppoeBand = self::bandFor('pppoe', $usage['pppoe']);
        if ($pppoeBand) {
            $extra = max(0, $usage['pppoe'] - (int) $pppoeBand['included_users']);
            $amount = (float) $pppoeBand['base_price'] + ($extra * (float) $pppoeBand['extra_user_price']);
            $desc = 'PPPoE SaaS band: ' . $pppoeBand['name'] . ' (' . $usage['pppoe'] . ' active users';
            if ($extra > 0) {
                $desc .= ', ' . $extra . ' extra @ ' . self::money($pppoeBand['extra_user_price']);
            }
            $desc .= ')';
            $lines[] = self::line('pppoe', $desc, 1, $amount, $amount, $pppoeBand);
        }

        $hasInvoice = (int) ORM::for_table('saas_invoices')->where('tenant_id', (int) $tenant['id'])->count() > 0;
        if (!$hasInvoice) {
            $setup = (float) self::setting('configuration_fee', '1000');
            $first = (float) self::setting('first_month_payment', '500');
            if ($setup > 0) {
                $lines[] = self::line('configuration_fee', 'One-time configuration fee', 1, $setup, $setup);
            }
            if ($first > 0) {
                $lines[] = self::line('first_month', 'First month platform payment', 1, $first, $first);
            }
        }

        $total = 0;
        foreach ($lines as $line) {
            $total += (float) $line['amount'];
        }

        return [
            'tenant' => $tenant,
            'billing_month' => $month,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'due_date' => $dueDate,
            'grace_until' => $graceDate,
            'usage' => $usage,
            'hotspot_band' => $hotspotBand,
            'pppoe_band' => $pppoeBand,
            'lines' => $lines,
            'subtotal' => $total,
            'total_due' => $total,
            'first_invoice' => !$hasInvoice,
        ];
    }

    public static function generateInvoice($tenantId, $billingMonth = null, $adminId = 0)
    {
        $preview = self::previewInvoice($tenantId, $billingMonth);
        $existing = ORM::for_table('saas_invoices')
            ->where('tenant_id', (int) $tenantId)
            ->where('billing_month', $preview['billing_month'])
            ->find_one();
        if ($existing) {
            return $existing;
        }

        $invoice = ORM::for_table('saas_invoices')->create();
        $invoice->tenant_id = (int) $tenantId;
        $invoice->invoice_number = self::invoiceNumber((int) $tenantId, $preview['billing_month']);
        $invoice->billing_month = $preview['billing_month'];
        $invoice->period_start = $preview['period_start'];
        $invoice->period_end = $preview['period_end'];
        $invoice->due_date = $preview['due_date'];
        $invoice->grace_until = $preview['grace_until'];
        $invoice->status = 'issued';
        $invoice->hotspot_users = $preview['usage']['hotspot'];
        $invoice->pppoe_users = $preview['usage']['pppoe'];
        $invoice->routers_count = $preview['usage']['routers'];
        $invoice->subtotal = $preview['subtotal'];
        $invoice->total_due = $preview['total_due'];
        $invoice->created_at = date('Y-m-d H:i:s');
        $invoice->updated_at = date('Y-m-d H:i:s');
        $invoice->save();

        foreach ($preview['lines'] as $line) {
            $item = ORM::for_table('saas_invoice_items')->create();
            $item->invoice_id = (int) $invoice->id();
            $item->tenant_id = (int) $tenantId;
            $item->item_type = $line['item_type'];
            $item->description = $line['description'];
            $item->quantity = $line['quantity'];
            $item->unit_price = $line['unit_price'];
            $item->amount = $line['amount'];
            $item->metadata = json_encode($line['metadata'] ?? []);
            $item->created_at = date('Y-m-d H:i:s');
            $item->save();
        }

        self::saveSnapshot((int) $tenantId, $preview, (int) $invoice->id());
        Tenant::audit('saas.invoice_generated', 'Invoice generated: ' . $invoice['invoice_number'], 'invoice', (string) $invoice->id(), (int) $tenantId, $adminId);
        return $invoice;
    }

    public static function markPaid($invoiceId, $adminId = 0)
    {
        $invoice = ORM::for_table('saas_invoices')->find_one((int) $invoiceId);
        if (!$invoice) {
            throw new Exception('Invoice not found.');
        }
        $balance = self::invoiceBalance($invoice);
        if ($balance > 0) {
            self::recordInvoicePayment($invoice, [
                'transaction_code' => 'MANUAL-' . $invoice['invoice_number'] . '-' . time(),
                'phone' => '',
                'amount' => $balance,
                'account_reference' => self::tenantPaymentReference((int) $invoice['tenant_id'], $invoice),
                'payment_provider' => 'manual',
                'raw_payload' => json_encode(['marked_by_admin' => $adminId]),
                'status' => 'success',
                'matched_status' => 'manual_full',
            ]);
        }
        $invoice->status = 'paid';
        $invoice->amount_paid = self::invoicePaidAmount((int) $invoice['id']);
        $invoice->balance_due = 0;
        $invoice->last_payment_at = date('Y-m-d H:i:s');
        $invoice->paid_at = date('Y-m-d H:i:s');
        $invoice->updated_at = date('Y-m-d H:i:s');
        $invoice->save();
        self::restoreTenant((int) $invoice['tenant_id'], $adminId, 'Invoice paid: ' . $invoice['invoice_number']);
        Tenant::audit('saas.invoice_paid', 'Invoice marked paid: ' . $invoice['invoice_number'], 'invoice', (string) $invoice->id(), (int) $invoice['tenant_id'], $adminId);
        return $invoice;
    }

    public static function suspendTenant($tenantId, $invoiceId = null, $adminId = 0, $reason = 'Overdue SaaS invoice')
    {
        $tenant = ORM::for_table('tenants')->find_one((int) $tenantId);
        if (!$tenant) {
            throw new Exception('Tenant not found.');
        }
        if ((string) $tenant['slug'] === 'main') {
            throw new Exception('The mother system tenant cannot be suspended.');
        }
        $tenant->status = 'suspended';
        $tenant->subscription_status = 'suspended';
        $tenant->updated_at = date('Y-m-d H:i:s');
        $tenant->save();

        if (self::setting('auto_disconnect_vpn', 'no') === 'yes') {
            $routers = ORM::for_table('tbl_routers')->where('tenant_id', (int) $tenantId)->find_many();
            foreach ($routers as $router) {
                if (Tenant::hasColumn('tbl_routers', 'vpn_status')) {
                    $router->vpn_status = 'blocked';
                }
                if (Tenant::hasColumn('tbl_routers', 'provisioning_status')) {
                    $router->provisioning_status = 'tenant_suspended';
                }
                $router->save();
            }
        }

        $row = ORM::for_table('tenant_suspensions')->create();
        $row->tenant_id = (int) $tenantId;
        $row->invoice_id = $invoiceId ? (int) $invoiceId : null;
        $row->reason = $reason;
        $row->status = 'active';
        $row->message = self::suspensionMessage((int) $tenantId, $invoiceId);
        $row->metadata = json_encode(['vpn_blocked' => self::setting('auto_disconnect_vpn', 'no')]);
        $row->suspended_at = date('Y-m-d H:i:s');
        $row->created_by = $adminId ?: null;
        $row->save();

        Tenant::audit('tenant.suspended_for_billing', $reason, 'tenant', (string) $tenantId, (int) $tenantId, $adminId);
    }

    public static function restoreTenant($tenantId, $adminId = 0, $reason = 'Tenant restored')
    {
        $tenant = ORM::for_table('tenants')->find_one((int) $tenantId);
        if (!$tenant) {
            throw new Exception('Tenant not found.');
        }
        $tenant->status = 'active';
        $tenant->subscription_status = 'active';
        $tenant->updated_at = date('Y-m-d H:i:s');
        $tenant->save();

        $suspensions = ORM::for_table('tenant_suspensions')->where('tenant_id', (int) $tenantId)->where('status', 'active')->find_many();
        foreach ($suspensions as $row) {
            $row->status = 'restored';
            $row->restored_at = date('Y-m-d H:i:s');
            $row->save();
        }

        $routers = ORM::for_table('tbl_routers')->where('tenant_id', (int) $tenantId)->find_many();
        foreach ($routers as $router) {
            if (Tenant::hasColumn('tbl_routers', 'vpn_status') && $router['vpn_status'] === 'blocked') {
                $router->vpn_status = 'restored';
            }
            if (Tenant::hasColumn('tbl_routers', 'provisioning_status') && $router['provisioning_status'] === 'tenant_suspended') {
                $router->provisioning_status = 'ready';
            }
            $router->save();
        }

        Tenant::audit('tenant.restored_for_billing', $reason, 'tenant', (string) $tenantId, (int) $tenantId, $adminId);
    }

    public static function suspensionMessage($tenantId, $invoiceId = null)
    {
        $invoice = $invoiceId ? ORM::for_table('saas_invoices')->find_one((int) $invoiceId) : self::latestUnpaidInvoice($tenantId);
        $base = self::setting('suspension_message', 'Your FASTNETPAY SaaS account is suspended because an invoice is overdue.');
        if (!$invoice) {
            return $base;
        }
        return $base . ' Amount due: Ksh ' . number_format((float) $invoice['total_due'], 2) . '. Due date: ' . $invoice['due_date'] . '. Grace deadline: ' . $invoice['grace_until'] . '.';
    }

    public static function tenantCanLogin($tenant)
    {
        return $tenant && (string) $tenant['status'] !== 'suspended';
    }

    public static function tenantPaymentReference($tenantId, $invoice = null)
    {
        $tenant = ORM::for_table('tenants')->find_one((int) $tenantId);
        $slug = $tenant ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $tenant['slug'])) : ('TENANT' . (int) $tenantId);
        $settings = self::paymentSettings();
        return self::cleanReference(($settings['account_prefix'] ?: 'FASTNETPAY_') . $slug);
    }

    public static function suspendedTenantPaymentContext($tenantId)
    {
        self::installSchema();
        $tenant = ORM::for_table('tenants')->find_one((int) $tenantId);
        if (!$tenant) {
            throw new Exception('Tenant not found.');
        }
        $invoice = self::latestUnpaidInvoice((int) $tenantId);
        if (!$invoice) {
            $invoice = self::generateInvoice((int) $tenantId, date('Y-m'));
        }
        $usage = self::usage((int) $tenantId);
        $settings = self::paymentSettings();
        $paid = $invoice ? self::invoicePaidAmount((int) $invoice['id']) : 0;
        $balance = $invoice ? self::invoiceBalance($invoice) : 0;
        return [
            'tenant' => $tenant,
            'invoice' => $invoice,
            'usage' => $usage,
            'settings' => $settings,
            'amount_paid' => $paid,
            'balance_due' => $balance,
            'account_reference' => self::tenantPaymentReference((int) $tenantId, $invoice),
            'message' => self::suspensionMessage((int) $tenantId, $invoice ? (int) $invoice['id'] : null),
        ];
    }

    public static function invoicePaidAmount($invoiceId)
    {
        self::installSchema();
        return (float) ORM::for_table('saas_invoice_payments')
            ->where('invoice_id', (int) $invoiceId)
            ->where_in('status', ['success', 'received'])
            ->sum('amount');
    }

    public static function invoiceBalance($invoice)
    {
        if (!$invoice) {
            return 0.0;
        }
        return max(0, (float) $invoice['total_due'] - self::invoicePaidAmount((int) $invoice['id']));
    }

    public static function invoicePayments($invoiceId)
    {
        self::installSchema();
        return ORM::for_table('saas_invoice_payments')
            ->where('invoice_id', (int) $invoiceId)
            ->order_by_desc('id')
            ->find_many();
    }

    public static function unmatchedPayments($limit = 200)
    {
        self::installSchema();
        return ORM::for_table('unmatched_payments')
            ->order_by_desc('id')
            ->limit((int) $limit)
            ->find_many();
    }

    public static function saasInvoicePayments($limit = 200)
    {
        self::installSchema();
        return ORM::for_table('saas_invoice_payments')
            ->order_by_desc('id')
            ->limit((int) $limit)
            ->find_many();
    }

    public static function tenantCustomerPayments($limit = 200)
    {
        self::installSchema();
        return ORM::for_table('tenant_customer_payments')
            ->order_by_desc('id')
            ->limit((int) $limit)
            ->find_many();
    }

    public static function reconcileUnmatchedPayment($unmatchedId, $invoiceId, $adminId)
    {
        self::installSchema();
        $unmatched = ORM::for_table('unmatched_payments')->find_one((int) $unmatchedId);
        $invoice = ORM::for_table('saas_invoices')->find_one((int) $invoiceId);
        if (!$unmatched || $unmatched['resolved_at']) {
            throw new Exception('Unmatched payment not found or already resolved.');
        }
        if (!$invoice) {
            throw new Exception('Invoice not found.');
        }
        $payload = json_decode((string) $unmatched['raw_payload'], true) ?: [];
        $payment = self::recordInvoicePayment($invoice, [
            'transaction_code' => (string) ($unmatched['transaction_code'] ?: ('UNMATCHED-' . $unmatched['id'])),
            'phone' => (string) $unmatched['phone'],
            'amount' => (float) $unmatched['amount'],
            'account_reference' => (string) $unmatched['account_reference'],
            'payment_provider' => (string) $unmatched['source'],
            'raw_payload' => json_encode(self::safePayload($payload)),
            'status' => 'success',
            'matched_status' => 'manual_reconciled',
        ]);
        self::refreshInvoiceSettlement($invoice, (int) $adminId, true);
        $unmatched->resolved_by = (int) $adminId;
        $unmatched->resolved_at = date('Y-m-d H:i:s');
        $unmatched->save();
        Tenant::audit('saas.unmatched_payment_reconciled', 'Unmatched payment reconciled to invoice ' . $invoice['invoice_number'], 'unmatched_payment', (string) $unmatched->id(), (int) $invoice['tenant_id'], (int) $adminId, ['payment_id' => (int) $payment['id']]);
        return $payment;
    }

    public static function voidInvoicePayment($paymentId, $note, $adminId)
    {
        self::installSchema();
        $payment = ORM::for_table('saas_invoice_payments')->find_one((int) $paymentId);
        if (!$payment) {
            throw new Exception('Payment record not found.');
        }
        if ((string) $payment['status'] === 'void') {
            return $payment;
        }
        $invoice = ORM::for_table('saas_invoices')->find_one((int) $payment['invoice_id']);
        if (!$invoice) {
            throw new Exception('Linked invoice not found.');
        }
        $payload = json_decode((string) $payment['raw_payload'], true) ?: [];
        $payload['void_note'] = substr(trim(strip_tags((string) $note)), 0, 255);
        $payload['voided_by'] = (int) $adminId;
        $payload['voided_at'] = date('Y-m-d H:i:s');
        $payment->status = 'void';
        $payment->matched_status = 'void_note';
        $payment->raw_payload = json_encode(self::safePayload($payload));
        $payment->save();
        self::refreshInvoiceSettlement($invoice, (int) $adminId, false);
        Tenant::audit('saas.invoice_payment_voided', 'SaaS invoice payment void note recorded.', 'saas_invoice_payment', (string) $payment->id(), (int) $invoice['tenant_id'], (int) $adminId);
        return $payment;
    }

    public static function handlePaymentCallback()
    {
        self::installSchema();
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            self::jsonPaymentResponse('failed', 'Invalid JSON payload.', '', 400);
        }

        $settings = self::paymentSettings(true);
        if (!self::validateSaasCallbackSignature($raw, $settings)) {
            self::storeUnmatchedPayment('saas_callback', self::normalizePaymentPayload($payload), $payload, 'Invalid callback signature.');
            Tenant::audit('saas.callback_rejected', 'SaaS payment callback rejected: invalid signature.', 'payment_callback', '', Tenant::currentId(), 0);
            self::jsonPaymentResponse('failed', 'Invalid signature.', '', 401);
        }

        $normalized = self::normalizePaymentPayload($payload);
        if ($normalized['account_reference'] === '') {
            self::storeUnmatchedPayment('saas_callback', $normalized, $payload, 'Missing account reference.');
            self::jsonPaymentResponse('failed', 'Account reference is missing.', '', 200);
        }

        $saasPrefix = (string) ($settings['account_prefix'] ?: 'FASTNETPAY_');
        if (strpos($normalized['account_reference'], $saasPrefix) === 0) {
            $result = self::settleSaasInvoicePayment($normalized, $payload, $settings);
            self::jsonPaymentResponse($result['status'], $result['message'], $result['reference'] ?? $normalized['account_reference'], $result['http_status'] ?? 200, $result);
        }

        $gateway = self::tenantGatewayForReference($normalized['account_reference']);
        if ($gateway) {
            $result = self::settleTenantCustomerPayment($normalized, $payload, $gateway);
            self::jsonPaymentResponse($result['status'], $result['message'], $result['reference'] ?? $normalized['account_reference'], $result['http_status'] ?? 200, $result);
        }

        self::storeUnmatchedPayment('saas_callback', $normalized, $payload, 'Account prefix is not registered for SaaS or tenant customer payments.');
        self::jsonPaymentResponse('failed', 'Account reference is not registered in FASTNETPAY.', $normalized['account_reference'], 200, [
            'fastnetpay_status' => 'unmatched',
        ]);
    }

    public static function settleSaasInvoicePayment($normalized, $payload, $settings = null)
    {
        $settings = $settings ?: self::paymentSettings(true);
        $transactionCode = self::transactionCode($normalized, $payload);
        $existing = ORM::for_table('saas_invoice_payments')->where('transaction_code', $transactionCode)->find_one();
        if ($existing) {
            return [
                'status' => 'success',
                'message' => 'Duplicate callback accepted. Payment was already recorded.',
                'reference' => (string) $existing['account_reference'],
                'fastnetpay_status' => 'duplicate',
            ];
        }

        $invoice = self::invoiceFromReference($normalized['account_reference'], (string) $settings['account_prefix']);
        if (!$invoice) {
            self::storeUnmatchedPayment('saas_invoice', $normalized + ['transaction_code' => $transactionCode], $payload, 'No matching tenant invoice was found.');
            return [
                'status' => 'failed',
                'message' => 'Invoice not found for this account reference.',
                'reference' => $normalized['account_reference'],
                'fastnetpay_status' => 'unmatched',
            ];
        }

        if (!$normalized['paid']) {
            self::recordInvoicePayment($invoice, [
                'transaction_code' => $transactionCode,
                'phone' => $normalized['phone'],
                'amount' => $normalized['amount'],
                'account_reference' => $normalized['account_reference'],
                'payment_provider' => $settings['provider'],
                'raw_payload' => json_encode(self::safePayload($payload)),
                'status' => 'failed',
                'matched_status' => 'failed_callback',
            ]);
            return [
                'status' => 'success',
                'message' => 'Failed payment callback acknowledged.',
                'reference' => $normalized['account_reference'],
                'fastnetpay_status' => 'payment_failed',
            ];
        }

        $amount = (float) $normalized['amount'];
        if ($amount <= 0) {
            self::storeUnmatchedPayment('saas_invoice', $normalized + ['transaction_code' => $transactionCode], $payload, 'Payment amount is missing or zero.');
            return [
                'status' => 'failed',
                'message' => 'Payment amount is invalid.',
                'reference' => $normalized['account_reference'],
                'fastnetpay_status' => 'invalid_amount',
            ];
        }

        $beforeBalance = self::invoiceBalance($invoice);
        $matchedStatus = $amount >= $beforeBalance ? ($amount > $beforeBalance ? 'overpaid' : 'full') : 'partial';
        $payment = self::recordInvoicePayment($invoice, [
            'transaction_code' => $transactionCode,
            'phone' => $normalized['phone'],
            'amount' => $amount,
            'account_reference' => $normalized['account_reference'],
            'payment_provider' => $settings['provider'],
            'raw_payload' => json_encode(self::safePayload($payload)),
            'status' => 'success',
            'matched_status' => $matchedStatus,
        ]);
        if ((int) $settings['auto_settle'] !== 1) {
            Tenant::audit('saas.invoice_payment_recorded', 'SaaS invoice payment recorded for manual settlement: ' . $transactionCode, 'saas_invoice_payment', (string) $payment->id(), (int) $invoice['tenant_id'], 0);
            return [
                'status' => 'success',
                'message' => 'Payment recorded for manual settlement.',
                'reference' => $normalized['account_reference'],
                'fastnetpay_status' => 'recorded_manual_settlement',
                'invoice_id' => (int) $invoice['id'],
                'balance_due' => self::invoiceBalance($invoice),
            ];
        }
        $fresh = ORM::for_table('saas_invoices')->find_one((int) $invoice['id']);
        $settled = self::refreshInvoiceSettlement($fresh, 0, (int) $settings['auto_restore'] === 1);
        Tenant::audit('saas.invoice_payment_received', 'SaaS invoice payment received: ' . $transactionCode, 'saas_invoice_payment', (string) $payment->id(), (int) $invoice['tenant_id'], 0, [
            'invoice_id' => (int) $invoice['id'],
            'amount' => $amount,
            'matched_status' => $matchedStatus,
        ]);
        return [
            'status' => 'success',
            'message' => $settled ? 'Payment settled successfully and tenant access restored if needed.' : 'Partial payment recorded successfully.',
            'reference' => $normalized['account_reference'],
            'fastnetpay_status' => $settled ? 'settled' : 'partial',
            'invoice_id' => (int) $invoice['id'],
            'balance_due' => self::invoiceBalance(ORM::for_table('saas_invoices')->find_one((int) $invoice['id'])),
        ];
    }

    public static function settleTenantCustomerPayment($normalized, $payload, $gateway)
    {
        $tenantId = (int) $gateway['tenant_id'];
        $transactionCode = self::transactionCode($normalized, $payload);
        $existing = ORM::for_table('tenant_customer_payments')->where('transaction_code', $transactionCode)->find_one();
        if ($existing) {
            return [
                'status' => 'success',
                'message' => 'Duplicate customer payment callback accepted.',
                'reference' => (string) $existing['account_reference'],
                'fastnetpay_status' => 'duplicate',
            ];
        }

        $jovi = null;
        if (class_exists('JoviPay')) {
            JoviPay::installSchema();
            $query = ORM::for_table('jovipay_transactions')->where('account_reference', $normalized['account_reference']);
            if (Tenant::hasColumn('jovipay_transactions', 'tenant_id')) {
                $query->where('tenant_id', $tenantId);
            }
            $jovi = $query->find_one();
        }

        $row = ORM::for_table('tenant_customer_payments')->create();
        $row->tenant_id = $tenantId;
        $row->router_id = $jovi ? (int) $jovi['router_id'] : null;
        $row->customer_id = $jovi ? (int) $jovi['customer_id'] : null;
        $row->package_id = $jovi ? (int) $jovi['package_id'] : null;
        $row->session_id = self::clean((string) ($payload['event_id'] ?? ''), 120);
        $row->transaction_code = $transactionCode;
        $row->phone = $normalized['phone'];
        $row->amount = $normalized['amount'];
        $row->account_reference = $normalized['account_reference'];
        $row->gateway_id = (int) $gateway['id'];
        $row->raw_payload = json_encode(self::safePayload($payload));
        $row->status = $normalized['paid'] ? 'success' : 'failed';
        $row->activation_status = 'pending';
        $row->received_at = date('Y-m-d H:i:s');
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();

        if (!$normalized['paid']) {
            $row->activation_status = 'failed';
            $row->save();
            return ['status' => 'success', 'message' => 'Failed customer payment callback acknowledged.', 'reference' => $normalized['account_reference'], 'fastnetpay_status' => 'payment_failed'];
        }
        if (!$jovi) {
            $row->activation_status = 'unmatched';
            $row->save();
            Tenant::audit('tenant.customer_payment_unmatched', 'Customer payment received but no pending package transaction matched.', 'tenant_customer_payment', (string) $row->id(), $tenantId, 0);
            return ['status' => 'success', 'message' => 'Customer payment recorded for manual reconciliation.', 'reference' => $normalized['account_reference'], 'fastnetpay_status' => 'customer_unmatched'];
        }

        $activation = self::activateJoviCustomerTransaction($jovi, $normalized);
        $row->activation_status = $activation['status'] === 'success' || $activation['status'] === 'already_processed' ? 'activated' : 'failed';
        $row->save();
        return [
            'status' => 'success',
            'message' => $activation['message'],
            'reference' => $normalized['account_reference'],
            'fastnetpay_status' => $activation['status'],
        ];
    }

    public static function runCron()
    {
        self::installSchema();
        $month = date('Y-m');
        $today = (int) date('j');
        $billingDay = (int) self::setting('billing_day', '23');
        $graceDay = (int) self::setting('grace_day', '28');
        $generated = 0;
        $suspended = 0;

        foreach (ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('billing_exempt', 0)->find_many() as $tenant) {
            if (self::setting('invoice_generation_mode', 'manual') === 'automatic' && $today >= $billingDay) {
                $invoice = self::generateInvoice((int) $tenant['id'], $month);
                if ($invoice) {
                    $generated++;
                }
            }
            if (self::setting('auto_suspend_unpaid', 'yes') === 'yes' && $today > $graceDay) {
                $invoice = self::latestUnpaidInvoice((int) $tenant['id']);
                if ($invoice && (string) $tenant['status'] !== 'suspended') {
                    self::suspendTenant((int) $tenant['id'], (int) $invoice['id'], 0, 'Automatic SaaS billing suspension.');
                    $suspended++;
                }
            }
        }

        return ['generated' => $generated, 'suspended' => $suspended];
    }

    public static function analytics()
    {
        self::installSchema();
        $month = date('Y-m');
        $tenantsTotal = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->count();
        $activeTenants = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('status', 'active')->count();
        $suspendedTenants = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('status', 'suspended')->count();
        $trialTenants = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('status', 'trial')->count();
        $newThisMonth = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where_gte('created_at', $month . '-01 00:00:00')->count();
        $billingExempt = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('billing_exempt', 1)->count();

        $routersTotal = (int) ORM::for_table('tbl_routers')->count();
        $onlineRouters = (int) ORM::for_table('tbl_routers')->where('status', 'Online')->count();
        $offlineRouters = max(0, $routersTotal - $onlineRouters);
        $vpnModes = [];
        foreach (['local', 'wireguard', 'sstp'] as $mode) {
            $vpnModes[$mode] = Tenant::hasColumn('tbl_routers', 'vpn_mode') ? (int) ORM::for_table('tbl_routers')->where('vpn_mode', $mode)->count() : 0;
        }

        $hotspot = (int) ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('type', 'Hotspot')->where_gte('expiration', date('Y-m-d'))->count();
        $pppoe = (int) ORM::for_table('tbl_user_recharges')->where('status', 'on')->where_in('type', ['PPPOE', 'PPPoE'])->where_gte('expiration', date('Y-m-d'))->count();
        $invoiceMonth = ORM::for_table('saas_invoices')->where('billing_month', $month);
        $invoiced = (float) $invoiceMonth->sum('total_due');
        $paid = (float) ORM::for_table('saas_invoices')->where('billing_month', $month)->where('status', 'paid')->sum('total_due');
        $paymentsCollected = (float) ORM::for_table('saas_invoice_payments')->where_in('status', ['success', 'received'])->sum('amount');
        $partialPayments = (int) ORM::for_table('saas_invoices')->where('status', 'partial')->count();
        $unmatchedPayments = (int) ORM::for_table('unmatched_payments')->where_null('resolved_at')->count();
        $tenantGatewayCount = (int) ORM::for_table('tenant_payment_gateways')->where('is_enabled', 1)->count();
        $unpaid = max(0, $invoiced - $paid);
        $overdue = (float) ORM::for_table('saas_invoices')->where_not_equal('status', 'paid')->where_lt('grace_until', date('Y-m-d'))->sum('total_due');
        $expected = (float) ORM::for_table('tenant_billing_snapshots')->where('billing_month', $month)->sum('amount_due');
        if ($expected <= 0) {
            $expected = $invoiced;
        }

        return [
            'tenants' => ['total' => $tenantsTotal, 'active' => $activeTenants, 'suspended' => $suspendedTenants, 'trial' => $trialTenants, 'new_this_month' => $newThisMonth, 'billing_exempt' => $billingExempt],
            'routers' => ['total' => $routersTotal, 'online' => $onlineRouters, 'offline' => $offlineRouters, 'vpn_modes' => $vpnModes],
            'clients' => ['hotspot' => $hotspot, 'pppoe' => $pppoe, 'total' => $hotspot + $pppoe],
            'financial' => ['expected' => $expected, 'invoiced' => $invoiced, 'paid' => $paid, 'payments_collected' => $paymentsCollected, 'unpaid' => $unpaid, 'overdue' => $overdue, 'partial_payments' => $partialPayments, 'unmatched_payments' => $unmatchedPayments, 'tenant_gateway_count' => $tenantGatewayCount],
            'billing_health' => [
                'due_today' => (int) ORM::for_table('saas_invoices')->where_not_equal('status', 'paid')->where('due_date', date('Y-m-d'))->count(),
                'in_grace' => (int) ORM::for_table('saas_invoices')->where_not_equal('status', 'paid')->where_gte('grace_until', date('Y-m-d'))->where_lte('due_date', date('Y-m-d'))->count(),
                'suspended' => $suspendedTenants,
                'gateway_health' => self::paymentSettings()['enabled'] ? 'enabled' : 'disabled',
            ],
            'recent_invoices' => ORM::for_table('saas_invoices')->order_by_desc('id')->limit(8)->find_many(),
            'top_tenants' => self::topTenantsByUsers(),
        ];
    }

    public static function smsReady()
    {
        global $config;
        if (function_exists('talksasa_config') && (talksasa_config('talksasa_api_token', '') !== '') && talksasa_config('talksasa_sender_id', '') !== '') {
            return true;
        }
        return !empty($config['sms_url']) && (strpos((string) $config['sms_url'], 'http') === 0 || (string) $config['sms_url'] === 'talksasa');
    }

    public static function superAdmin2FAEnabled($adminId)
    {
        $row = ORM::for_table('superadmin_2fa_settings')->where('admin_id', (int) $adminId)->find_one();
        return $row ? (int) $row['enabled'] === 1 : self::setting('superadmin_2fa_enabled', 'no') === 'yes';
    }

    public static function requiresSuperAdmin2FA($admin)
    {
        if (!$admin || (string) $admin['user_type'] !== 'SuperAdmin') {
            return false;
        }
        return self::superAdmin2FAEnabled((int) $admin['id']) && self::smsReady();
    }

    public static function save2FASettings($adminId, $enabled, $actorId)
    {
        if ($enabled && !self::smsReady()) {
            throw new Exception('Configure SMS gateway before enabling SuperAdmin 2FA.');
        }
        $row = ORM::for_table('superadmin_2fa_settings')->where('admin_id', (int) $adminId)->find_one();
        if (!$row) {
            $row = ORM::for_table('superadmin_2fa_settings')->create();
            $row->admin_id = (int) $adminId;
            $row->created_at = date('Y-m-d H:i:s');
        }
        $row->enabled = $enabled ? 1 : 0;
        $row->remember_days = 0;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        self::saveSetting('superadmin_2fa_enabled', $enabled ? 'yes' : 'no');
        Tenant::audit($enabled ? 'superadmin.2fa_enabled' : 'superadmin.2fa_disabled', 'SuperAdmin 2FA setting changed.', 'user', (string) $adminId, Tenant::currentId(), $actorId);
    }

    public static function issueSuperAdminOtp($admin)
    {
        if (!self::smsReady()) {
            throw new Exception('SMS gateway is not configured, so SuperAdmin 2FA cannot send OTP.');
        }
        $phone = trim((string) ($admin['phone'] ?? ''));
        if ($phone === '') {
            throw new Exception('SuperAdmin has no phone number for 2FA OTP.');
        }
        $otp = (string) random_int(100000, 999999);
        $row = ORM::for_table('superadmin_2fa_otps')->create();
        $row->admin_id = (int) $admin['id'];
        $row->otp_hash = hash('sha256', $otp);
        $row->expires_at = date('Y-m-d H:i:s', time() + 600);
        $row->attempts = 0;
        $row->ip = self::clientIp();
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
        Message::sendSMS($phone, 'FASTNETPAY SuperAdmin OTP: ' . $otp . '. It expires in 10 minutes.');
        Tenant::audit('superadmin.2fa_otp_sent', 'SuperAdmin 2FA OTP sent.', 'user', (string) $admin['id'], Tenant::currentId(), (int) $admin['id']);
        return true;
    }

    public static function verifySuperAdminOtp($adminId, $otp)
    {
        $otp = preg_replace('/[^0-9]/', '', (string) $otp);
        $row = ORM::for_table('superadmin_2fa_otps')
            ->where('admin_id', (int) $adminId)
            ->where_null('verified_at')
            ->where_gt('expires_at', date('Y-m-d H:i:s'))
            ->order_by_desc('id')
            ->find_one();
        if (!$row) {
            Tenant::audit('superadmin.2fa_failed', 'No active SuperAdmin OTP found.', 'user', (string) $adminId, Tenant::currentId(), (int) $adminId);
            return false;
        }
        $row->attempts = (int) $row['attempts'] + 1;
        if ((int) $row['attempts'] > 5) {
            $row->save();
            return false;
        }
        if (!hash_equals((string) $row['otp_hash'], hash('sha256', $otp))) {
            $row->save();
            Tenant::audit('superadmin.2fa_failed', 'Invalid SuperAdmin OTP.', 'user', (string) $adminId, Tenant::currentId(), (int) $adminId);
            return false;
        }
        $row->verified_at = date('Y-m-d H:i:s');
        $row->save();
        Tenant::audit('superadmin.2fa_verified', 'SuperAdmin 2FA verified.', 'user', (string) $adminId, Tenant::currentId(), (int) $adminId);
        return true;
    }

    public static function latestUnpaidInvoice($tenantId)
    {
        return ORM::for_table('saas_invoices')
            ->where('tenant_id', (int) $tenantId)
            ->where_not_equal('status', 'paid')
            ->order_by_desc('id')
            ->find_one();
    }

    public static function invoices($limit = 100)
    {
        return ORM::for_table('saas_invoices')->order_by_desc('id')->limit((int) $limit)->find_many();
    }

    public static function invoiceItems($invoiceId)
    {
        return ORM::for_table('saas_invoice_items')->where('invoice_id', (int) $invoiceId)->order_by_asc('id')->find_many();
    }

    private static function migratePaymentSchema()
    {
        self::ensureColumn('saas_invoices', 'amount_paid', 'DECIMAL(15,2) NOT NULL DEFAULT 0');
        self::ensureColumn('saas_invoices', 'balance_due', 'DECIMAL(15,2) NOT NULL DEFAULT 0');
        self::ensureColumn('saas_invoices', 'last_payment_at', 'DATETIME NULL');
        $type = self::columnType('saas_invoices', 'status');
        if ($type !== '' && strpos($type, "'partial'") === false) {
            try {
                ORM::raw_execute("ALTER TABLE saas_invoices MODIFY status ENUM('draft','issued','partial','paid','overdue','void') NOT NULL DEFAULT 'issued'");
            } catch (Throwable $e) {
                _log('FASTNETPAY SaaS invoice status migration failed: ' . $e->getMessage(), 'SaaS', 0);
            }
        }
        self::ensureColumn('saas_payment_settings', 'paybill_number', 'VARCHAR(80) NULL');
        self::ensureColumn('saas_payment_settings', 'till_number', 'VARCHAR(80) NULL');
        self::ensureColumn('tenant_payment_gateways', 'router_id', 'INT UNSIGNED NULL');
        self::ensureColumn('tenant_payment_gateways', 'payment_label', 'VARCHAR(140) NULL');
        self::ensureColumn('tenant_payment_gateways', 'settlement_account_name', 'VARCHAR(160) NULL');
        self::ensureIndex('saas_invoices', 'idx_tenant_status_due', ['tenant_id', 'status', 'due_date']);
        self::ensureIndex('saas_invoices', 'idx_tenant_month', ['tenant_id', 'billing_month']);
        self::ensureIndex('saas_invoice_payments', 'idx_tenant_transaction', ['tenant_id', 'transaction_code']);
        self::ensureIndex('saas_invoice_payments', 'idx_invoice_status', ['invoice_id', 'status']);
        self::ensureIndex('tenant_customer_payments', 'idx_tenant_status_created', ['tenant_id', 'status', 'created_at']);
        self::ensureIndex('tenant_customer_payments', 'idx_gateway_status', ['gateway_id', 'status']);
        self::ensureIndex('unmatched_payments', 'idx_source_resolved_created', ['source', 'resolved_at', 'created_at']);
        self::ensureIndex('tenant_payment_gateways', 'idx_tenant_default_enabled', ['tenant_id', 'is_default', 'is_enabled']);
    }

    private static function recordInvoicePayment($invoice, $data)
    {
        $code = self::clean((string) ($data['transaction_code'] ?? ''), 120);
        if ($code === '') {
            $code = 'PAY-' . sha1(json_encode($data) . microtime(true));
        }
        $existing = ORM::for_table('saas_invoice_payments')->where('transaction_code', $code)->find_one();
        if ($existing) {
            return $existing;
        }
        $row = ORM::for_table('saas_invoice_payments')->create();
        $row->tenant_id = (int) $invoice['tenant_id'];
        $row->invoice_id = (int) $invoice['id'];
        $row->transaction_code = $code;
        $row->phone = self::clean((string) ($data['phone'] ?? ''), 40);
        $row->amount = self::money($data['amount'] ?? 0);
        $row->account_reference = self::cleanReference((string) ($data['account_reference'] ?? ''));
        $row->payment_provider = self::clean((string) ($data['payment_provider'] ?? 'manual'), 64);
        $row->raw_payload = (string) ($data['raw_payload'] ?? '');
        $row->status = self::clean((string) ($data['status'] ?? 'received'), 32);
        $row->matched_status = self::clean((string) ($data['matched_status'] ?? 'pending'), 32);
        $row->received_at = date('Y-m-d H:i:s');
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
        return $row;
    }

    private static function refreshInvoiceSettlement($invoice, $adminId = 0, $restoreTenant = true)
    {
        if (!$invoice) {
            return false;
        }
        $paid = self::invoicePaidAmount((int) $invoice['id']);
        $balance = max(0, (float) $invoice['total_due'] - $paid);
        $settled = $balance <= 0.0001;
        $invoice->amount_paid = number_format($paid, 2, '.', '');
        $invoice->balance_due = number_format($balance, 2, '.', '');
        $invoice->last_payment_at = date('Y-m-d H:i:s');
        if ($settled) {
            $invoice->status = 'paid';
        } elseif ($paid > 0) {
            $invoice->status = 'partial';
        } elseif (in_array((string) $invoice['status'], ['paid', 'partial'], true)) {
            $invoice->status = 'issued';
            $invoice->paid_at = null;
        }
        if ($settled) {
            $invoice->paid_at = $invoice['paid_at'] ?: date('Y-m-d H:i:s');
        }
        $invoice->updated_at = date('Y-m-d H:i:s');
        $invoice->save();
        if ($settled && $restoreTenant) {
            self::restoreTenant((int) $invoice['tenant_id'], (int) $adminId, 'SaaS invoice settled: ' . $invoice['invoice_number']);
            Tenant::audit('saas.invoice_settled', 'SaaS invoice settled and tenant restored: ' . $invoice['invoice_number'], 'invoice', (string) $invoice['id'], (int) $invoice['tenant_id'], (int) $adminId);
        }
        return $settled;
    }

    private static function invoiceFromReference($reference, $prefix)
    {
        $reference = self::cleanReference($reference);
        if ($reference === '') {
            return null;
        }
        $invoice = ORM::for_table('saas_invoices')->where('invoice_number', $reference)->find_one();
        if ($invoice) {
            return $invoice;
        }
        foreach (ORM::for_table('saas_invoices')->where_not_equal('status', 'paid')->order_by_desc('id')->find_many() as $row) {
            if (strpos($reference, (string) $row['invoice_number']) !== false) {
                return $row;
            }
        }
        $slug = $reference;
        if ($prefix !== '' && strpos($slug, $prefix) === 0) {
            $slug = substr($slug, strlen($prefix));
        }
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9_-]/', '', $slug), '_-'));
        if ($slug === '') {
            return null;
        }
        $tenant = ORM::for_table('tenants')->where('slug', $slug)->find_one();
        if (!$tenant) {
            return null;
        }
        $invoice = self::latestUnpaidInvoice((int) $tenant['id']);
        if (!$invoice) {
            $invoice = self::generateInvoice((int) $tenant['id'], date('Y-m'));
        }
        return $invoice;
    }

    private static function tenantGatewayForReference($reference)
    {
        $reference = self::cleanReference($reference);
        if ($reference === '') {
            return null;
        }
        foreach (ORM::for_table('tenant_payment_gateways')->where('is_enabled', 1)->order_by_desc('is_default')->find_many() as $gateway) {
            $prefix = (string) $gateway['account_prefix'];
            if ($prefix !== '' && strpos($reference, $prefix) === 0) {
                return $gateway;
            }
        }
        return null;
    }

    private static function storeUnmatchedPayment($source, $normalized, $payload, $reason)
    {
        try {
            $code = self::transactionCode($normalized, $payload);
            if ($code !== '') {
                $existing = ORM::for_table('unmatched_payments')->where('transaction_code', $code)->where_null('resolved_at')->find_one();
                if ($existing) {
                    return $existing;
                }
            }
            $row = ORM::for_table('unmatched_payments')->create();
            $row->source = self::clean((string) $source, 80);
            $row->account_reference = self::cleanReference((string) ($normalized['account_reference'] ?? ''));
            $row->transaction_code = self::clean($code, 120);
            $row->phone = self::clean((string) ($normalized['phone'] ?? ''), 40);
            $row->amount = self::money($normalized['amount'] ?? 0);
            $row->raw_payload = json_encode(self::safePayload($payload));
            $row->reason = substr(trim(strip_tags((string) $reason)), 0, 255);
            $row->created_at = date('Y-m-d H:i:s');
            $row->save();
            return $row;
        } catch (Throwable $e) {
            _log('FASTNETPAY unmatched payment store failed: ' . $e->getMessage(), 'SaaS', 0);
            return null;
        }
    }

    private static function activateJoviCustomerTransaction($tx, $normalized)
    {
        if (!$tx) {
            return ['status' => 'failed', 'message' => 'Pending customer transaction was not found.'];
        }
        if ($tx['activation_status'] === 'activated') {
            return ['status' => 'already_processed', 'message' => 'Customer package was already activated.'];
        }
        $tenantId = (int) ($tx['tenant_id'] ?? 0);
        $plan = ORM::for_table('tbl_plans')->find_one((int) $tx['package_id']);
        $router = ORM::for_table('tbl_routers')->find_one((int) $tx['router_id']);
        $customer = ORM::for_table('tbl_customers')->find_one((int) $tx['customer_id']);
        if (!$plan || !$router || !$customer) {
            $tx->activation_status = 'failed';
            $tx->updated_at = date('Y-m-d H:i:s');
            $tx->save();
            return ['status' => 'failed', 'message' => 'Missing plan, router, or customer record.'];
        }
        foreach (['plan' => $plan, 'router' => $router, 'customer' => $customer] as $label => $row) {
            if ($tenantId > 0 && isset($row['tenant_id']) && (int) $row['tenant_id'] !== $tenantId) {
                $tx->activation_status = 'failed';
                $tx->updated_at = date('Y-m-d H:i:s');
                $tx->save();
                Tenant::audit('tenant.customer_payment_cross_access_blocked', 'Customer activation blocked due to tenant mismatch.', $label, (string) $row['id'], $tenantId);
                return ['status' => 'failed', 'message' => 'Payment could not be activated for this tenant.'];
            }
        }
        $expected = (float) self::money($plan['price']);
        $paid = (float) self::money($normalized['amount'] ?: $tx['amount']);
        if (abs($paid - $expected) > 0.01) {
            $tx->activation_status = 'failed';
            $tx->updated_at = date('Y-m-d H:i:s');
            $tx->save();
            return ['status' => 'failed', 'message' => 'Paid amount does not match package price.'];
        }
        global $trx;
        $payment = $tx['payment_gateway_id'] ? ORM::for_table('tbl_payment_gateway')->find_one((int) $tx['payment_gateway_id']) : null;
        if ($payment && (int) $payment['status'] === 2) {
            $tx->status = 'success';
            $tx->activation_status = 'activated';
            $tx->updated_at = date('Y-m-d H:i:s');
            $tx->save();
            return ['status' => 'already_processed', 'message' => 'Payment was already activated.'];
        }
        $trx = $payment ?: $tx;
        $invoiceNo = Package::rechargeUser((int) $customer['id'], $router['name'], (int) $plan['id'], 'jovipay', 'Jovi-Pay M-Pesa STK Push', 'Jovi-Pay receipt ' . ($normalized['receipt'] ?: $tx['mpesa_receipt_number']));
        if (!$invoiceNo) {
            $tx->activation_status = 'failed';
            $tx->updated_at = date('Y-m-d H:i:s');
            $tx->save();
            return ['status' => 'failed', 'message' => 'Payment received, but package activation failed.'];
        }
        if ($payment) {
            $payment->pg_paid_response = json_encode(self::safePayload($normalized));
            $payment->payment_method = 'M-Pesa';
            $payment->payment_channel = 'Jovi-Pay STK Push';
            $payment->paid_date = date('Y-m-d H:i:s');
            $payment->trx_invoice = $invoiceNo;
            $payment->status = 2;
            $payment->save();
        }
        $tx->status = 'success';
        $tx->activation_status = 'activated';
        $tx->mpesa_receipt_number = $normalized['receipt'] ?: $tx['mpesa_receipt_number'];
        $tx->callback_received_at = date('Y-m-d H:i:s');
        $tx->updated_at = date('Y-m-d H:i:s');
        $tx->save();
        Tenant::audit('tenant.customer_payment_activated', 'Customer internet package activated from payment callback.', 'jovipay_transaction', (string) $tx['id'], $tenantId);
        return ['status' => 'success', 'message' => 'Payment confirmed and internet package activated.'];
    }

    private static function normalizePaymentPayload($payload)
    {
        $resultCode = self::recursiveValue($payload, ['ResultCode', 'result_code', 'code']);
        $receipt = self::recursiveValue($payload, ['MpesaReceiptNumber', 'mpesa_receipt_number', 'receipt', 'transaction_code', 'TransID', 'trans_id']);
        $status = strtolower((string) self::recursiveValue($payload, ['status', 'ResultDesc', 'result_desc', 'event']));
        $paid = ((string) $resultCode === '0') || $receipt !== '' || in_array($status, ['success', 'paid', 'completed', 'mpesa.c2b.confirmed'], true);
        if (in_array($status, ['failed', 'cancelled', 'canceled', 'timeout', 'error'], true) || ((string) $resultCode !== '' && (string) $resultCode !== '0')) {
            $paid = false;
        }
        return [
            'event_id' => self::clean(self::recursiveValue($payload, ['event_id', 'X-Jovi-Event-ID']), 120),
            'account_reference' => self::cleanReference(self::recursiveValue($payload, ['account_reference', 'AccountReference', 'BillRefNumber', 'bill_ref_number', 'reference', 'account', 'AccountNo'])),
            'phone' => self::cleanPhone(self::recursiveValue($payload, ['PhoneNumber', 'phone', 'MSISDN', 'msisdn'])),
            'amount' => self::money(self::recursiveValue($payload, ['Amount', 'amount', 'TransAmount', 'trans_amount'])),
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

    private static function validateSaasCallbackSignature($raw, $settings)
    {
        $secret = trim((string) ($settings['callback_secret'] ?? ''));
        if ($secret === '') {
            return true;
        }
        $provided = self::headerValue(['X-Jovi-Signature', 'X-JoviPay-Signature', 'X-Fastnetpay-Signature', 'X-Signature']);
        if ($provided === '') {
            $shared = self::headerValue(['X-Jovi-Secret', 'X-JoviPay-Secret']) ?: ($_GET['secret'] ?? '');
            return $shared !== '' && hash_equals($secret, (string) $shared);
        }
        $provided = preg_replace('/^sha256=/i', '', $provided);
        $timestamp = self::headerValue(['X-Jovi-Timestamp']);
        $expected = $timestamp !== '' ? hash_hmac('sha256', $raw . $timestamp, $secret) : '';
        if ($expected !== '' && hash_equals($expected, $provided)) {
            return true;
        }
        return hash_equals(hash_hmac('sha256', $raw, $secret), $provided);
    }

    private static function transactionCode($normalized, $payload)
    {
        $code = self::clean((string) ($normalized['receipt'] ?? ''), 120);
        if ($code === '') {
            $code = self::clean((string) ($normalized['checkout_request_id'] ?? ''), 120);
        }
        if ($code === '') {
            $code = self::clean((string) ($normalized['merchant_request_id'] ?? ''), 120);
        }
        if ($code === '') {
            $code = self::clean((string) ($payload['event_id'] ?? $normalized['event_id'] ?? ''), 120);
        }
        if ($code === '') {
            $code = 'CB-' . sha1(json_encode(self::safePayload($payload)) . '|' . ($normalized['account_reference'] ?? '') . '|' . ($normalized['amount'] ?? '0'));
        }
        return $code;
    }

    private static function jsonPaymentResponse($status, $message, $reference = '', $httpStatus = 200, $extra = [])
    {
        http_response_code((int) $httpStatus);
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo json_encode(array_merge([
            'status' => $status === 'success' ? 'success' : 'failed',
            'message' => $message,
            'reference' => $reference,
        ], $extra));
        exit;
    }

    private static function gatewayCredentialsFromPost($existingEncrypted = '')
    {
        $existing = self::decryptGatewayCredentials($existingEncrypted);
        foreach (['api_base_url', 'stk_endpoint', 'api_token', 'api_secret', 'callback_secret', 'mini_app_id', 'passkey', 'consumer_key', 'consumer_secret'] as $key) {
            $value = trim((string) _post($key));
            if ($value !== '') {
                $existing[$key] = $key === 'api_base_url' || $key === 'stk_endpoint' ? self::cleanUrlOrPath($value) : $value;
            }
        }
        return array_filter($existing, function ($value) {
            return (string) $value !== '';
        });
    }

    private static function decryptGatewayCredentials($encrypted)
    {
        $plain = self::decryptSecret((string) $encrypted);
        $data = json_decode($plain, true);
        return is_array($data) ? $data : [];
    }

    private static function syncTenantGatewayToPublicSettings($gateway, $tenantId)
    {
        Tenant::saveSetting('payment', 'enabled', (int) $gateway['is_enabled'] === 1 ? 'yes' : 'no', false, $tenantId);
        Tenant::saveSetting('payment', 'active_gateways', 'jovipay', false, $tenantId);
        Tenant::saveSetting('payment', 'public_label', (string) ($gateway['payment_label'] ?: $gateway['gateway_name']), false, $tenantId);
        Tenant::saveSetting('payment', 'support_message', substr(strip_tags((string) $gateway['public_instructions']), 0, 255), false, $tenantId);
        Tenant::saveSetting('jovipay', 'account_prefix', (string) $gateway['account_prefix'], false, $tenantId);
        Tenant::saveSetting('jovipay', 'callback_url', (string) $gateway['callback_url'], false, $tenantId);
    }

    private static function maskGatewayCredentials($encrypted)
    {
        $credentials = self::decryptGatewayCredentials((string) $encrypted);
        $masked = [];
        foreach ($credentials as $key => $value) {
            $masked[] = $key . ': ' . self::maskSecret((string) $value);
        }
        return implode(', ', $masked);
    }

    private static function defaultSaasCallbackUrl()
    {
        return rtrim(APP_URL, '/') . '/?_route=api/saas/mpesa/callback';
    }

    private static function clean($value, $max = 120)
    {
        return substr(preg_replace('/[^A-Za-z0-9_.:@\\/-]+/', '', trim((string) $value)), 0, (int) $max);
    }

    private static function cleanReference($value)
    {
        return substr(strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $value))), 0, 160);
    }

    private static function cleanPrefix($value, $default = 'FASTNETPAY_')
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', (string) $value));
        return $prefix !== '' ? substr($prefix, 0, 80) : $default;
    }

    private static function cleanPhone($phone)
    {
        return substr(preg_replace('/\\D+/', '', (string) $phone), 0, 40);
    }

    private static function cleanUrl($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        return filter_var($value, FILTER_VALIDATE_URL) ? substr($value, 0, 255) : '';
    }

    private static function cleanUrlOrPath($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $value)) {
            return self::cleanUrl($value);
        }
        return substr(preg_replace('/[^A-Za-z0-9_.:\\/\\-?=&]+/', '', $value), 0, 255);
    }

    private static function safePayload($payload)
    {
        $sensitive = ['password', 'passkey', 'consumer_secret', 'api_secret', 'api_token', 'callback_secret', 'authorization', 'access_token'];
        if (is_array($payload)) {
            $clean = [];
            foreach ($payload as $key => $value) {
                $lower = strtolower((string) $key);
                $clean[$key] = in_array($lower, $sensitive, true) ? '***' : self::safePayload($value);
            }
            return $clean;
        }
        return is_string($payload) ? substr($payload, 0, 1000) : $payload;
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
                $plain = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', self::secretKey(), OPENSSL_RAW_DATA, substr($raw, 0, 16));
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
        $seed = ($config['api_key'] ?? '') . '|' . __DIR__ . '|FASTNETPAY_SAAS_PAYMENTS';
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

    private static function headerValue($names)
    {
        foreach ($names as $name) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            if (isset($_SERVER[$key])) {
                return trim((string) $_SERVER[$key]);
            }
        }
        return '';
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
            _log('FASTNETPAY SaaS schema check failed: ' . $e->getMessage(), 'SaaS', 0);
        }
    }

    private static function ensureIndex($table, $index, $columns)
    {
        try {
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
            $index = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $index);
            $columns = array_values(array_filter(array_map(function ($column) {
                return preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
            }, (array) $columns)));
            if ($table === '' || $index === '' || !$columns) {
                return;
            }
            foreach ($columns as $column) {
                if (self::columnType($table, $column) === '') {
                    return;
                }
            }
            $exists = ORM::for_table($table)->raw_query("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index])->find_one();
            if (!$exists) {
                ORM::raw_execute("ALTER TABLE `$table` ADD INDEX `$index` (`" . implode('`,`', $columns) . "`)");
            }
        } catch (Throwable $e) {
            _log('FASTNETPAY SaaS index check failed: ' . $e->getMessage(), 'SaaS', 0);
        }
    }

    private static function columnType($table, $column)
    {
        try {
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
            $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
            $row = ORM::for_table($table)->raw_query("SHOW COLUMNS FROM `$table` LIKE ?", [$column])->find_one();
            return $row ? (string) $row['Type'] : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    private static function bandFor($serviceType, $users)
    {
        $query = ORM::for_table('saas_billing_bands')
            ->where('service_type', $serviceType)
            ->where('enabled', 1)
            ->where_lte('min_users', (int) $users)
            ->order_by_desc('min_users');
        foreach ($query->find_many() as $band) {
            if ($band['max_users'] === null || $band['max_users'] === '' || (int) $users <= (int) $band['max_users']) {
                return $band;
            }
        }
        return null;
    }

    private static function line($type, $description, $quantity, $unit, $amount, $metadata = [])
    {
        return [
            'item_type' => $type,
            'description' => $description,
            'quantity' => (float) $quantity,
            'unit_price' => (float) $unit,
            'amount' => (float) $amount,
            'metadata' => is_object($metadata) && method_exists($metadata, 'as_array') ? $metadata->as_array() : $metadata,
        ];
    }

    private static function invoiceNumber($tenantId, $month)
    {
        return 'FNP-SaaS-' . str_pad((string) $tenantId, 4, '0', STR_PAD_LEFT) . '-' . str_replace('-', '', $month);
    }

    private static function saveSnapshot($tenantId, $preview, $invoiceId)
    {
        $row = ORM::for_table('tenant_billing_snapshots')->where('tenant_id', (int) $tenantId)->where('billing_month', $preview['billing_month'])->find_one();
        if (!$row) {
            $row = ORM::for_table('tenant_billing_snapshots')->create();
            $row->tenant_id = (int) $tenantId;
            $row->billing_month = $preview['billing_month'];
        }
        $row->active_hotspot = $preview['usage']['hotspot'];
        $row->active_pppoe = $preview['usage']['pppoe'];
        $row->routers_count = $preview['usage']['routers'];
        $row->amount_due = $preview['total_due'];
        $row->invoice_id = (int) $invoiceId;
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
    }

    private static function topTenantsByUsers()
    {
        try {
            $rows = ORM::for_table('tenant_billing_snapshots')->raw_query(
                "SELECT t.id tenant_id, t.name, t.slug, s.active_hotspot hotspot, s.active_pppoe pppoe, (s.active_hotspot + s.active_pppoe) users
                 FROM tenant_billing_snapshots s
                 INNER JOIN tenants t ON t.id = s.tenant_id
                 WHERE s.billing_month = ?
                 ORDER BY users DESC
                 LIMIT 8",
                [date('Y-m')]
            )->find_many();
            if ($rows) {
                $optimized = [];
                foreach ($rows as $row) {
                    $optimized[] = [
                        'tenant' => ['id' => (int) $row['tenant_id'], 'name' => (string) $row['name'], 'slug' => (string) $row['slug']],
                        'users' => (int) $row['users'],
                        'hotspot' => (int) $row['hotspot'],
                        'pppoe' => (int) $row['pppoe'],
                    ];
                }
                return $optimized;
            }
        } catch (Throwable $ignored) {
        }

        $rows = [];
        foreach (ORM::for_table('tenants')->where_not_equal('slug', 'main')->order_by_asc('name')->limit(30)->find_many() as $tenant) {
            $usage = self::usage((int) $tenant['id']);
            $rows[] = ['tenant' => $tenant, 'users' => $usage['hotspot'] + $usage['pppoe'], 'hotspot' => $usage['hotspot'], 'pppoe' => $usage['pppoe']];
        }
        usort($rows, function ($a, $b) {
            return $b['users'] <=> $a['users'];
        });
        return array_slice($rows, 0, 8);
    }

    private static function money($value)
    {
        return number_format((float) preg_replace('/[^0-9.]/', '', (string) $value), 2, '.', '');
    }

    private static function clientIp()
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'CLI'), 0, 80);
    }
}
