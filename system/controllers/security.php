<?php

_admin();
$admin = Admin::_info();
$ui->assign('_admin', $admin);
$ui->assign('_system_menu', 'security');
$ui->assign('_title', 'Security Throttling');

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'], true)) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

if (class_exists('Tenant') && Tenant::isTenantRequest() && $admin['user_type'] !== 'SuperAdmin') {
    _alert(Lang::T('Security throttling is managed from the mother system.'), 'danger', 'dashboard');
}

SecurityThrottle::ensureSchema();
$action = $routes['1'] ?? 'throttle';

switch ($action) {
    case 'settings-post':
        if (!Csrf::check(_post('csrf_token'))) {
            r2(getUrl('security/throttle'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        SecurityThrottle::saveConfig($_POST);
        _log('[' . $admin['username'] . ']: Updated security throttling settings', 'security', $admin['id']);
        r2(getUrl('security/throttle'), 's', Lang::T('Settings Saved Successfully'));
        break;

    case 'rule-post':
        if (!Csrf::check(_post('csrf_token'))) {
            r2(getUrl('security/throttle'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            SecurityThrottle::addRule(
                _post('rule_type'),
                _post('value'),
                _post('action_type'),
                _post('reason'),
                _post('expires_at'),
                (int) $admin['id']
            );
            _log('[' . $admin['username'] . ']: Added security throttle rule', 'security', $admin['id']);
            r2(getUrl('security/throttle'), 's', 'Security rule saved.');
        } catch (Throwable $e) {
            r2(getUrl('security/throttle'), 'e', $e->getMessage());
        }
        break;

    case 'rule-delete':
        if (!Csrf::check(_req('csrf_token'))) {
            r2(getUrl('security/throttle'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        SecurityThrottle::deleteRule((int) ($routes['2'] ?? 0));
        _log('[' . $admin['username'] . ']: Deleted security throttle rule', 'security', $admin['id']);
        r2(getUrl('security/throttle'), 's', 'Security rule deleted.');
        break;

    case 'block-ip':
        if (!Csrf::check(_req('csrf_token'))) {
            r2(getUrl('security/throttle'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            SecurityThrottle::addRule('ip', _req('ip'), 'block', 'Blocked from throttle log', '', (int) $admin['id']);
            r2(getUrl('security/throttle'), 's', 'IP blocked.');
        } catch (Throwable $e) {
            r2(getUrl('security/throttle'), 'e', $e->getMessage());
        }
        break;

    case 'whitelist-ip':
        if (!Csrf::check(_req('csrf_token'))) {
            r2(getUrl('security/throttle'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            SecurityThrottle::addRule('ip', _req('ip'), 'whitelist', 'Whitelisted from throttle log', '', (int) $admin['id']);
            r2(getUrl('security/throttle'), 's', 'IP whitelisted.');
        } catch (Throwable $e) {
            r2(getUrl('security/throttle'), 'e', $e->getMessage());
        }
        break;

    case 'cleanup':
        if (!Csrf::check(_req('csrf_token'))) {
            r2(getUrl('security/throttle'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        SecurityThrottle::purge((int) _req('days', 14));
        r2(getUrl('security/throttle'), 's', 'Old throttle events cleaned.');
        break;

    case 'throttle':
    default:
        $filters = [
            'ip' => _req('ip'),
            'action' => _req('action'),
        ];
        $ui->assign('stats', SecurityThrottle::stats());
        $ui->assign('throttle_config', SecurityThrottle::config($config));
        $ui->assign('events', SecurityThrottle::events($filters, 150));
        $ui->assign('rules', SecurityThrottle::rules());
        $ui->assign('filters', $filters);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/security/throttle.tpl');
        break;
}
