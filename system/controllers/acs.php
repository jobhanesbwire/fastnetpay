<?php

_admin();
$admin = Admin::_info();
if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

$ui->assign('_title', 'ACS Management');
$ui->assign('_system_menu', 'acs');
$ui->assign('_admin', $admin);

fnp_acs_ensure_schema();
$action = $routes['1'] ?? '';

switch ($action) {
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('acs'), 'e', Lang::T('Invalid or expired form token'));
        }
        $id = (int) _post('id');
        $row = $id > 0 ? Tenant::scopeIfTenant(ORM::for_table('acs_devices'))->find_one($id) : ORM::for_table('acs_devices')->create();
        if (!$row) {
            r2(getUrl('acs'), 'e', Lang::T('ACS device not found'));
        }
        Tenant::stamp($row, null, 'acs_devices');
        $row->customer_id = (int) _post('customer_id');
        $row->device_name = Text::sanitize(_post('device_name'));
        $row->mac_address = strtoupper(Text::alphanumeric(_post('mac_address'), ':-'));
        $row->serial_number = Text::alphanumeric(_post('serial_number'), '-_');
        $row->status = in_array(_post('status'), ['online', 'offline', 'pending', 'disabled'], true) ? _post('status') : 'pending';
        $row->notes = Text::sanitize(_post('notes'));
        $row->updated_at = date('Y-m-d H:i:s');
        if (!$id) {
            $row->created_by = (int) $admin['id'];
            $row->created_at = date('Y-m-d H:i:s');
            $row->registered_at = date('Y-m-d H:i:s');
        }
        $row->save();
        _log('[' . $admin['username'] . ']: saved ACS device ' . $row->device_name, $admin['user_type'], $admin['id']);
        r2(getUrl('acs'), 's', Lang::T('ACS device saved successfully'));

    case 'delete':
        if (!Csrf::check(_req('csrf_token'))) {
            r2(getUrl('acs'), 'e', Lang::T('Invalid or expired form token'));
        }
        $row = Tenant::scopeIfTenant(ORM::for_table('acs_devices'))->find_one((int) ($routes['2'] ?? 0));
        if ($row) {
            $row->delete();
            _log('[' . $admin['username'] . ']: deleted ACS device #' . (int) ($routes['2'] ?? 0), $admin['user_type'], $admin['id']);
        }
        r2(getUrl('acs'), 's', Lang::T('ACS device deleted'));

    default:
        $q = trim((string) _req('name'));
        $query = ORM::for_table('acs_devices')->order_by_desc('id');
        $query = Tenant::scopeIfTenant($query);
        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where_raw('(device_name LIKE ? OR mac_address LIKE ? OR serial_number LIKE ? OR notes LIKE ?)', [$like, $like, $like, $like]);
        }
        $devices = Paginator::findMany($query, ['name' => $q], 30, 'name=' . urlencode($q));
        $customerIds = [];
        foreach ($devices as $device) {
            if ((int) $device['customer_id'] > 0) {
                $customerIds[] = (int) $device['customer_id'];
            }
        }
        $customers = [];
        if ($customerIds) {
            $customerQuery = ORM::for_table('tbl_customers')->where_id_in(array_unique($customerIds));
            foreach (Tenant::scopeIfTenant($customerQuery)->find_array() as $customer) {
                $customers[(int) $customer['id']] = $customer;
            }
        }
        $allCustomers = Tenant::scopeIfTenant(ORM::for_table('tbl_customers')->select('id')->select('username')->select('fullname')->order_by_asc('username'))->limit(300)->find_array();
        $ui->assign('devices', $devices);
        $ui->assign('customers', $customers);
        $ui->assign('all_customers', $allCustomers);
        $ui->assign('search', $q);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/acs/list.tpl');
        break;
}

function fnp_acs_ensure_schema()
{
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS acs_devices (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NULL,
        customer_id INT UNSIGNED NOT NULL DEFAULT 0,
        device_name VARCHAR(160) NOT NULL DEFAULT '',
        mac_address VARCHAR(32) NOT NULL DEFAULT '',
        serial_number VARCHAR(120) NOT NULL DEFAULT '',
        status VARCHAR(32) NOT NULL DEFAULT 'pending',
        notes TEXT NULL,
        registered_at DATETIME NULL,
        last_seen_at DATETIME NULL,
        created_by INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        INDEX idx_tenant_status (tenant_id, status),
        INDEX idx_customer (customer_id),
        INDEX idx_mac (mac_address),
        INDEX idx_serial (serial_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
