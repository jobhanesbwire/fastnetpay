<?php

_admin();
$ui->assign('_title', 'Expiry Worker');
$ui->assign('_system_menu', 'logs');
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

$action = $routes['1'] ?: 'status';

switch ($action) {
    case 'run':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            r2(getUrl('expiry/status'));
        }
        if (!Csrf::check(_post('csrf_token'))) {
            _alert('Invalid CSRF token. Please refresh and try again.', 'danger', 'expiry/status');
        }
        try {
            $result = ExpiryWorker::run(true, $admin['id'] ?? null);
            r2(getUrl('expiry/status'), $result['ok'] ? 's' : 'w', $result['message']);
        } catch (Throwable $e) {
            r2(getUrl('expiry/status'), 'e', $e->getMessage());
        }
        break;

    case 'status':
    default:
        ExpiryWorker::installSchema();
        $ui->assign('health', ExpiryWorker::health($UPLOAD_PATH));
        $ui->assign('runs', ExpiryWorker::recentRuns());
        $ui->assign('logs', ExpiryWorker::recentLogs());
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/expiry/status.tpl');
        break;
}
