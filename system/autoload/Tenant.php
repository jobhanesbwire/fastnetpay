<?php

/**
 * FASTNETPAY SaaS tenancy helper.
 *
 * This layer is intentionally additive. It creates nullable tenant ownership
 * columns first, assigns existing rows to a default tenant, and exposes helper
 * methods that legacy controllers can adopt incrementally.
 */
class Tenant
{
    private static $booted = false;
    private static $current = null;
    private static $mode = 'main';
    private static $unknownTenantHost = '';
    private static $columnCache = [];
    private static $tenantCache = [];
    private static $settingsCache = [];
    const SCHEMA_VERSION = '2026-07-20-perf2';

    public static function boot(&$config = [])
    {
        if (self::$booted) {
            return;
        }

        self::installSchema();
        self::$unknownTenantHost = '';
        self::$current = self::resolveFromHost($config);
        if (self::$unknownTenantHost !== '') {
            self::$mode = 'unknown';
            $_SESSION['resolved_tenant_id'] = 0;
            $_SESSION['resolved_tenant_mode'] = self::$mode;
            self::$booted = true;
            return;
        }
        if (!self::$current) {
            self::$current = self::defaultTenant();
        }
        self::$mode = self::isDefaultTenant(self::$current) ? 'main' : 'tenant';
        $_SESSION['resolved_tenant_id'] = (int) self::$current['id'];
        $_SESSION['resolved_tenant_mode'] = self::$mode;
        self::$booted = true;
    }

    public static function installSchema()
    {
        if (class_exists('FastnetpayRuntime') && FastnetpayRuntime::schemaFresh('tenant', self::SCHEMA_VERSION, 86400)) {
            return;
        }

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenants (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            slug VARCHAR(80) NOT NULL,
            subdomain VARCHAR(190) NOT NULL,
            custom_domain VARCHAR(190) NULL,
            status ENUM('active','suspended','trial') NOT NULL DEFAULT 'trial',
            logo VARCHAR(255) NULL,
            primary_color VARCHAR(16) NOT NULL DEFAULT '#41a146',
            secondary_color VARCHAR(16) NOT NULL DEFAULT '#f9c02b',
            dark_primary_color VARCHAR(16) NOT NULL DEFAULT '#4ade80',
            dark_secondary_color VARCHAR(16) NOT NULL DEFAULT '#facc15',
            contact_phone VARCHAR(64) NULL,
            contact_email VARCHAR(160) NULL,
            billing_email VARCHAR(160) NULL,
            timezone VARCHAR(80) NOT NULL DEFAULT 'Africa/Nairobi',
            currency VARCHAR(16) NOT NULL DEFAULT 'KES',
            subscription_plan VARCHAR(80) NOT NULL DEFAULT 'Starter',
            subscription_status VARCHAR(32) NOT NULL DEFAULT 'trial',
            trial_ends_at DATETIME NULL,
            max_routers INT UNSIGNED NULL,
            max_clients INT UNSIGNED NULL,
            allowed_features MEDIUMTEXT NULL,
            billing_exempt TINYINT(1) NOT NULL DEFAULT 0,
            exemption_reason VARCHAR(255) NULL,
            internal_tenant TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY slug_unique (slug),
            UNIQUE KEY subdomain_unique (subdomain),
            UNIQUE KEY custom_domain_unique (custom_domain),
            INDEX status_idx (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenant_domains (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            domain VARCHAR(190) NOT NULL,
            domain_type ENUM('subdomain','custom') NOT NULL DEFAULT 'subdomain',
            status ENUM('pending','active','failed') NOT NULL DEFAULT 'pending',
            ssl_status VARCHAR(64) NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY domain_unique (domain),
            INDEX tenant_idx (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenant_settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            namespace VARCHAR(80) NOT NULL DEFAULT 'app',
            setting VARCHAR(160) NOT NULL,
            value MEDIUMTEXT NULL,
            is_secret TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY tenant_setting_unique (tenant_id, namespace, setting),
            INDEX tenant_idx (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenant_subscription_plans (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL,
            slug VARCHAR(80) NOT NULL,
            monthly_price DECIMAL(15,2) NOT NULL DEFAULT 0,
            max_routers INT UNSIGNED NULL,
            max_clients INT UNSIGNED NULL,
            allowed_features MEDIUMTEXT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY slug_unique (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NULL,
            admin_id INT UNSIGNED NULL,
            action VARCHAR(120) NOT NULL,
            resource_type VARCHAR(120) NULL,
            resource_id VARCHAR(80) NULL,
            ip VARCHAR(80) NULL,
            user_agent VARCHAR(255) NULL,
            message TEXT NULL,
            metadata MEDIUMTEXT NULL,
            created_at DATETIME NOT NULL,
            INDEX tenant_idx (tenant_id),
            INDEX admin_idx (admin_id),
            INDEX action_idx (action),
            INDEX created_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $tenantOwnedTables = [
            'tbl_routers',
            'router_provisioning_runs',
            'router_provisioning_steps',
            'router_provisioning_templates',
            'router_port_mappings',
            'router_portal_tokens',
            'router_management_audit_logs',
            'tbl_customers',
            'tbl_customers_fields',
            'tbl_customers_inbox',
            'tbl_plans',
            'tbl_bandwidth',
            'tbl_user_recharges',
            'tbl_voucher',
            'tbl_transactions',
            'tbl_payment_gateway',
            'tbl_coupons',
            'tbl_pool',
            'tbl_port_pool',
            'tbl_odps',
            'tbl_logs',
            'tbl_message_logs',
            'tbl_users',
            'tbl_widgets',
            'jovipay_settings',
            'jovipay_transactions',
            'reconnection_attempts',
            'expiry_worker_runs',
            'expiry_worker_logs',
            'hotspot_api_attempts',
        ];

        foreach ($tenantOwnedTables as $table) {
            self::ensureColumn($table, 'tenant_id', 'INT UNSIGNED NULL');
        }

        self::ensureColumn('tbl_routers', 'site_name', 'VARCHAR(120) NULL');
        self::ensureColumn('tbl_routers', 'routeros_version', 'VARCHAR(80) NULL');
        self::ensureColumn('tbl_routers', 'provisioning_status', 'VARCHAR(64) NULL');
        self::ensureColumn('tbl_users', 'tenant_role', 'VARCHAR(80) NULL');
        self::ensureColumn('tenants', 'billing_exempt', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureColumn('tenants', 'exemption_reason', 'VARCHAR(255) NULL');
        self::ensureColumn('tenants', 'internal_tenant', 'TINYINT(1) NOT NULL DEFAULT 0');

        self::ensureIndex('tenants', 'idx_slug_status', ['slug', 'status']);
        self::ensureIndex('tenants', 'idx_subdomain_status', ['subdomain', 'status']);
        self::ensureIndex('tenant_domains', 'idx_domain_status', ['domain', 'status']);
        foreach ($tenantOwnedTables as $table) {
            self::ensureIndex($table, 'idx_tenant_id', ['tenant_id']);
        }
        self::ensureIndex('tbl_routers', 'idx_tenant_status', ['tenant_id', 'status']);
        self::ensureIndex('tbl_routers', 'idx_tenant_enabled_name', ['tenant_id', 'enabled', 'name']);
        self::ensureIndex('tbl_routers', 'idx_tenant_vpn_status', ['tenant_id', 'vpn_status']);
        self::ensureIndex('tbl_customers', 'idx_tenant_status', ['tenant_id', 'status']);
        self::ensureIndex('tbl_customers', 'idx_tenant_username', ['tenant_id', 'username']);
        self::ensureIndex('tbl_customers', 'idx_tenant_phone', ['tenant_id', 'phonenumber']);
        self::ensureIndex('tbl_customers', 'idx_tenant_service_status', ['tenant_id', 'service_type', 'status']);
        self::ensureIndex('tbl_transactions', 'idx_tenant_recharged', ['tenant_id', 'recharged_on']);
        self::ensureIndex('tbl_transactions', 'idx_tenant_username_recharged', ['tenant_id', 'username', 'recharged_on']);
        self::ensureIndex('tbl_transactions', 'idx_tenant_user_recharged', ['tenant_id', 'user_id', 'recharged_on']);
        self::ensureIndex('tbl_transactions', 'idx_tenant_invoice', ['tenant_id', 'invoice']);
        self::ensureIndex('tbl_payment_gateway', 'idx_tenant_status_date', ['tenant_id', 'status', 'created_date']);
        self::ensureIndex('tbl_payment_gateway', 'idx_tenant_gateway_status', ['tenant_id', 'gateway', 'status']);
        self::ensureIndex('tbl_payment_gateway', 'idx_gateway_trx', ['gateway_trx_id']);
        self::ensureIndex('tbl_user_recharges', 'idx_tenant_status_type_expiry', ['tenant_id', 'status', 'type', 'expiration']);
        self::ensureIndex('tbl_user_recharges', 'idx_tenant_customer_expiry', ['tenant_id', 'customer_id', 'expiration']);
        self::ensureIndex('tbl_user_recharges', 'idx_tenant_username_expiry', ['tenant_id', 'username', 'expiration']);
        self::ensureIndex('tbl_voucher', 'idx_tenant_code_status', ['tenant_id', 'code', 'status']);
        self::ensureIndex('tbl_voucher', 'idx_tenant_plan_status', ['tenant_id', 'id_plan', 'status']);
        self::ensureIndex('jovipay_transactions', 'idx_tenant_reference', ['tenant_id', 'account_reference']);
        self::ensureIndex('jovipay_transactions', 'idx_tenant_status', ['tenant_id', 'status']);
        self::ensureIndex('jovipay_transactions', 'idx_receipt_status', ['mpesa_receipt_number', 'status']);
        self::ensureIndex('jovipay_transactions', 'idx_tenant_phone_status_created', ['tenant_id', 'phone', 'status', 'created_at']);
        self::ensureIndex('jovipay_transactions', 'idx_tenant_mac_status_created', ['tenant_id', 'mac_address', 'status', 'created_at']);
        self::ensureIndex('jovipay_transactions', 'idx_tenant_checkout', ['tenant_id', 'checkout_request_id']);

        self::seedSubscriptionPlans();
        self::migrateDefaultTenant();
        if (class_exists('SaasBilling')) {
            SaasBilling::installSchema();
        }
        if (class_exists('FastnetpayRuntime')) {
            FastnetpayRuntime::markSchemaFresh('tenant', self::SCHEMA_VERSION);
        }
    }

    public static function current()
    {
        return self::$current ?: self::defaultTenant();
    }

    public static function currentTenant()
    {
        return self::current();
    }

    public static function currentId()
    {
        $tenant = self::current();
        return $tenant ? (int) $tenant['id'] : 0;
    }

    public static function currentTenantId()
    {
        return self::currentId();
    }

    public static function mode()
    {
        return self::$mode;
    }

    public static function isTenantRequest()
    {
        return self::$mode === 'tenant';
    }

    public static function isUnknownTenantRequest()
    {
        return self::$mode === 'unknown';
    }

    public static function unknownTenantHost()
    {
        return self::$unknownTenantHost;
    }

    public static function isDefaultTenant($tenant = null)
    {
        $tenant = $tenant ?: self::current();
        return $tenant && (string) $tenant['slug'] === 'main';
    }

    public static function defaultTenant()
    {
        $tenant = ORM::for_table('tenants')->where('slug', 'main')->find_one();
        if ($tenant) {
            return $tenant;
        }

        $tenant = ORM::for_table('tenants')->create();
        $tenant->name = 'FASTNETPAY Main';
        $tenant->slug = 'main';
        $tenant->subdomain = 'fastnetpay';
        $tenant->custom_domain = 'fastnetpay.co.ke';
        $tenant->status = 'active';
        $tenant->timezone = 'Africa/Nairobi';
        $tenant->currency = 'KES';
        $tenant->subscription_plan = 'Enterprise';
        $tenant->subscription_status = 'active';
        $tenant->created_at = date('Y-m-d H:i:s');
        $tenant->updated_at = date('Y-m-d H:i:s');
        $tenant->save();
        self::upsertDomain((int) $tenant->id(), 'fastnetpay.fastnetpay.co.ke', 'subdomain', 'active');
        self::upsertDomain((int) $tenant->id(), 'fastnetpay.co.ke', 'custom', 'active');
        return ORM::for_table('tenants')->find_one($tenant->id());
    }

    public static function resolveFromHost($config = [])
    {
        $host = self::requestHost();
        $cacheKey = $host . '|' . (isset($_GET['tenant']) ? self::slug($_GET['tenant']) : '') . '|' . (isset($_GET['_tenant']) ? self::slug($_GET['_tenant']) : '');
        if (isset(self::$tenantCache[$cacheKey])) {
            return self::$tenantCache[$cacheKey];
        }
        $base = self::baseDomain($config);
        $localDomain = self::localDomain($config);
        $localTestingHost = self::isLocalHost($host, $config) || ($localDomain !== '' && substr($host, -strlen('.' . $localDomain)) === '.' . $localDomain);
        $localTesting = self::localTenantTesting($config) || $localTestingHost;

        $debugTenant = '';
        if ($localTesting) {
            $debugTenant = isset($_GET['tenant']) ? self::slug($_GET['tenant']) : '';
            if ($debugTenant === '' && isset($_GET['_tenant'])) {
                $debugTenant = self::slug($_GET['_tenant']);
            }
            if ($debugTenant !== '') {
                $tenant = ORM::for_table('tenants')->where('slug', $debugTenant)->find_one();
                if (!$tenant) {
                    $tenant = ORM::for_table('tenants')->where('subdomain', $debugTenant)->find_one();
                }
                if ($tenant) {
                    return self::$tenantCache[$cacheKey] = $tenant;
                }
            }
        }

        if ($host === '') {
            return self::$tenantCache[$cacheKey] = self::defaultTenant();
        }

        if ($localTesting && $localDomain !== '' && substr($host, -strlen('.' . $localDomain)) === '.' . $localDomain) {
            $subdomain = substr($host, 0, -strlen('.' . $localDomain));
            if (strpos($subdomain, '.') === false && $subdomain !== '' && !in_array($subdomain, ['www', 'app'], true)) {
                $tenant = ORM::for_table('tenants')->where('subdomain', $subdomain)->find_one();
                if (!$tenant) {
                    $tenant = ORM::for_table('tenants')->where('slug', $subdomain)->find_one();
                }
                if ($tenant) {
                    return self::$tenantCache[$cacheKey] = $tenant;
                }
            }
        }

        if ($host === '' || self::isLocalHost($host, $config)) {
            return self::$tenantCache[$cacheKey] = self::defaultTenant();
        }

        if (self::isMainHost($config, $host)) {
            return self::$tenantCache[$cacheKey] = self::defaultTenant();
        }

        $domain = ORM::for_table('tenant_domains')->where('domain', $host)->where('status', 'active')->find_one();
        if ($domain) {
            return self::$tenantCache[$cacheKey] = (ORM::for_table('tenants')->find_one((int) $domain['tenant_id']) ?: self::defaultTenant());
        }

        $custom = ORM::for_table('tenants')->where('custom_domain', $host)->find_one();
        if ($custom) {
            return self::$tenantCache[$cacheKey] = $custom;
        }

        if (substr($host, -strlen('.' . $base)) === '.' . $base) {
            $subdomain = substr($host, 0, -strlen('.' . $base));
            if (strpos($subdomain, '.') === false && $subdomain !== '' && !in_array($subdomain, self::reservedSubdomains(), true)) {
                $tenant = ORM::for_table('tenants')->where('subdomain', $subdomain)->find_one();
                if ($tenant) {
                    return self::$tenantCache[$cacheKey] = $tenant;
                }
                self::$unknownTenantHost = $host;
                return self::$tenantCache[$cacheKey] = null;
            }
        }

        $debugTenant = isset($_GET['_tenant']) ? self::slug($_GET['_tenant']) : '';
        if ($debugTenant !== '') {
            $tenant = ORM::for_table('tenants')->where('slug', $debugTenant)->find_one();
            if ($tenant) {
                return self::$tenantCache[$cacheKey] = $tenant;
            }
        }

        return self::$tenantCache[$cacheKey] = self::defaultTenant();
    }

    public static function isMainHost($config = [], $host = null)
    {
        $host = $host ?: self::requestHost();
        $base = self::baseDomain($config);
        $localDomain = self::localDomain($config);
        $appHost = '';
        if (defined('APP_URL')) {
            $appHost = strtolower((string) parse_url(APP_URL, PHP_URL_HOST));
        }
        return in_array($host, array_filter([$base, 'www.' . $base, $appHost, $localDomain]), true) || self::isLocalHost($host, $config);
    }

    public static function baseDomain($config = [])
    {
        $value = trim((string) ($config['saas_base_domain'] ?? ''));
        if ($value === '') {
            $env = getenv('APP_BASE_DOMAIN');
            $value = $env === false ? '' : trim((string) $env);
        }
        if ($value === '') {
            $value = 'fastnetpay.co.ke';
        }
        return strtolower(preg_replace('/:\d+$/', '', $value));
    }

    public static function localDomain($config = [])
    {
        $value = trim((string) ($config['saas_local_domain'] ?? ''));
        if ($value === '') {
            $env = getenv('APP_LOCAL_DOMAIN');
            $value = $env === false ? '' : trim((string) $env);
        }
        return strtolower(preg_replace('/:\d+$/', '', $value ?: 'localhost'));
    }

    public static function localTenantTesting($config = [])
    {
        $value = trim((string) ($config['tenant_local_testing'] ?? ''));
        if ($value === '') {
            $env = getenv('TENANT_LOCAL_TESTING');
            $value = $env === false ? '' : trim((string) $env);
        }
        $appEnv = strtolower(trim((string) ($config['app_env'] ?? getenv('APP_ENV') ?: '')));
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true) || in_array($appEnv, ['local', 'dev', 'development'], true);
    }

    public static function requestHost()
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host);
        return preg_replace('/[^a-z0-9.-]/', '', $host);
    }

    public static function isLocalHost($host, $config = [])
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1', 'fastnetpay.local', self::localDomain($config)], true);
    }

    public static function applyConfigOverrides(&$config)
    {
        $tenant = self::current();
        if (!$tenant) {
            return;
        }

        $config['tenant_id'] = (string) $tenant['id'];
        $config['tenant_slug'] = (string) $tenant['slug'];
        $config['tenant_mode'] = self::mode();
        $config['tenant_primary_color'] = (string) $tenant['primary_color'];
        $config['tenant_secondary_color'] = (string) $tenant['secondary_color'];
        $config['tenant_dark_primary_color'] = (string) $tenant['dark_primary_color'];
        $config['tenant_dark_secondary_color'] = (string) $tenant['dark_secondary_color'];

        if (self::isTenantRequest()) {
            $tenantGateways = self::setting('payment', 'active_gateways', '', (int) $tenant['id']);
            if ($tenantGateways !== '') {
                $config['payment_gateway'] = $tenantGateways;
            }

            foreach (['sms_gateway', 'sms_url', 'talksasa_api_endpoint', 'talksasa_sender_id'] as $smsSetting) {
                $smsValue = self::setting('sms', $smsSetting, '', (int) $tenant['id']);
                if ($smsValue !== '') {
                    $config[$smsSetting] = $smsValue;
                }
            }

            $config['CompanyName'] = (string) $tenant['name'];
            $config['currency_code'] = (string) ($tenant['currency'] ?: ($config['currency_code'] ?? 'KES'));
            $config['timezone'] = (string) ($tenant['timezone'] ?: ($config['timezone'] ?? 'Africa/Nairobi'));
            if (!empty($tenant['contact_phone'])) {
                $config['phone'] = (string) $tenant['contact_phone'];
            }
            if (!empty($tenant['contact_email'])) {
                $config['mail_from'] = (string) $tenant['contact_email'];
            }
        }
    }

    public static function assignToSmarty($ui)
    {
        $tenant = self::current();
        $ui->assign('_tenant', $tenant ? $tenant->as_array() : []);
        $ui->assign('_tenant_mode', self::mode());
        $ui->assign('_tenant_is_main', !self::isTenantRequest());
    }

    public static function scopeQuery($query, $tableAlias = '', $tenantId = null)
    {
        $tenantId = $tenantId ?: self::currentId();
        if (!$tenantId) {
            return $query;
        }
        $column = $tableAlias !== '' ? $tableAlias . '.tenant_id' : 'tenant_id';
        return $query->where($column, (int) $tenantId);
    }

    public static function scopeIfTenant($query, $tableAlias = '')
    {
        if (self::isTenantRequest()) {
            return self::scopeQuery($query, $tableAlias);
        }
        return $query;
    }

    public static function enforceTenantScope($query, $tableAlias = '', $tenantId = null)
    {
        return self::scopeQuery($query, $tableAlias, $tenantId);
    }

    public static function isSuperAdmin($admin = null)
    {
        if (!$admin) {
            $admin = class_exists('Admin') ? Admin::_info() : null;
        }
        return $admin && (string) ($admin['user_type'] ?? '') === 'SuperAdmin';
    }

    public static function isTenantAdmin($admin = null)
    {
        if (!$admin) {
            $admin = class_exists('Admin') ? Admin::_info() : null;
        }
        return $admin && self::isTenantRequest() && !self::isSuperAdmin($admin);
    }

    public static function denyCrossTenantResourceAccess($row, $resourceType = 'resource', $resourceId = '')
    {
        if (!$row || !self::isTenantRequest()) {
            return $row;
        }
        $tenantId = isset($row['tenant_id']) ? (int) $row['tenant_id'] : 0;
        if ($tenantId > 0 && $tenantId !== self::currentId()) {
            self::audit('tenant.cross_tenant_blocked', 'Blocked cross-tenant access to ' . $resourceType, $resourceType, (string) ($resourceId ?: ($row['id'] ?? '')), self::currentId(), (int) ($_SESSION['aid'] ?? 0), ['resource_tenant_id' => $tenantId]);
            _alert(Lang::T('You do not have permission to access this tenant resource.'), 'danger', 'dashboard');
        }
        return $row;
    }

    public static function denyTenantAccessToSuperAdminRoutes($handler, $routes = [])
    {
        if (!self::isTenantRequest()) {
            return;
        }
        $handler = (string) $handler;
        $action = (string) ($routes[1] ?? '');
        $blockedHandlers = ['saas', 'paymentgateway', 'jovipay', 'pluginmanager', 'community', 'widgets', 'maps', 'radius', 'expiry'];
        $blockedPlugins = ['talksasa', 'pay_setup', 'port_tester', 'speedtest', 'system_info'];
        $blockedSettings = ['app', 'app-post', 'localisation', 'localisation-post', 'miscellaneous', 'miscellaneous-post', 'maintenance', 'maintenance-post', 'devices', 'dbstatus', 'docs', 'users'];
        $blocked = in_array($handler, $blockedHandlers, true)
            || ($handler === 'plugin' && in_array($action, $blockedPlugins, true))
            || ($handler === 'settings' && in_array($action, $blockedSettings, true))
            || in_array($handler, ['pages', 'customfield'], true);
        if ($blocked) {
            self::audit('tenant.route_blocked', 'Tenant attempted to access SuperAdmin/global route.', 'route', $handler . '/' . $action, self::currentId(), (int) ($_SESSION['aid'] ?? 0));
            _alert(Lang::T('This page is available only from the FASTNETPAY SuperAdmin portal.'), 'danger', 'dashboard');
        }
    }

    public static function stamp($row, $tenantId = null, $table = '')
    {
        if (!$row) {
            return $row;
        }
        if ($table === '') {
            try {
                $ref = new ReflectionObject($row);
                if ($ref->hasProperty('_table_name')) {
                    $prop = $ref->getProperty('_table_name');
                    $prop->setAccessible(true);
                    $table = (string) $prop->getValue($row);
                }
            } catch (Throwable $ignored) {
                $table = '';
            }
        }
        if ($table !== '' && self::hasColumn($table, 'tenant_id')) {
            $row->tenant_id = (int) ($tenantId ?: self::currentId());
        }
        return $row;
    }

    public static function rowTenantId($row)
    {
        if (!$row) {
            return self::currentId();
        }
        $value = isset($row['tenant_id']) ? (int) $row['tenant_id'] : 0;
        return $value ?: self::currentId();
    }

    public static function tenantFromPrefix($accountReference)
    {
        $accountReference = (string) $accountReference;
        foreach (ORM::for_table('tenant_settings')->where('namespace', 'jovipay')->where('setting', 'account_prefix')->find_many() as $row) {
            $prefix = (string) $row['value'];
            if ($prefix !== '' && strpos($accountReference, $prefix) === 0) {
                return ORM::for_table('tenants')->find_one((int) $row['tenant_id']);
            }
        }
        return self::current();
    }

    public static function setting($namespace, $setting, $default = '', $tenantId = null)
    {
        $tenantId = $tenantId ?: self::currentId();
        $cacheKey = (int) $tenantId . '|' . (string) $namespace . '|' . (string) $setting;
        if (array_key_exists($cacheKey, self::$settingsCache)) {
            return self::$settingsCache[$cacheKey] === null ? $default : self::$settingsCache[$cacheKey];
        }
        $row = ORM::for_table('tenant_settings')
            ->where('tenant_id', (int) $tenantId)
            ->where('namespace', $namespace)
            ->where('setting', $setting)
            ->find_one();
        self::$settingsCache[$cacheKey] = $row ? (string) $row['value'] : null;
        return $row ? (string) $row['value'] : $default;
    }

    public static function saveSetting($namespace, $setting, $value, $isSecret = false, $tenantId = null)
    {
        $tenantId = $tenantId ?: self::currentId();
        $row = ORM::for_table('tenant_settings')
            ->where('tenant_id', (int) $tenantId)
            ->where('namespace', $namespace)
            ->where('setting', $setting)
            ->find_one();
        if (!$row) {
            $row = ORM::for_table('tenant_settings')->create();
            $row->tenant_id = (int) $tenantId;
            $row->namespace = $namespace;
            $row->setting = $setting;
            $row->created_at = date('Y-m-d H:i:s');
        }
        $row->value = (string) $value;
        $row->is_secret = $isSecret ? 1 : 0;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        unset(self::$settingsCache[(int) $tenantId . '|' . (string) $namespace . '|' . (string) $setting]);
    }

    public static function audit($action, $message = '', $resourceType = '', $resourceId = '', $tenantId = null, $adminId = null, $metadata = [])
    {
        try {
            $row = ORM::for_table('saas_audit_logs')->create();
            $row->tenant_id = $tenantId ?: self::currentId();
            $row->admin_id = $adminId ?: (int) ($_SESSION['aid'] ?? 0);
            $row->action = substr((string) $action, 0, 120);
            $row->resource_type = substr((string) $resourceType, 0, 120);
            $row->resource_id = substr((string) $resourceId, 0, 80);
            $row->ip = self::clientIp();
            $row->user_agent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $row->message = (string) $message;
            $row->metadata = $metadata ? json_encode($metadata) : null;
            $row->created_at = date('Y-m-d H:i:s');
            $row->save();
        } catch (Throwable $e) {
            _log('SaaS audit failed: ' . $e->getMessage(), 'SaaS', 0);
        }
    }

    public static function createTenant($data, $adminId = 0)
    {
        $slug = self::slug($data['slug'] ?: $data['name']);
        $subdomain = self::slug($data['subdomain'] ?: $slug);
        if ($slug === '' || $subdomain === '') {
            throw new Exception('Tenant slug and subdomain are required.');
        }
        if (in_array($slug, self::reservedSubdomains(), true) || in_array($subdomain, self::reservedSubdomains(), true)) {
            throw new Exception('This tenant slug or subdomain is reserved for FASTNETPAY infrastructure.');
        }
        if (ORM::for_table('tenants')->where('slug', $slug)->find_one()) {
            throw new Exception('Tenant slug already exists.');
        }
        if (ORM::for_table('tenants')->where('subdomain', $subdomain)->find_one()) {
            throw new Exception('Tenant subdomain already exists.');
        }

        $tenant = ORM::for_table('tenants')->create();
        self::fillTenant($tenant, $data, $slug, $subdomain);
        $tenant->created_at = date('Y-m-d H:i:s');
        $tenant->updated_at = date('Y-m-d H:i:s');
        $tenant->save();

        self::upsertDomain((int) $tenant->id(), $subdomain . '.' . self::baseDomain(), 'subdomain', 'active');
        if (!empty($tenant['custom_domain'])) {
            self::upsertDomain((int) $tenant->id(), $tenant['custom_domain'], 'custom', 'pending');
        }
        self::audit('tenant.created', 'Tenant created: ' . $tenant['name'], 'tenant', (string) $tenant->id(), (int) $tenant->id(), $adminId);
        return ORM::for_table('tenants')->find_one($tenant->id());
    }

    public static function updateTenant($tenant, $data, $adminId = 0)
    {
        if (!$tenant) {
            throw new Exception('Tenant not found.');
        }
        $slug = self::slug($data['slug'] ?: $tenant['slug']);
        $subdomain = self::slug($data['subdomain'] ?: $tenant['subdomain']);
        if (in_array($slug, self::reservedSubdomains(), true) || in_array($subdomain, self::reservedSubdomains(), true)) {
            throw new Exception('This tenant slug or subdomain is reserved for FASTNETPAY infrastructure.');
        }
        $exists = ORM::for_table('tenants')->where('slug', $slug)->where_not_equal('id', (int) $tenant['id'])->find_one();
        if ($exists) {
            throw new Exception('Tenant slug already exists.');
        }
        $exists = ORM::for_table('tenants')->where('subdomain', $subdomain)->where_not_equal('id', (int) $tenant['id'])->find_one();
        if ($exists) {
            throw new Exception('Tenant subdomain already exists.');
        }

        self::fillTenant($tenant, $data, $slug, $subdomain);
        $tenant->updated_at = date('Y-m-d H:i:s');
        $tenant->save();
        self::upsertDomain((int) $tenant['id'], $subdomain . '.' . self::baseDomain(), 'subdomain', 'active');
        if (!empty($tenant['custom_domain'])) {
            self::upsertDomain((int) $tenant['id'], $tenant['custom_domain'], 'custom', 'pending');
        }
        self::audit('tenant.updated', 'Tenant updated: ' . $tenant['name'], 'tenant', (string) $tenant['id'], (int) $tenant['id'], $adminId);
    }

    public static function createTenantAdmin($tenantId, $data, $adminId = 0)
    {
        $tenant = ORM::for_table('tenants')->find_one((int) $tenantId);
        if (!$tenant) {
            throw new Exception('Tenant not found.');
        }
        $username = strtolower(trim((string) ($data['username'] ?? '')));
        if (!preg_match('/^[a-z0-9._@-]{3,64}$/', $username)) {
            throw new Exception('Username should be at least 3 characters.');
        }
        if (ORM::for_table('tbl_users')->where('username', $username)->find_one()) {
            throw new Exception('Username already exists.');
        }
        $password = (string) ($data['password'] ?? '');
        if (strlen($password) < 6) {
            throw new Exception('Password should be at least 6 characters.');
        }
        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Tenant admin email address is invalid.');
        }
        $phone = substr(preg_replace('/[^0-9+]/', '', (string) ($data['phone'] ?? '')), 0, 32);
        $fullname = substr(trim((string) ($data['fullname'] ?? $tenant['name'] . ' Admin')), 0, 45);
        $role = trim((string) ($data['role'] ?? 'Admin'));
        if (!in_array($role, ['Admin', 'Agent', 'Sales', 'Report'], true)) {
            $role = 'Admin';
        }

        $user = ORM::for_table('tbl_users')->create();
        $user->tenant_id = (int) $tenantId;
        $user->root = 0;
        $user->photo = '/admin.default.png';
        $user->username = $username;
        $user->fullname = $fullname;
        $user->password = Password::_crypt($password);
        $user->phone = $phone;
        $user->email = substr($email, 0, 128);
        $user->city = '';
        $user->subdistrict = '';
        $user->ward = '';
        $user->user_type = $role;
        $user->tenant_role = $role === 'Admin' ? 'Tenant Admin' : 'Tenant ' . $role;
        $user->status = 'Active';
        $user->creationdate = date('Y-m-d H:i:s');
        $user->save();

        self::audit('tenant.admin_created', 'Tenant admin created: ' . $username, 'user', (string) $user->id(), (int) $tenantId, $adminId);
        return $user;
    }

    public static function tenantsWithSummary()
    {
        $rows = [];
        foreach (ORM::for_table('tenants')->order_by_asc('id')->find_many() as $tenant) {
            $id = (int) $tenant['id'];
            $rows[] = [
                'tenant' => $tenant,
                'routers' => self::countByTenant('tbl_routers', $id),
                'clients' => self::countByTenant('tbl_customers', $id),
                'plans' => self::countByTenant('tbl_plans', $id),
                'payments' => self::countByTenant('tbl_payment_gateway', $id),
                'admins' => self::countByTenant('tbl_users', $id),
            ];
        }
        return $rows;
    }

    private static function countByTenant($table, $tenantId)
    {
        if (!self::hasColumn($table, 'tenant_id')) {
            return 0;
        }
        return (int) ORM::for_table($table)->where('tenant_id', (int) $tenantId)->count();
    }

    private static function fillTenant($tenant, $data, $slug, $subdomain)
    {
        $tenant->name = substr(trim((string) ($data['name'] ?? '')), 0, 160);
        $tenant->slug = $slug;
        $tenant->subdomain = $subdomain;
        $tenant->custom_domain = self::domain($data['custom_domain'] ?? '');
        $tenant->status = in_array(($data['status'] ?? 'trial'), ['active', 'suspended', 'trial'], true) ? $data['status'] : 'trial';
        $tenant->logo = substr(trim((string) ($data['logo'] ?? '')), 0, 255);
        $tenant->primary_color = self::color($data['primary_color'] ?? '#41a146', '#41a146');
        $tenant->secondary_color = self::color($data['secondary_color'] ?? '#f9c02b', '#f9c02b');
        $tenant->dark_primary_color = self::color($data['dark_primary_color'] ?? '#4ade80', '#4ade80');
        $tenant->dark_secondary_color = self::color($data['dark_secondary_color'] ?? '#facc15', '#facc15');
        $tenant->contact_phone = substr(trim((string) ($data['contact_phone'] ?? '')), 0, 64);
        $tenant->contact_email = substr(trim((string) ($data['contact_email'] ?? '')), 0, 160);
        $tenant->billing_email = substr(trim((string) ($data['billing_email'] ?? '')), 0, 160);
        $tenant->timezone = substr(trim((string) ($data['timezone'] ?? 'Africa/Nairobi')), 0, 80);
        $tenant->currency = strtoupper(substr(trim((string) ($data['currency'] ?? 'KES')), 0, 16));
        $tenant->subscription_plan = substr(trim((string) ($data['subscription_plan'] ?? 'Starter')), 0, 80);
        $tenant->subscription_status = substr(trim((string) ($data['subscription_status'] ?? $tenant->status)), 0, 32);
        $tenant->trial_ends_at = trim((string) ($data['trial_ends_at'] ?? '')) ?: null;
        $tenant->max_routers = (int) ($data['max_routers'] ?? 0) ?: null;
        $tenant->max_clients = (int) ($data['max_clients'] ?? 0) ?: null;
        $tenant->allowed_features = trim((string) ($data['allowed_features'] ?? ''));
        $tenant->billing_exempt = !empty($data['billing_exempt']) ? 1 : 0;
        $tenant->exemption_reason = substr(trim((string) ($data['exemption_reason'] ?? '')), 0, 255);
        $tenant->internal_tenant = !empty($data['internal_tenant']) ? 1 : 0;
    }

    private static function migrateDefaultTenant()
    {
        $tenant = self::defaultTenant();
        $tenantId = (int) $tenant['id'];
        foreach (self::tenantTables() as $table) {
            if (self::hasColumn($table, 'tenant_id')) {
                try {
                    ORM::raw_execute("UPDATE `$table` SET tenant_id = ? WHERE tenant_id IS NULL", [$tenantId]);
                } catch (Throwable $e) {
                    _log('Tenant migration skipped for ' . $table . ': ' . $e->getMessage(), 'SaaS', 0);
                }
            }
        }
        try {
            if (self::tableExists('jovipay_settings') && !ORM::for_table('tenant_settings')->where('tenant_id', $tenantId)->where('namespace', 'jovipay')->where('setting', 'account_prefix')->find_one()) {
                $jovi = ORM::for_table('jovipay_settings')->where('tenant_id', $tenantId)->order_by_asc('id')->find_one();
                if ($jovi && !empty($jovi['account_prefix'])) {
                    self::saveSetting('jovipay', 'account_prefix', (string) $jovi['account_prefix'], false, $tenantId);
                }
            }
        } catch (Throwable $e) {
            _log('Tenant Jovi-Pay prefix migration skipped: ' . $e->getMessage(), 'SaaS', 0);
        }
    }

    private static function tenantTables()
    {
        return [
            'tbl_routers', 'router_provisioning_runs', 'router_provisioning_steps', 'router_provisioning_templates',
            'router_port_mappings', 'router_portal_tokens', 'router_management_audit_logs', 'tbl_customers',
            'tbl_customers_fields', 'tbl_customers_inbox', 'tbl_plans', 'tbl_bandwidth', 'tbl_user_recharges',
            'tbl_voucher', 'tbl_transactions', 'tbl_payment_gateway', 'tbl_coupons', 'tbl_pool', 'tbl_port_pool',
            'tbl_odps', 'tbl_logs', 'tbl_message_logs', 'tbl_users', 'tbl_widgets', 'jovipay_settings',
            'jovipay_transactions', 'reconnection_attempts', 'expiry_worker_runs', 'expiry_worker_logs',
            'hotspot_api_attempts',
        ];
    }

    private static function seedSubscriptionPlans()
    {
        $plans = [
            ['Starter', 'starter', 0, 2, 500, 'hotspot,pppoe,mpesa,sms'],
            ['Growth', 'growth', 0, 10, 5000, 'hotspot,pppoe,mpesa,sms,provisioning'],
            ['ISP Pro', 'isp-pro', 0, 50, 50000, 'hotspot,pppoe,mpesa,sms,provisioning,vpn,monitoring'],
            ['Enterprise', 'enterprise', 0, null, null, 'all'],
        ];
        foreach ($plans as $plan) {
            if (ORM::for_table('tenant_subscription_plans')->where('slug', $plan[1])->find_one()) {
                continue;
            }
            $row = ORM::for_table('tenant_subscription_plans')->create();
            $row->name = $plan[0];
            $row->slug = $plan[1];
            $row->monthly_price = $plan[2];
            $row->max_routers = $plan[3];
            $row->max_clients = $plan[4];
            $row->allowed_features = $plan[5];
            $row->status = 'active';
            $row->created_at = date('Y-m-d H:i:s');
            $row->updated_at = date('Y-m-d H:i:s');
            $row->save();
        }
    }

    private static function upsertDomain($tenantId, $domain, $type, $status)
    {
        $domain = self::domain($domain);
        if ($domain === '') {
            return;
        }
        $row = ORM::for_table('tenant_domains')->where('domain', $domain)->find_one();
        if (!$row) {
            $row = ORM::for_table('tenant_domains')->create();
            $row->domain = $domain;
            $row->created_at = date('Y-m-d H:i:s');
        }
        $row->tenant_id = (int) $tenantId;
        $row->domain_type = $type === 'custom' ? 'custom' : 'subdomain';
        $row->status = $status;
        $row->ssl_status = $type === 'custom' ? 'pending' : 'wildcard';
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
    }

    private static function ensureColumn($table, $column, $definition)
    {
        try {
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
            $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
            if ($table === '' || $column === '' || !self::tableExists($table)) {
                return;
            }
            if (!self::hasColumn($table, $column)) {
                ORM::raw_execute("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                self::$columnCache[$table][$column] = true;
            }
        } catch (Throwable $e) {
            _log('Tenant schema check failed for ' . $table . '.' . $column . ': ' . $e->getMessage(), 'SaaS', 0);
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
            if ($table === '' || $index === '' || !$columns || !self::tableExists($table)) {
                return;
            }
            foreach ($columns as $column) {
                if (!self::hasColumn($table, $column)) {
                    return;
                }
            }
            $exists = ORM::for_table($table)->raw_query("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index])->find_one();
            if ($exists) {
                return;
            }
            $columnSql = implode(',', array_map(function ($column) {
                return "`$column`";
            }, $columns));
            ORM::raw_execute("ALTER TABLE `$table` ADD INDEX `$index` ($columnSql)");
        } catch (Throwable $e) {
            _log('Tenant index check failed for ' . $table . '.' . $index . ': ' . $e->getMessage(), 'SaaS', 0);
        }
    }

    private static function tableExists($table)
    {
        try {
            $row = ORM::for_table($table)->raw_query("SHOW TABLES LIKE ?", [$table])->find_one();
            return (bool) $row;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function hasColumn($table, $column)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);
        if ($table === '' || $column === '') {
            return false;
        }
        if (isset(self::$columnCache[$table][$column])) {
            return self::$columnCache[$table][$column];
        }
        try {
            $row = ORM::for_table($table)->raw_query("SHOW COLUMNS FROM `$table` LIKE ?", [$column])->find_one();
            self::$columnCache[$table][$column] = (bool) $row;
        } catch (Throwable $e) {
            self::$columnCache[$table][$column] = false;
        }
        return self::$columnCache[$table][$column];
    }

    public static function slug($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    public static function reservedSubdomains()
    {
        return [
            'mother',
            'www',
            'api',
            'vpn',
            'portainer',
            'admin',
            'mail',
            'smtp',
            'ftp',
            'docs',
            'support',
            'status',
            'monitor',
            'callback',
            'assets',
            'static',
        ];
    }

    private static function domain($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('#^https?://#', '', $value);
        $value = preg_replace('#/.*$#', '', $value);
        $value = preg_replace('/:\d+$/', '', $value);
        return preg_replace('/[^a-z0-9.-]/', '', $value);
    }

    private static function color($value, $default)
    {
        $value = trim((string) $value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default;
    }

    private static function clientIp()
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return substr((string) $_SERVER[$key], 0, 80);
            }
        }
        return php_sapi_name() === 'cli' ? 'CLI' : 'Unknown';
    }
}
