<?php

/**
 * FASTNETPAY Jovi-Pay payment orchestration admin pages.
 */

_admin();
$ui->assign('_title', 'Jovi-Pay Integration');
$ui->assign('_system_menu', 'paymentgateway');
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

JoviPay::installSchema();

$action = $routes['1'] ?: 'settings';

switch ($action) {
    case 'settings-post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            r2(getUrl('jovipay/settings'));
        }
        if (!Csrf::check(_post('csrf_token'))) {
            _alert('Invalid CSRF token. Please refresh and try again.', 'danger', 'jovipay/settings');
        }
        try {
            JoviPay::saveFromPost($admin);
            r2(getUrl('jovipay/settings'), 's', 'Jovi-Pay integration settings saved.');
        } catch (Throwable $e) {
            r2(getUrl('jovipay/settings'), 'e', $e->getMessage());
        }
        break;

    case 'transactions':
        $status = _req('status', 'all');
        $allowed = ['all', 'pending', 'success', 'failed', 'ignored', 'reconnected', 'unmatched'];
        if (!in_array($status, $allowed, true)) {
            $status = 'all';
        }
        $q = trim(_req('q', ''));
        $ui->assign('_title', 'Jovi-Pay Transactions');
        $ui->assign('status', $status);
        $ui->assign('q', $q);
        $ui->assign('summary', JoviPay::summary());
        $ui->assign('transactions', JoviPay::transactions($status, $q));
        $ui->display('admin/jovipay/transactions.tpl');
        break;

    case 'settings':
    default:
        $ui->assign('jovipay', JoviPay::publicSettings());
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/jovipay/settings.tpl');
        break;
}
