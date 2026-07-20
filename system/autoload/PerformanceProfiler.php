<?php

/**
 * Development-only route profiler for FASTNETPAY.
 */
class PerformanceProfiler
{
    const SCHEMA_VERSION = '2026-07-09-perf1';

    private static $enabled = false;
    private static $startedAt = 0.0;
    private static $route = 'unknown';
    private static $queries = [];

    public static function boot($stage = '', $config = [])
    {
        if (self::$startedAt > 0) {
            return;
        }
        self::$startedAt = microtime(true);
        self::$enabled = class_exists('FastnetpayRuntime') && FastnetpayRuntime::isDevelopment($stage, $config);
        if (!self::$enabled) {
            return;
        }
        ORM::configure('logging', true);
        ORM::configure('logger', [__CLASS__, 'recordQuery']);
        register_shutdown_function([__CLASS__, 'shutdown']);
    }

    public static function setRoute($route)
    {
        self::$route = trim((string) $route) ?: 'default';
    }

    public static function recordQuery($sql, $seconds)
    {
        if (!self::$enabled) {
            return;
        }
        self::$queries[] = [
            'sql' => self::redactQuery((string) $sql),
            'ms' => round(((float) $seconds) * 1000, 3),
        ];
    }

    public static function shutdown()
    {
        if (!self::$enabled || self::$startedAt <= 0) {
            return;
        }
        $totalMs = round((microtime(true) - self::$startedAt) * 1000, 2);
        $slow = array_values(array_filter(self::$queries, function ($query) {
            return (float) $query['ms'] >= 50;
        }));
        usort($slow, function ($a, $b) {
            return $b['ms'] <=> $a['ms'];
        });
        $slow = array_slice($slow, 0, 6);
        if (!headers_sent()) {
            header('X-Fastnetpay-Profile: route=' . self::$route . '; total_ms=' . $totalMs . '; queries=' . count(self::$queries));
        }
        if (!self::shouldStoreSample()) {
            return;
        }
        try {
            self::installSchema();
            $row = ORM::for_table('performance_route_samples')->create();
            $row->route = substr(self::$route, 0, 190);
            $row->method = substr((string) ($_SERVER['REQUEST_METHOD'] ?? 'CLI'), 0, 12);
            $row->uri = substr((string) ($_SERVER['REQUEST_URI'] ?? ''), 0, 255);
            $row->status_code = (int) (http_response_code() ?: 200);
            $row->total_ms = $totalMs;
            $row->query_count = count(self::$queries);
            $row->slow_query_count = count($slow);
            $row->memory_mb = round(memory_get_peak_usage(true) / 1048576, 2);
            $row->included_files = count(get_included_files());
            $row->slowest_queries = json_encode($slow);
            $row->tenant_id = class_exists('Tenant') ? Tenant::currentId() : 0;
            $row->admin_id = (int) ($_SESSION['aid'] ?? 0);
            $row->created_at = date('Y-m-d H:i:s');
            $row->save();
            self::trimSamples();
        } catch (Throwable $e) {
            _log('FASTNETPAY profiler failed: ' . $e->getMessage(), 'Performance', 0);
        }
    }

    public static function installSchema()
    {
        if (class_exists('FastnetpayRuntime') && FastnetpayRuntime::schemaFresh('performance_profiler', self::SCHEMA_VERSION, 86400)) {
            return;
        }
        ORM::raw_execute("CREATE TABLE IF NOT EXISTS performance_route_samples (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            route VARCHAR(190) NOT NULL,
            method VARCHAR(12) NOT NULL,
            uri VARCHAR(255) NULL,
            status_code INT UNSIGNED NOT NULL DEFAULT 200,
            total_ms DECIMAL(12,2) NOT NULL DEFAULT 0,
            query_count INT UNSIGNED NOT NULL DEFAULT 0,
            slow_query_count INT UNSIGNED NOT NULL DEFAULT 0,
            memory_mb DECIMAL(12,2) NOT NULL DEFAULT 0,
            included_files INT UNSIGNED NOT NULL DEFAULT 0,
            slowest_queries MEDIUMTEXT NULL,
            tenant_id INT UNSIGNED NULL,
            admin_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            INDEX route_idx (route),
            INDEX created_idx (created_at),
            INDEX total_ms_idx (total_ms),
            INDEX tenant_idx (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (class_exists('FastnetpayRuntime')) {
            FastnetpayRuntime::markSchemaFresh('performance_profiler', self::SCHEMA_VERSION);
        }
    }

    public static function latestSamples($limit = 120)
    {
        self::installSchema();
        return ORM::for_table('performance_route_samples')->order_by_desc('id')->limit((int) $limit)->find_many();
    }

    public static function summary()
    {
        self::installSchema();
        $row = ORM::for_table('performance_route_samples')->raw_query(
            "SELECT COUNT(*) samples, ROUND(AVG(total_ms),2) avg_ms, MAX(total_ms) max_ms, ROUND(AVG(query_count),2) avg_queries, MAX(query_count) max_queries FROM performance_route_samples WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        )->find_one();
        $slowRoutes = ORM::for_table('performance_route_samples')->raw_query(
            "SELECT route, COUNT(*) hits, ROUND(AVG(total_ms),2) avg_ms, MAX(total_ms) max_ms, ROUND(AVG(query_count),2) avg_queries FROM performance_route_samples WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY route ORDER BY avg_ms DESC LIMIT 10"
        )->find_many();
        return [
            'samples' => (int) ($row['samples'] ?? 0),
            'avg_ms' => (float) ($row['avg_ms'] ?? 0),
            'max_ms' => (float) ($row['max_ms'] ?? 0),
            'avg_queries' => (float) ($row['avg_queries'] ?? 0),
            'max_queries' => (int) ($row['max_queries'] ?? 0),
            'slow_routes' => $slowRoutes,
        ];
    }

    public static function cacheStatus()
    {
        $dir = class_exists('FastnetpayRuntime') ? FastnetpayRuntime::cacheDir('schema') : '';
        $rows = [];
        foreach (['tenant', 'saas_billing', 'jovipay', 'router_provisioning', 'performance_profiler'] as $key) {
            $file = $dir ? $dir . DIRECTORY_SEPARATOR . FastnetpayRuntime::safeKey($key) . '.json' : '';
            $data = is_file($file) ? json_decode((string) @file_get_contents($file), true) : [];
            $rows[] = [
                'key' => $key,
                'version' => $data['version'] ?? 'not cached',
                'checked_at' => !empty($data['checked_at']) ? date('Y-m-d H:i:s', (int) $data['checked_at']) : '',
            ];
        }
        return $rows;
    }

    public static function indexWarnings()
    {
        $checks = [
            ['tenants', 'idx_subdomain_status'],
            ['tenants', 'idx_slug_status'],
            ['tbl_customers', 'idx_tenant_username'],
            ['tbl_customers', 'idx_tenant_service_status'],
            ['tbl_transactions', 'idx_tenant_username_recharged'],
            ['tbl_user_recharges', 'idx_tenant_status_type_expiry'],
            ['tbl_user_recharges', 'idx_tenant_username_expiry'],
            ['tbl_payment_gateway', 'idx_tenant_status_date'],
            ['jovipay_transactions', 'idx_tenant_reference'],
            ['jovipay_transactions', 'idx_tenant_phone_status_created'],
            ['jovipay_transactions', 'idx_tenant_mac_status_created'],
            ['saas_invoices', 'idx_tenant_status_due'],
            ['saas_invoice_payments', 'idx_tenant_transaction'],
        ];
        $rows = [];
        foreach ($checks as $check) {
            $rows[] = [
                'table' => $check[0],
                'index' => $check[1],
                'exists' => self::indexExists($check[0], $check[1]),
            ];
        }
        return $rows;
    }

    public static function cronHealth()
    {
        $health = ['expiry_worker' => 'not found', 'last_run' => '', 'message' => 'Expiry worker table has not been created yet.'];
        try {
            if (!self::tableExists('expiry_worker_runs')) {
                return $health;
            }
            $run = ORM::for_table('expiry_worker_runs')->order_by_desc('id')->find_one();
            if ($run) {
                $health['expiry_worker'] = (string) $run['status'];
                $health['last_run'] = (string) ($run['completed_at'] ?: $run['started_at']);
                $health['message'] = 'Last expiry worker run was recorded.';
            }
        } catch (Throwable $e) {
            $health['message'] = $e->getMessage();
        }
        return $health;
    }

    public static function assetNotes()
    {
        $files = [
            'FASTNETPAY theme CSS' => 'ui/ui/styles/fastnetpay-theme.css',
            'FASTNETPAY UI JS' => 'ui/ui/scripts/fastnetpay-ui.js',
            'FASTNETPAY monitor JS' => 'ui/ui/scripts/fastnetpay-monitor.js',
            'Provisioning JS' => 'ui/ui/scripts/fastnetpay-provisioning.js',
        ];
        $rows = [];
        foreach ($files as $label => $path) {
            $absolute = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $path;
            $rows[] = [
                'label' => $label,
                'path' => $path,
                'kb' => is_file($absolute) ? round(filesize($absolute) / 1024, 1) : 0,
            ];
        }
        return $rows;
    }

    private static function trimSamples()
    {
        $old = ORM::for_table('performance_route_samples')->raw_query('SELECT id FROM performance_route_samples ORDER BY id DESC LIMIT 1 OFFSET 500')->find_one();
        if ($old) {
            ORM::raw_execute('DELETE FROM performance_route_samples WHERE id < ' . (int) $old['id']);
        }
    }

    private static function shouldStoreSample()
    {
        return isset($_GET['fnp_profile'])
            || getenv('FNP_PROFILE_RECORD') === '1'
            || self::$route === 'performance';
    }

    private static function redactQuery($sql)
    {
        $sql = preg_replace("/(password|secret|token|passkey|consumer_secret|api_token)([^,)]*)/i", '$1=***', $sql);
        return substr((string) $sql, 0, 1200);
    }

    private static function indexExists($table, $index)
    {
        try {
            if (!self::tableExists($table)) {
                return false;
            }
            return (bool) ORM::for_table($table)->raw_query("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index])->find_one();
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function tableExists($table)
    {
        try {
            return (bool) ORM::for_table($table)->raw_query("SHOW TABLES LIKE ?", [$table])->find_one();
        } catch (Throwable $e) {
            return false;
        }
    }
}
