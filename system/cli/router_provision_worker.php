<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "FASTNETPAY router provisioning worker must run from CLI.\n");
    exit(1);
}

$runId = (int) ($argv[1] ?? 0);
if ($runId <= 0) {
    fwrite(STDERR, "Usage: php system/cli/router_provision_worker.php <run_id>\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
chdir($root);

$_SERVER['HTTP_HOST'] = getenv('FASTNETPAY_WORKER_HOST') ?: 'mother.fastnetpay.co.ke';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_URI'] = '/';
$_COOKIE['kolaps'] = '';
$_SESSION = [];

require $root . '/system/vendor/autoload.php';
require $root . '/init.php';

try {
    RouterProvisioning::runQueuedProvisioning($runId);
} catch (Throwable $e) {
    try {
        $run = ORM::for_table('router_provisioning_runs')->find_one($runId);
        if ($run) {
            $run->status = 'failed';
            $run->completed_at = date('Y-m-d H:i:s');
            $run->notes = trim((string) $run->notes) . "\nWorker failed: " . RouterProvisioning::redactForLog($e->getMessage());
            $run->save();
        }
        $step = ORM::for_table('router_provisioning_steps')->create();
        $step->run_id = $runId;
        $step->step_name = 'Provisioning Worker';
        $step->status = 'failed';
        $step->error_message = RouterProvisioning::redactForLog($e->getMessage());
        $step->started_at = date('Y-m-d H:i:s');
        $step->completed_at = date('Y-m-d H:i:s');
        $step->save();
    } catch (Throwable $logError) {
        fwrite(STDERR, "Worker failed and could not log failure: " . $logError->getMessage() . "\n");
    }
    fwrite(STDERR, "Provisioning worker failed: " . $e->getMessage() . "\n");
    exit(1);
}
