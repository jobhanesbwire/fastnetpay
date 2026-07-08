<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

if (Admin::getID()) {
    r2(getUrl('dashboard'), "s", Lang::T("You are already logged in"));
}

if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'login-display';
}

switch ($do) {
    case '2fa':
        if (empty($_SESSION['pending_admin_2fa_id'])) {
            r2(getUrl('admin'), 'e', Lang::T('Please login first'));
        }
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/admin/2fa.tpl');
        break;

    case '2fa-post':
        if (empty($_SESSION['pending_admin_2fa_id'])) {
            r2(getUrl('admin'), 'e', Lang::T('Please login first'));
        }
        if (!Csrf::check(_post('csrf_token'))) {
            _alert(Lang::T('Invalid or Expired CSRF Token') . ".", 'danger', "admin/2fa");
        }
        try {
            $adminId = (int) $_SESSION['pending_admin_2fa_id'];
            if (!class_exists('SaasBilling') || !SaasBilling::verifySuperAdminOtp($adminId, _post('otp'))) {
                _alert('Invalid or expired OTP.', 'danger', 'admin/2fa');
            }
            $d = ORM::for_table('tbl_users')->find_one($adminId);
            if (!$d) {
                unset($_SESSION['pending_admin_2fa_id'], $_SESSION['pending_admin_2fa_username'], $_SESSION['pending_admin_2fa_tenant_id'], $_SESSION['pending_admin_2fa_mode']);
                r2(getUrl('admin'), 'e', Lang::T('Invalid Username or Password'));
            }
            $_SESSION['aid'] = $d['id'];
            if (class_exists('Tenant')) {
                $_SESSION['tenant_id'] = (int) ($_SESSION['pending_admin_2fa_tenant_id'] ?? ($d['tenant_id'] ?: Tenant::currentId()));
                $_SESSION['tenant_mode'] = (string) ($_SESSION['pending_admin_2fa_mode'] ?? Tenant::mode());
            }
            $token = Admin::setCookie($d['id']);
            $d->last_login = date('Y-m-d H:i:s');
            $d->save();
            _log($d['username'] . ' ' . Lang::T('Login Successful'), $d['user_type'], $d['id']);
            unset($_SESSION['pending_admin_2fa_id'], $_SESSION['pending_admin_2fa_username'], $_SESSION['pending_admin_2fa_tenant_id'], $_SESSION['pending_admin_2fa_mode']);
            _alert(Lang::T('Login Successful'), 'success', "dashboard");
        } catch (Throwable $e) {
            _alert($e->getMessage(), 'danger', 'admin/2fa');
        }
        break;

    case '2fa-resend':
        if (empty($_SESSION['pending_admin_2fa_id'])) {
            r2(getUrl('admin'), 'e', Lang::T('Please login first'));
        }
        if (!Csrf::check(_post('csrf_token'))) {
            _alert(Lang::T('Invalid or Expired CSRF Token') . ".", 'danger', "admin/2fa");
        }
        try {
            $d = ORM::for_table('tbl_users')->find_one((int) $_SESSION['pending_admin_2fa_id']);
            if (!$d || !class_exists('SaasBilling')) {
                r2(getUrl('admin'), 'e', Lang::T('Invalid Username or Password'));
            }
            SaasBilling::issueSuperAdminOtp($d);
            _alert('A fresh OTP has been sent.', 'success', 'admin/2fa');
        } catch (Throwable $e) {
            _alert($e->getMessage(), 'danger', 'admin/2fa');
        }
        break;

    case 'post':
        $username = _post('username');
        $password = _post('password');
        //csrf token
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            _alert(Lang::T('Invalid or Expired CSRF Token') . ".", 'danger', "admin");
        }
        run_hook('admin_login'); #HOOK
        if ($username != '' and $password != '') {
            $query = ORM::for_table('tbl_users')->where('username', $username);
            if (class_exists('Tenant') && Tenant::isTenantRequest()) {
                $query->where('tenant_id', Tenant::currentId());
            }
            $d = $query->find_one();
            if ($d) {
                $d_pass = $d['password'];
                if (Password::_verify($password, $d_pass) == true) {
                    if (class_exists('Tenant') && Tenant::isTenantRequest() && $d['user_type'] !== 'SuperAdmin' && (int) ($d['tenant_id'] ?? 0) !== Tenant::currentId()) {
                        Tenant::audit('tenant.login_blocked', 'Login blocked because admin belongs to a different tenant.', 'user', (string) $d['id'], Tenant::currentId(), (int) $d['id']);
                        _alert(Lang::T('This administrator does not belong to this ISP tenant.'), 'danger', 'admin');
                    }
                    if (class_exists('Tenant') && class_exists('SaasBilling') && Tenant::isTenantRequest() && $d['user_type'] !== 'SuperAdmin' && !SaasBilling::tenantCanLogin(Tenant::current())) {
                        Tenant::audit('tenant.login_blocked_suspended', 'Tenant admin login blocked because tenant is suspended.', 'user', (string) $d['id'], Tenant::currentId(), (int) $d['id']);
                        _alert(SaasBilling::suspensionMessage(Tenant::currentId()), 'danger', 'admin');
                    }
                    if (class_exists('SaasBilling') && SaasBilling::requiresSuperAdmin2FA($d)) {
                        if ($isApi) {
                            showResult(false, 'SuperAdmin 2FA is required. Please login from the admin interface.');
                        }
                        $_SESSION['pending_admin_2fa_id'] = (int) $d['id'];
                        $_SESSION['pending_admin_2fa_username'] = (string) $d['username'];
                        $_SESSION['pending_admin_2fa_tenant_id'] = class_exists('Tenant') ? (int) ($d['tenant_id'] ?: Tenant::currentId()) : 0;
                        $_SESSION['pending_admin_2fa_mode'] = class_exists('Tenant') ? Tenant::mode() : 'main';
                        SaasBilling::issueSuperAdminOtp($d);
                        _alert('A SuperAdmin OTP has been sent by SMS.', 'success', 'admin/2fa');
                    }
                    $_SESSION['aid'] = $d['id'];
                    if (class_exists('Tenant')) {
                        $_SESSION['tenant_id'] = (int) ($d['tenant_id'] ?: Tenant::currentId());
                        $_SESSION['tenant_mode'] = Tenant::mode();
                    }
                    $token = Admin::setCookie($d['id']);
                    $d->last_login = date('Y-m-d H:i:s');
                    $d->save();
                    _log($username . ' ' . Lang::T('Login Successful'), $d['user_type'], $d['id']);
                    if (class_exists('Tenant') && Tenant::isTenantRequest()) {
                        Tenant::audit('tenant.login', 'Tenant admin login successful.', 'user', (string) $d['id'], Tenant::currentId(), (int) $d['id']);
                    }
                    if ($isApi) {
                        if ($token) {
                            showResult(true, Lang::T('Login Successful'), ['token' => "a." . $token]);
                        } else {
                            showResult(false, Lang::T('Invalid Username or Password'));
                        }
                    }
                    _alert(Lang::T('Login Successful'), 'success', "dashboard");
                } else {
                    _log($username . ' ' . Lang::T('Failed Login'), $d['user_type']);
                    _alert(Lang::T('Invalid Username or Password') . ".", 'danger', "admin");
                }
            } else {
                _alert(Lang::T('Invalid Username or Password') . "..", 'danger', "admin");
            }
        } else {
            _alert(Lang::T('Invalid Username or Password') . "...", 'danger', "admin");
        }

        break;
    default:
        run_hook('view_login'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/admin/login.tpl');
        break;
}
