<?php

class ExpiryWorker
{
    public static function installSchema()
    {
        ORM::raw_execute("CREATE TABLE IF NOT EXISTS expiry_worker_runs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            run_type VARCHAR(32) NOT NULL DEFAULT 'cron',
            status VARCHAR(32) NOT NULL DEFAULT 'running',
            expired_found INT UNSIGNED NOT NULL DEFAULT 0,
            disconnected_count INT UNSIGNED NOT NULL DEFAULT 0,
            failed_count INT UNSIGNED NOT NULL DEFAULT 0,
            started_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            created_by INT UNSIGNED NULL,
            summary TEXT NULL,
            INDEX started_at_idx (started_at),
            INDEX status_idx (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS expiry_worker_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            run_id INT UNSIGNED NULL,
            recharge_id INT UNSIGNED NULL,
            customer_id INT UNSIGNED NULL,
            username VARCHAR(190) NULL,
            router_name VARCHAR(190) NULL,
            service_type VARCHAR(64) NULL,
            action VARCHAR(80) NOT NULL DEFAULT 'disconnect',
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            message TEXT NULL,
            created_at DATETIME NOT NULL,
            INDEX run_id_idx (run_id),
            INDEX username_idx (username),
            INDEX created_at_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function health($uploadPath = null)
    {
        self::installSchema();
        $lastSuccess = self::setting('expiry_worker_last_success');
        $lastRun = self::setting('expiry_worker_last_run');
        $cronFileTime = null;
        if ($uploadPath) {
            $file = rtrim($uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cron_last_run.txt';
            if (is_file($file)) {
                $cronFileTime = (int) trim((string) file_get_contents($file));
            }
        }
        $lastTs = $lastSuccess ? strtotime($lastSuccess) : 0;
        if (!$lastTs && $cronFileTime) {
            $lastTs = $cronFileTime;
        }
        $age = $lastTs ? time() - $lastTs : null;
        $ok = $age !== null && $age <= 7200;
        return [
            'ok' => $ok,
            'last_run' => $lastRun,
            'last_success' => $lastSuccess,
            'cron_file_time' => $cronFileTime ? date('Y-m-d H:i:s', $cronFileTime) : '',
            'age_seconds' => $age,
            'message' => $ok ? 'Expiry worker is healthy.' : 'Cron/Expiry Worker not running. Expired users may keep browsing until this is fixed.',
        ];
    }

    public static function recentRuns($limit = 20)
    {
        self::installSchema();
        return ORM::for_table('expiry_worker_runs')->order_by_desc('id')->limit((int) $limit)->find_many();
    }

    public static function recentLogs($limit = 80)
    {
        self::installSchema();
        return ORM::for_table('expiry_worker_logs')->order_by_desc('id')->limit((int) $limit)->find_many();
    }

    public static function run($manual = false, $adminId = null, $limit = 200)
    {
        global $_app_stage;
        self::installSchema();
        $run = ORM::for_table('expiry_worker_runs')->create();
        $run->run_type = $manual ? 'manual' : 'cron';
        $run->status = 'running';
        $run->started_at = date('Y-m-d H:i:s');
        $run->created_by = $adminId ? (int) $adminId : null;
        $run->save();

        $expired = ORM::for_table('tbl_user_recharges')
            ->where('status', 'on')
            ->where_raw("UNIX_TIMESTAMP(CONCAT(`expiration`,' ',`time`)) <= UNIX_TIMESTAMP(NOW())")
            ->limit((int) $limit)
            ->find_many();

        $found = count($expired);
        $ok = 0;
        $failed = 0;
        foreach ($expired as $recharge) {
            try {
                self::disconnectRecharge($run->id(), $recharge, $_app_stage === 'demo');
                $ok++;
            } catch (Throwable $e) {
                $failed++;
                self::log($run->id(), $recharge, 'failed', $e->getMessage());
                _log('FASTNETPAY expiry worker failed for ' . $recharge['username'] . ': ' . $e->getMessage());
            }
        }

        $run->expired_found = $found;
        $run->disconnected_count = $ok;
        $run->failed_count = $failed;
        $run->status = $failed > 0 ? 'warning' : 'success';
        $run->completed_at = date('Y-m-d H:i:s');
        $run->summary = 'Found ' . $found . ' expired active user(s), disconnected ' . $ok . ', failed ' . $failed . '.';
        $run->save();

        self::saveSetting('expiry_worker_last_run', date('Y-m-d H:i:s'));
        if ($failed === 0) {
            self::saveSetting('expiry_worker_last_success', date('Y-m-d H:i:s'));
        }

        return [
            'ok' => $failed === 0,
            'run_id' => $run->id(),
            'found' => $found,
            'disconnected' => $ok,
            'failed' => $failed,
            'message' => $run->summary,
        ];
    }

    private static function disconnectRecharge($runId, $recharge, $demo = false)
    {
        global $DEVICE_PATH;
        $customer = ORM::for_table('tbl_customers')->where('id', $recharge['customer_id'])->find_one();
        if (!$customer) {
            $customer = $recharge;
        }
        $plan = ORM::for_table('tbl_plans')->where('id', $recharge['plan_id'])->find_one();
        if (!$plan) {
            throw new Exception('Plan not found for recharge #' . $recharge['id']);
        }
        $plan->routers = $recharge['routers'] ?: $plan['routers'];
        if (!empty($recharge['namebp'])) {
            $plan->name_plan = $recharge['namebp'];
        }

        $devicePath = Package::getDevice($plan);
        if (!$demo) {
            if (!file_exists($devicePath)) {
                throw new Exception('Device adapter not found for ' . $plan['device']);
            }
            require_once $devicePath;
            (new $plan['device'])->remove_customer($customer, $plan);
        }

        $recharge->status = 'off';
        $recharge->save();
        self::log($runId, $recharge, 'success', 'Expired access disconnected on router ' . ($recharge['routers'] ?: 'default') . '.');
    }

    private static function log($runId, $recharge, $status, $message)
    {
        $row = ORM::for_table('expiry_worker_logs')->create();
        $row->run_id = (int) $runId;
        $row->recharge_id = (int) $recharge['id'];
        $row->customer_id = (int) $recharge['customer_id'];
        $row->username = $recharge['username'];
        $row->router_name = $recharge['routers'];
        $row->service_type = $recharge['type'];
        $row->action = 'disconnect-expired';
        $row->status = $status;
        $row->message = substr(strip_tags((string) $message), 0, 2000);
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
    }

    private static function setting($key)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        return $row ? (string) $row['value'] : '';
    }

    private static function saveSetting($key, $value)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if (!$row) {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $key;
        }
        $row->value = $value;
        $row->save();
        global $config;
        $config[$key] = $value;
    }
}
