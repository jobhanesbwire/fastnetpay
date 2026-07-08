<?php

/**
 * FASTNETPAY SaaS Management.
 */

_admin();

if (!class_exists('Tenant')) {
    _alert('SaaS tenancy helper is not available.', 'danger', 'dashboard');
}

Tenant::installSchema();
if (class_exists('SaasBilling')) {
    SaasBilling::installSchema();
}
$ui->assign('_system_menu', 'saas');
$ui->assign('_admin', $admin);

if (!$admin || $admin['user_type'] !== 'SuperAdmin') {
    Tenant::audit('tenant.superadmin_access_denied', 'Non-SuperAdmin attempted to open SaaS Management.', 'user', (string) ($admin['id'] ?? 0), Tenant::currentId(), (int) ($admin['id'] ?? 0));
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

$action = $routes['1'] ?: 'tenants';

switch ($action) {
    case 'add':
        $ui->assign('_title', 'Add Tenant / ISP');
        $ui->assign('tenant', null);
        $ui->assign('plans', ORM::for_table('tenant_subscription_plans')->where('status', 'active')->order_by_asc('id')->find_many());
        $ui->assign('payment_settings', fnp_saas_payment_settings(0));
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/saas/form.tpl');
        break;

    case 'create-post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('saas/add'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        try {
            $tenant = Tenant::createTenant(fnp_saas_tenant_input(), (int) $admin['id']);
            fnp_saas_save_payment_settings((int) $tenant['id'], (int) $admin['id']);
            if (trim(_post('admin_username')) !== '' && trim(_post('admin_password')) !== '') {
                Tenant::createTenantAdmin((int) $tenant['id'], fnp_saas_admin_input(), (int) $admin['id']);
            }
            r2(getUrl('saas/tenants'), 's', 'Tenant created successfully.');
        } catch (Throwable $e) {
            r2(getUrl('saas/add'), 'e', $e->getMessage());
        }
        break;

    case 'edit':
        $tenant = ORM::for_table('tenants')->find_one((int) ($routes['2'] ?? 0));
        if (!$tenant) {
            r2(getUrl('saas/tenants'), 'e', 'Tenant not found.');
        }
        $ui->assign('_title', 'Edit Tenant / ISP');
        $ui->assign('tenant', $tenant);
        $ui->assign('plans', ORM::for_table('tenant_subscription_plans')->where('status', 'active')->order_by_asc('id')->find_many());
        $ui->assign('payment_settings', fnp_saas_payment_settings((int) $tenant['id']));
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/saas/form.tpl');
        break;

    case 'update-post':
        $tenant = ORM::for_table('tenants')->find_one((int) _post('tenant_id'));
        if (!$tenant) {
            r2(getUrl('saas/tenants'), 'e', 'Tenant not found.');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('saas/edit/' . $tenant['id']), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        try {
            Tenant::updateTenant($tenant, fnp_saas_tenant_input(), (int) $admin['id']);
            fnp_saas_save_payment_settings((int) $tenant['id'], (int) $admin['id']);
            r2(getUrl('saas/edit/' . $tenant['id']), 's', 'Tenant updated successfully.');
        } catch (Throwable $e) {
            r2(getUrl('saas/edit/' . $tenant['id']), 'e', $e->getMessage());
        }
        break;

    case 'admin-post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('saas/tenants'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        try {
            Tenant::createTenantAdmin((int) _post('tenant_id'), fnp_saas_admin_input(), (int) $admin['id']);
            r2(getUrl('saas/edit/' . (int) _post('tenant_id')), 's', 'Tenant admin created.');
        } catch (Throwable $e) {
            r2(getUrl('saas/edit/' . (int) _post('tenant_id')), 'e', $e->getMessage());
        }
        break;

    case 'activate':
    case 'suspend':
        $tenant = ORM::for_table('tenants')->find_one((int) ($routes['2'] ?? 0));
        if (!$tenant) {
            r2(getUrl('saas/tenants'), 'e', 'Tenant not found.');
        }
        if ($action === 'activate') {
            SaasBilling::restoreTenant((int) $tenant['id'], (int) $admin['id'], 'Manual tenant activation.');
        } else {
            SaasBilling::suspendTenant((int) $tenant['id'], null, (int) $admin['id'], 'Manual tenant suspension.');
        }
        r2(getUrl('saas/tenants'), 's', 'Tenant status updated.');
        break;

    case 'billing':
        $tenants = ORM::for_table('tenants')->where_not_equal('slug', 'main')->order_by_asc('name')->find_many();
        $previews = [];
        foreach ($tenants as $tenant) {
            try {
                $previews[] = SaasBilling::previewInvoice((int) $tenant['id']);
            } catch (Throwable $ignored) {
            }
        }
        $ui->assign('_title', 'SaaS Plans / Billing');
        $ui->assign('settings', SaasBilling::settings());
        $ui->assign('bands', SaasBilling::bands());
        $ui->assign('tenants', $tenants);
        $ui->assign('previews', $previews);
        $ui->assign('invoices', SaasBilling::invoices(120));
        $ui->assign('analytics', SaasBilling::analytics());
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/saas/billing.tpl');
        break;

    case 'billing-save-settings':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('saas/billing'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        try {
            SaasBilling::saveSettingsFromPost((int) $admin['id']);
            r2(getUrl('saas/billing'), 's', 'SaaS billing settings saved.');
        } catch (Throwable $e) {
            r2(getUrl('saas/billing'), 'e', $e->getMessage());
        }
        break;

    case 'billing-save-band':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('saas/billing'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        try {
            SaasBilling::saveBandFromPost((int) $admin['id']);
            r2(getUrl('saas/billing'), 's', 'Billing band saved.');
        } catch (Throwable $e) {
            r2(getUrl('saas/billing'), 'e', $e->getMessage());
        }
        break;

    case 'billing-generate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('saas/billing'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        try {
            $tenantId = (int) _post('tenant_id');
            $count = 0;
            if ($tenantId > 0) {
                SaasBilling::generateInvoice($tenantId, _post('billing_month') ?: null, (int) $admin['id']);
                $count = 1;
            } else {
                foreach (ORM::for_table('tenants')->where_not_equal('slug', 'main')->find_many() as $tenant) {
                    SaasBilling::generateInvoice((int) $tenant['id'], _post('billing_month') ?: null, (int) $admin['id']);
                    $count++;
                }
            }
            r2(getUrl('saas/billing'), 's', $count . ' SaaS invoice(s) generated.');
        } catch (Throwable $e) {
            r2(getUrl('saas/billing'), 'e', $e->getMessage());
        }
        break;

    case 'invoice':
        $invoice = ORM::for_table('saas_invoices')->find_one((int) ($routes['2'] ?? 0));
        if (!$invoice) {
            r2(getUrl('saas/billing'), 'e', 'Invoice not found.');
        }
        $ui->assign('_title', 'SaaS Invoice ' . $invoice['invoice_number']);
        $ui->assign('invoice', $invoice);
        $ui->assign('tenant', ORM::for_table('tenants')->find_one((int) $invoice['tenant_id']));
        $ui->assign('items', SaasBilling::invoiceItems((int) $invoice['id']));
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/saas/invoice.tpl');
        break;

    case 'invoice-paid':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('saas/billing'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        try {
            $invoice = SaasBilling::markPaid((int) ($routes['2'] ?? 0), (int) $admin['id']);
            r2(getUrl('saas/invoice/' . (int) $invoice['id']), 's', 'Invoice marked paid and tenant restored if suspended.');
        } catch (Throwable $e) {
            r2(getUrl('saas/billing'), 'e', $e->getMessage());
        }
        break;

    case 'tenant-suspend':
    case 'tenant-restore':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('saas/billing'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        try {
            $tenantId = (int) ($routes['2'] ?? 0);
            if ($action === 'tenant-suspend') {
                SaasBilling::suspendTenant($tenantId, null, (int) $admin['id'], trim((string) _post('reason', 'Manual billing suspension.')));
            } else {
                SaasBilling::restoreTenant($tenantId, (int) $admin['id'], 'Manual billing restoration.');
            }
            r2(getUrl('saas/billing'), 's', 'Tenant billing access updated.');
        } catch (Throwable $e) {
            r2(getUrl('saas/billing'), 'e', $e->getMessage());
        }
        break;

    case '2fa':
        $superAdmins = ORM::for_table('tbl_users')->where('user_type', 'SuperAdmin')->order_by_asc('username')->find_many();
        $ui->assign('_title', 'SuperAdmin SMS 2FA');
        $ui->assign('settings', SaasBilling::settings());
        $ui->assign('superadmins', $superAdmins);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/saas/2fa.tpl');
        break;

    case '2fa-save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('saas/2fa'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        try {
            SaasBilling::save2FASettings((int) _post('admin_id'), _post('enabled') === '1', (int) $admin['id']);
            r2(getUrl('saas/2fa'), 's', 'SuperAdmin 2FA settings saved.');
        } catch (Throwable $e) {
            r2(getUrl('saas/2fa'), 'e', $e->getMessage());
        }
        break;

    case 'audit':
        $ui->assign('_title', 'SaaS Audit Logs');
        $ui->assign('logs', ORM::for_table('saas_audit_logs')->order_by_desc('id')->limit(300)->find_many());
        $ui->display('admin/saas/audit.tpl');
        break;

    case 'domains':
        $ui->assign('_title', 'Tenant Domains');
        $ui->assign('domains', ORM::for_table('tenant_domains')->order_by_asc('domain')->find_many());
        $ui->display('admin/saas/domains.tpl');
        break;

    case 'routers':
    case 'payments':
    case 'health':
    case 'admins':
        $ui->assign('_title', 'SaaS ' . ucfirst($action));
        $ui->assign('view_mode', $action);
        $ui->assign('tenants', Tenant::tenantsWithSummary());
        $ui->assign('analytics', SaasBilling::analytics());
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/saas/overview.tpl');
        break;

    case 'tenants':
    default:
        $ui->assign('_title', 'SaaS Tenants / ISPs');
        $ui->assign('view_mode', $action);
        $ui->assign('tenants', Tenant::tenantsWithSummary());
        $ui->assign('base_domain', Tenant::baseDomain($config));
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/saas/tenants.tpl');
        break;
}

function fnp_saas_tenant_input()
{
    return [
        'name' => _post('name'),
        'slug' => _post('slug'),
        'subdomain' => _post('subdomain'),
        'custom_domain' => _post('custom_domain'),
        'status' => _post('status', 'trial'),
        'logo' => _post('logo'),
        'primary_color' => _post('primary_color', '#41a146'),
        'secondary_color' => _post('secondary_color', '#f9c02b'),
        'dark_primary_color' => _post('dark_primary_color', '#4ade80'),
        'dark_secondary_color' => _post('dark_secondary_color', '#facc15'),
        'contact_phone' => _post('contact_phone'),
        'contact_email' => _post('contact_email'),
        'billing_email' => _post('billing_email'),
        'timezone' => _post('timezone', 'Africa/Nairobi'),
        'currency' => _post('currency', 'KES'),
        'subscription_plan' => _post('subscription_plan', 'Starter'),
        'subscription_status' => _post('subscription_status', _post('status', 'trial')),
        'trial_ends_at' => _post('trial_ends_at'),
        'max_routers' => _post('max_routers'),
        'max_clients' => _post('max_clients'),
        'allowed_features' => _post('allowed_features'),
        'billing_exempt' => _post('billing_exempt') === '1',
        'exemption_reason' => _post('exemption_reason'),
        'internal_tenant' => _post('internal_tenant') === '1',
    ];
}

function fnp_saas_admin_input()
{
    return [
        'username' => _post('admin_username'),
        'password' => _post('admin_password'),
        'fullname' => _post('admin_fullname'),
        'email' => _post('admin_email'),
        'phone' => _post('admin_phone'),
        'role' => _post('admin_role', 'Admin'),
    ];
}

function fnp_saas_payment_settings($tenantId)
{
    $tenantId = (int) $tenantId;
    return [
        'active_gateways' => $tenantId > 0 ? Tenant::setting('payment', 'active_gateways', '', $tenantId) : 'mpesastkpush',
        'payment_label' => $tenantId > 0 ? Tenant::setting('payment', 'public_label', '', $tenantId) : 'M-Pesa STK Push',
        'payment_support_message' => $tenantId > 0 ? Tenant::setting('payment', 'support_message', '', $tenantId) : 'Pay securely with M-Pesa.',
        'jovipay_prefix' => $tenantId > 0 ? Tenant::setting('jovipay', 'account_prefix', '', $tenantId) : '',
        'jovipay_callback_url' => $tenantId > 0 ? Tenant::setting('jovipay', 'callback_url', '', $tenantId) : '',
        'payment_enabled' => $tenantId > 0 ? Tenant::setting('payment', 'enabled', 'yes', $tenantId) : 'yes',
    ];
}

function fnp_saas_save_payment_settings($tenantId, $adminId)
{
    $tenantId = (int) $tenantId;
    if ($tenantId <= 0) {
        return;
    }
    $enabled = _post('payment_enabled') === 'no' ? 'no' : 'yes';
    $gateways = implode(',', array_values(array_filter(array_map(function ($gateway) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $gateway);
    }, explode(',', (string) _post('active_gateways', 'mpesastkpush'))))));
    Tenant::saveSetting('payment', 'enabled', $enabled, false, $tenantId);
    Tenant::saveSetting('payment', 'active_gateways', $gateways ?: 'mpesastkpush', false, $tenantId);
    Tenant::saveSetting('payment', 'public_label', substr(trim(strip_tags((string) _post('payment_label', 'M-Pesa STK Push'))), 0, 120), false, $tenantId);
    Tenant::saveSetting('payment', 'support_message', substr(trim(strip_tags((string) _post('payment_support_message', ''))), 0, 255), false, $tenantId);
    Tenant::saveSetting('jovipay', 'account_prefix', substr(preg_replace('/[^A-Za-z0-9_-]/', '', (string) _post('jovipay_prefix')), 0, 60), false, $tenantId);
    Tenant::saveSetting('jovipay', 'callback_url', substr(trim(filter_var((string) _post('jovipay_callback_url'), FILTER_SANITIZE_URL)), 0, 255), false, $tenantId);
    Tenant::audit('tenant.payment_assignment_changed', 'Tenant payment assignment updated by SuperAdmin.', 'tenant', (string) $tenantId, $tenantId, $adminId);
}
