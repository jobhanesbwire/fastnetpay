<?php

/**
 * Small runtime helpers for FASTNETPAY performance-sensitive paths.
 */
class FastnetpayRuntime
{
    public static function cacheDir($scope = 'runtime')
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $scope;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public static function schemaFresh($key, $version, $ttl = 86400)
    {
        if (isset($_GET['refresh_schema']) || getenv('FNP_REFRESH_SCHEMA') === '1') {
            return false;
        }
        $file = self::cacheDir('schema') . DIRECTORY_SEPARATOR . self::safeKey($key) . '.json';
        if (!is_file($file)) {
            return false;
        }
        $data = json_decode((string) @file_get_contents($file), true);
        if (!is_array($data) || ($data['version'] ?? '') !== (string) $version) {
            return false;
        }
        return ((int) ($data['checked_at'] ?? 0) + (int) $ttl) > time();
    }

    public static function markSchemaFresh($key, $version)
    {
        $file = self::cacheDir('schema') . DIRECTORY_SEPARATOR . self::safeKey($key) . '.json';
        @file_put_contents($file, json_encode([
            'version' => (string) $version,
            'checked_at' => time(),
        ]));
    }

    public static function isDevelopment($stage = '', $config = [])
    {
        $env = strtolower(trim((string) ($config['app_env'] ?? getenv('APP_ENV') ?: '')));
        $stage = strtolower(trim((string) $stage));
        return isset($_GET['fnp_profile'])
            || in_array($env, ['local', 'dev', 'development'], true)
            || ($stage !== '' && $stage !== 'live');
    }

    public static function safeKey($value)
    {
        return preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $value);
    }
}
