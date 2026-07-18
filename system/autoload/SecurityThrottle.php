<?php

class SecurityThrottle
{
    const SCHEMA_VERSION = '2026-07-17-throttle1';

    const DEFAULTS = [
        'security_throttle_enabled' => 'yes',
        'security_throttle_window_seconds' => '60',
        'security_throttle_guest_limit' => '120',
        'security_throttle_auth_limit' => '240',
        'security_throttle_login_limit' => '15',
        'security_throttle_api_limit' => '180',
        'security_throttle_payment_limit' => '300',
        'security_throttle_block_minutes' => '15',
        'security_throttle_event_retention_days' => '14',
        'security_trust_proxy_headers' => 'no',
        'security_robots_headers_enabled' => 'yes',
        'security_ai_bot_block_enabled' => 'yes',
    ];

    const AI_USER_AGENTS = [
        'GPTBot',
        'ChatGPT-User',
        'OAI-SearchBot',
        'ClaudeBot',
        'Claude-Web',
        'anthropic-ai',
        'PerplexityBot',
        'Perplexity-User',
        'Bytespider',
        'CCBot',
        'Google-Extended',
        'Applebot-Extended',
        'Amazonbot',
        'FacebookBot',
        'Meta-ExternalAgent',
        'Diffbot',
        'YouBot',
        'cohere-ai',
        'omgili',
    ];

    public static function boot($config, $route)
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        self::sendPrivacyHeaders($config);

        $route = trim((string) $route, '/');
        $enabled = self::cfg($config, 'security_throttle_enabled') === 'yes';
        $aiBlockEnabled = self::cfg($config, 'security_ai_bot_block_enabled') === 'yes';
        if (!$enabled && !$aiBlockEnabled) {
            return;
        }

        try {
            self::ensureSchema();
        } catch (Throwable $e) {
            error_log('FASTNETPAY throttle schema unavailable: ' . $e->getMessage());
            return;
        }

        $ip = self::clientIp($config);
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if (self::matchesRule('whitelist', $ip, $ua, $route)) {
            return;
        }

        $blocked = self::matchesRule('block', $ip, $ua, $route);
        if ($blocked) {
            self::logEvent($ip, $route, $method, $ua, 'blocked', $blocked['reason'] ?: 'Manual block rule', 0, 0, 0);
            self::deny(403, 'Request blocked by FASTNETPAY security policy.');
        }

        if ($aiBlockEnabled && self::isAiUserAgent($ua)) {
            self::logEvent($ip, $route, $method, $ua, 'ai_blocked', 'Known AI crawler or AI-assisted browser user-agent', 0, 0, 0);
            self::deny(403, 'Automated AI crawling is not permitted on this FASTNETPAY system.');
        }

        if (!$enabled) {
            return;
        }

        $policy = self::policy($config, $route);
        if ($policy['limit'] <= 0 || $policy['window'] <= 0) {
            return;
        }

        try {
            self::rateLimit($ip, $route, $method, $ua, $policy);
            self::maybePurge($config);
        } catch (Throwable $e) {
            error_log('FASTNETPAY throttle skipped: ' . $e->getMessage());
        }
    }

    public static function ensureSchema()
    {
        if (class_exists('FastnetpayRuntime') && FastnetpayRuntime::schemaFresh('security_throttle', self::SCHEMA_VERSION, 86400)) {
            return;
        }

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS security_rate_counters (
            rate_key CHAR(64) NOT NULL PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            route_group VARCHAR(80) NOT NULL,
            hits INT UNSIGNED NOT NULL DEFAULT 0,
            window_start DATETIME NOT NULL,
            blocked_until DATETIME NULL,
            last_seen DATETIME NOT NULL,
            INDEX idx_ip_last_seen (ip, last_seen),
            INDEX idx_blocked_until (blocked_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS security_throttle_events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            route VARCHAR(180) NOT NULL DEFAULT '',
            method VARCHAR(12) NOT NULL DEFAULT 'GET',
            user_agent VARCHAR(500) NOT NULL DEFAULT '',
            action VARCHAR(40) NOT NULL,
            reason VARCHAR(255) NOT NULL DEFAULT '',
            hit_count INT UNSIGNED NOT NULL DEFAULT 0,
            limit_count INT UNSIGNED NOT NULL DEFAULT 0,
            window_seconds INT UNSIGNED NOT NULL DEFAULT 0,
            retry_after INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX idx_created_at (created_at),
            INDEX idx_ip_created (ip, created_at),
            INDEX idx_action_created (action, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS security_throttle_rules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            rule_type VARCHAR(24) NOT NULL,
            value VARCHAR(255) NOT NULL,
            action VARCHAR(24) NOT NULL,
            reason VARCHAR(255) NOT NULL DEFAULT '',
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            expires_at DATETIME NULL,
            created_by INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            INDEX idx_action_enabled (action, enabled),
            INDEX idx_rule_type (rule_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if (class_exists('FastnetpayRuntime')) {
            FastnetpayRuntime::markSchemaFresh('security_throttle', self::SCHEMA_VERSION);
        }
    }

    public static function config($config)
    {
        $out = [];
        foreach (self::DEFAULTS as $key => $default) {
            $out[$key] = self::cfg($config, $key, $default);
        }
        return $out;
    }

    public static function saveConfig($values)
    {
        foreach (self::DEFAULTS as $key => $default) {
            $value = isset($values[$key]) ? trim((string) $values[$key]) : $default;
            if (in_array($key, ['security_throttle_enabled', 'security_trust_proxy_headers', 'security_robots_headers_enabled', 'security_ai_bot_block_enabled'], true)) {
                $value = $value === 'yes' ? 'yes' : 'no';
            } else {
                $value = (string) max(0, (int) $value);
            }
            self::saveAppConfig($key, $value);
        }
    }

    public static function addRule($type, $value, $action, $reason, $expiresAt, $adminId)
    {
        self::ensureSchema();
        $type = in_array($type, ['ip', 'cidr', 'user_agent', 'route'], true) ? $type : 'ip';
        $action = in_array($action, ['whitelist', 'block'], true) ? $action : 'block';
        $value = trim((string) $value);
        if ($value === '') {
            throw new Exception('Rule value is required.');
        }
        $row = ORM::for_table('security_throttle_rules')->create();
        $row->rule_type = $type;
        $row->value = substr($value, 0, 255);
        $row->action = $action;
        $row->reason = substr(trim((string) $reason), 0, 255);
        $row->enabled = 1;
        $row->expires_at = self::validDateTime($expiresAt);
        $row->created_by = (int) $adminId;
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
    }

    public static function deleteRule($id)
    {
        $rule = ORM::for_table('security_throttle_rules')->find_one((int) $id);
        if ($rule) {
            $rule->delete();
        }
    }

    public static function stats()
    {
        self::ensureSchema();
        $day = date('Y-m-d H:i:s', time() - 86400);
        $hour = date('Y-m-d H:i:s', time() - 3600);
        return [
            'events_24h' => (int) ORM::for_table('security_throttle_events')->where_gte('created_at', $day)->count(),
            'events_1h' => (int) ORM::for_table('security_throttle_events')->where_gte('created_at', $hour)->count(),
            'blocked_24h' => (int) ORM::for_table('security_throttle_events')->where_gte('created_at', $day)->where_in('action', ['throttled', 'blocked', 'ai_blocked'])->count(),
            'rules' => (int) ORM::for_table('security_throttle_rules')->where('enabled', 1)->count(),
        ];
    }

    public static function events($filters = [], $limit = 100)
    {
        self::ensureSchema();
        $query = ORM::for_table('security_throttle_events')->order_by_desc('id');
        if (!empty($filters['ip'])) {
            $query->where_like('ip', '%' . trim((string) $filters['ip']) . '%');
        }
        if (!empty($filters['action'])) {
            $query->where('action', trim((string) $filters['action']));
        }
        return $query->limit(max(10, min(500, (int) $limit)))->find_array();
    }

    public static function rules()
    {
        self::ensureSchema();
        return ORM::for_table('security_throttle_rules')->order_by_desc('id')->find_array();
    }

    public static function purge($days)
    {
        $days = max(1, (int) $days);
        ORM::raw_execute("DELETE FROM security_throttle_events WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
        ORM::raw_execute("DELETE FROM security_rate_counters WHERE last_seen < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }

    private static function rateLimit($ip, $route, $method, $ua, $policy)
    {
        $now = time();
        $routeGroup = $policy['group'];
        $key = sha1($ip . '|' . $method . '|' . $routeGroup);
        $row = ORM::for_table('security_rate_counters')->where('rate_key', $key)->find_one();
        if (!$row) {
            $row = ORM::for_table('security_rate_counters')->create();
            $row->rate_key = $key;
            $row->ip = $ip;
            $row->route_group = $routeGroup;
            $row->hits = 0;
            $row->window_start = date('Y-m-d H:i:s', $now);
        }

        $blockedUntil = $row['blocked_until'] ? strtotime($row['blocked_until']) : 0;
        if ($blockedUntil > $now) {
            $retry = max(1, $blockedUntil - $now);
            self::logEvent($ip, $route, $method, $ua, 'throttled', $policy['reason'], (int) $row['hits'], $policy['limit'], $policy['window'], $retry);
            self::deny(429, 'Too many requests. Please try again later.', $retry);
        }

        $windowStart = $row['window_start'] ? strtotime($row['window_start']) : 0;
        if (!$windowStart || ($now - $windowStart) >= $policy['window']) {
            $row->hits = 1;
            $row->window_start = date('Y-m-d H:i:s', $now);
            $row->blocked_until = null;
        } else {
            $row->hits = (int) $row['hits'] + 1;
        }
        $row->last_seen = date('Y-m-d H:i:s', $now);

        if ((int) $row->hits > $policy['limit']) {
            $retry = max(60, (int) $policy['block_minutes'] * 60);
            $row->blocked_until = date('Y-m-d H:i:s', $now + $retry);
            $row->save();
            self::logEvent($ip, $route, $method, $ua, 'throttled', $policy['reason'], (int) $row->hits, $policy['limit'], $policy['window'], $retry);
            self::deny(429, 'Too many requests. Please try again later.', $retry);
        }
        $row->save();
    }

    private static function policy($config, $route)
    {
        $window = max(10, (int) self::cfg($config, 'security_throttle_window_seconds'));
        $blockMinutes = max(1, (int) self::cfg($config, 'security_throttle_block_minutes'));
        $route = trim((string) $route, '/');
        if (preg_match('#^(admin|login|forgot|register)(/|$)#i', $route)) {
            return ['group' => 'auth', 'limit' => max(1, (int) self::cfg($config, 'security_throttle_login_limit')), 'window' => $window, 'block_minutes' => $blockMinutes, 'reason' => 'Authentication route throttled'];
        }
        if (preg_match('#^(api/jovipay/callback|api/hotspot/pay|callback|paymentgateway)(/|$)#i', $route)) {
            return ['group' => 'payment', 'limit' => max(1, (int) self::cfg($config, 'security_throttle_payment_limit')), 'window' => $window, 'block_minutes' => $blockMinutes, 'reason' => 'Payment/API route throttled'];
        }
        if (preg_match('#^api(/|$)#i', $route)) {
            return ['group' => 'api', 'limit' => max(1, (int) self::cfg($config, 'security_throttle_api_limit')), 'window' => $window, 'block_minutes' => $blockMinutes, 'reason' => 'API route throttled'];
        }
        $authenticated = !empty($_SESSION['aid']) || !empty($_SESSION['uid']);
        return [
            'group' => $authenticated ? 'auth-user' : 'guest',
            'limit' => max(1, (int) self::cfg($config, $authenticated ? 'security_throttle_auth_limit' : 'security_throttle_guest_limit')),
            'window' => $window,
            'block_minutes' => $blockMinutes,
            'reason' => $authenticated ? 'Authenticated route throttled' : 'Guest route throttled',
        ];
    }

    private static function matchesRule($action, $ip, $ua, $route)
    {
        static $rulesByAction = [];
        if (!isset($rulesByAction[$action])) {
            $rulesByAction[$action] = ORM::for_table('security_throttle_rules')
                ->where('enabled', 1)
                ->where('action', $action)
                ->find_array();
        }
        $rules = $rulesByAction[$action];
        $now = time();
        foreach ($rules as $rule) {
            if (!empty($rule['expires_at']) && strtotime($rule['expires_at']) < $now) {
                continue;
            }
            $value = (string) $rule['value'];
            $match = false;
            if ($rule['rule_type'] === 'ip') {
                $match = hash_equals($value, $ip);
            } elseif ($rule['rule_type'] === 'cidr') {
                $match = self::cidrMatch($ip, $value);
            } elseif ($rule['rule_type'] === 'user_agent') {
                $match = $value !== '' && stripos($ua, $value) !== false;
            } elseif ($rule['rule_type'] === 'route') {
                $match = $value !== '' && stripos($route, $value) === 0;
            }
            if ($match) {
                return $rule;
            }
        }
        return false;
    }

    private static function isAiUserAgent($ua)
    {
        if ($ua === '') {
            return false;
        }
        foreach (self::AI_USER_AGENTS as $needle) {
            if (stripos($ua, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function logEvent($ip, $route, $method, $ua, $action, $reason, $hits, $limit, $window, $retry = 0)
    {
        $row = ORM::for_table('security_throttle_events')->create();
        $row->ip = substr((string) $ip, 0, 45);
        $row->route = substr((string) $route, 0, 180);
        $row->method = substr((string) $method, 0, 12);
        $row->user_agent = substr((string) $ua, 0, 500);
        $row->action = substr((string) $action, 0, 40);
        $row->reason = substr((string) $reason, 0, 255);
        $row->hit_count = (int) $hits;
        $row->limit_count = (int) $limit;
        $row->window_seconds = (int) $window;
        $row->retry_after = (int) $retry;
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
    }

    private static function deny($status, $message, $retryAfter = 0)
    {
        http_response_code($status);
        if ($retryAfter > 0) {
            header('Retry-After: ' . (int) $retryAfter);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'failed',
            'message' => $message,
        ]);
        exit;
    }

    private static function sendPrivacyHeaders($config)
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=()');
        if (self::cfg($config, 'security_robots_headers_enabled') === 'yes') {
            header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noai, noimageai');
        }
    }

    private static function clientIp($config)
    {
        $trustProxy = self::cfg($config, 'security_trust_proxy_headers') === 'yes';
        if ($trustProxy) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP'] as $header) {
                if (!empty($_SERVER[$header]) && filter_var($_SERVER[$header], FILTER_VALIDATE_IP)) {
                    return $_SERVER[$header];
                }
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($parts as $part) {
                    $candidate = trim($part);
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function cidrMatch($ip, $cidr)
    {
        if (strpos($cidr, '/') === false) {
            return hash_equals($cidr, $ip);
        }
        [$subnet, $bits] = explode('/', $cidr, 2);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $bits = (int) $bits;
        if ($bits < 0 || $bits > 32) {
            return false;
        }
        $mask = $bits === 0 ? 0 : (-1 << (32 - $bits));
        return (ip2long($ip) & $mask) === (ip2long($subnet) & $mask);
    }

    private static function maybePurge($config)
    {
        if (mt_rand(1, 1000) === 1) {
            self::purge((int) self::cfg($config, 'security_throttle_event_retention_days'));
        }
    }

    private static function cfg($config, $key, $default = null)
    {
        if ($default === null && isset(self::DEFAULTS[$key])) {
            $default = self::DEFAULTS[$key];
        }
        return isset($config[$key]) && trim((string) $config[$key]) !== '' ? trim((string) $config[$key]) : $default;
    }

    private static function saveAppConfig($key, $value)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if (!$row) {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $key;
        }
        $row->value = $value;
        $row->save();
    }

    private static function validDateTime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
