<?php

/**
 * FASTNETPAY SaaS billing, suspension, analytics, and SuperAdmin 2FA helper.
 *
 * This is intentionally additive: it creates its own tables, reads tenant-owned
 * usage from existing PHPNuxBill tables, and only marks tenant/router state
 * instead of deleting or rewriting ISP data.
 */
class SaasBilling
{
    public static function installSchema()
    {
        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_billing_settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            setting VARCHAR(120) NOT NULL,
            value MEDIUMTEXT NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY setting_unique (setting)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_billing_bands (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            service_type VARCHAR(32) NOT NULL,
            name VARCHAR(120) NOT NULL,
            min_users INT UNSIGNED NOT NULL DEFAULT 0,
            max_users INT UNSIGNED NULL,
            base_price DECIMAL(15,2) NOT NULL DEFAULT 0,
            included_users INT UNSIGNED NOT NULL DEFAULT 0,
            extra_user_price DECIMAL(15,2) NOT NULL DEFAULT 0,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX service_idx (service_type),
            INDEX enabled_idx (enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_invoices (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            invoice_number VARCHAR(64) NOT NULL,
            billing_month CHAR(7) NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            due_date DATE NOT NULL,
            grace_until DATE NOT NULL,
            status ENUM('draft','issued','paid','overdue','void') NOT NULL DEFAULT 'issued',
            hotspot_users INT UNSIGNED NOT NULL DEFAULT 0,
            pppoe_users INT UNSIGNED NOT NULL DEFAULT 0,
            routers_count INT UNSIGNED NOT NULL DEFAULT 0,
            subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
            total_due DECIMAL(15,2) NOT NULL DEFAULT 0,
            paid_at DATETIME NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY tenant_month_unique (tenant_id, billing_month),
            UNIQUE KEY invoice_unique (invoice_number),
            INDEX tenant_idx (tenant_id),
            INDEX status_idx (status),
            INDEX due_idx (due_date),
            INDEX grace_idx (grace_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS saas_invoice_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            invoice_id BIGINT UNSIGNED NOT NULL,
            tenant_id INT UNSIGNED NOT NULL,
            item_type VARCHAR(64) NOT NULL,
            description VARCHAR(255) NOT NULL,
            quantity DECIMAL(15,2) NOT NULL DEFAULT 1,
            unit_price DECIMAL(15,2) NOT NULL DEFAULT 0,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0,
            metadata MEDIUMTEXT NULL,
            created_at DATETIME NULL,
            INDEX invoice_idx (invoice_id),
            INDEX tenant_idx (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenant_suspensions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            invoice_id BIGINT UNSIGNED NULL,
            reason VARCHAR(160) NOT NULL,
            status ENUM('active','restored') NOT NULL DEFAULT 'active',
            message TEXT NULL,
            metadata MEDIUMTEXT NULL,
            suspended_at DATETIME NULL,
            restored_at DATETIME NULL,
            created_by INT UNSIGNED NULL,
            INDEX tenant_idx (tenant_id),
            INDEX invoice_idx (invoice_id),
            INDEX status_idx (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS superadmin_2fa_settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            remember_days INT UNSIGNED NOT NULL DEFAULT 0,
            recovery_codes MEDIUMTEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY admin_unique (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS superadmin_2fa_otps (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NOT NULL,
            otp_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified_at DATETIME NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            ip VARCHAR(80) NULL,
            created_at DATETIME NOT NULL,
            INDEX admin_idx (admin_id),
            INDEX expires_idx (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tenant_billing_snapshots (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            billing_month CHAR(7) NOT NULL,
            active_hotspot INT UNSIGNED NOT NULL DEFAULT 0,
            active_pppoe INT UNSIGNED NOT NULL DEFAULT 0,
            routers_count INT UNSIGNED NOT NULL DEFAULT 0,
            amount_due DECIMAL(15,2) NOT NULL DEFAULT 0,
            invoice_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            UNIQUE KEY tenant_month_unique (tenant_id, billing_month),
            INDEX tenant_idx (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::seedSettings();
        self::seedBands();
    }

    public static function seedSettings()
    {
        $defaults = [
            'configuration_fee' => '1000',
            'first_month_payment' => '500',
            'billing_day' => '23',
            'grace_day' => '28',
            'auto_suspend_unpaid' => 'yes',
            'auto_disconnect_vpn' => 'no',
            'invoice_generation_mode' => 'manual',
            'reminder_days_before_due' => '3,1',
            'suspension_message' => 'Your FASTNETPAY SaaS account is suspended because an invoice is overdue. Please settle the invoice or contact support.',
            'allow_tenant_invoice_preview' => 'yes',
            'superadmin_2fa_enabled' => 'no',
        ];
        foreach ($defaults as $key => $value) {
            if (!ORM::for_table('saas_billing_settings')->where('setting', $key)->find_one()) {
                self::saveSetting($key, $value);
            }
        }
    }

    public static function seedBands()
    {
        $bands = [
            ['hotspot', 'Starter', 0, 50, 500, 0, 0, 10],
            ['hotspot', 'Growth', 51, 300, 1000, 0, 0, 20],
            ['hotspot', 'Intermediate', 301, 700, 1500, 0, 0, 30],
            ['hotspot', 'Scale 1', 701, 1300, 2000, 0, 0, 40],
            ['hotspot', 'Scale 2', 1301, 2000, 2500, 0, 0, 50],
            ['hotspot', 'Scale 3', 2001, 3000, 3000, 0, 0, 60],
            ['hotspot', 'Enterprise', 3001, null, 3500, 0, 0, 70],
            ['pppoe', 'PPPoE Starter', 0, 25, 500, 25, 0, 10],
            ['pppoe', 'PPPoE Master', 26, null, 500, 25, 20, 20],
        ];
        foreach ($bands as $band) {
            $exists = ORM::for_table('saas_billing_bands')
                ->where('service_type', $band[0])
                ->where('name', $band[1])
                ->find_one();
            if ($exists) {
                continue;
            }
            $row = ORM::for_table('saas_billing_bands')->create();
            $row->service_type = $band[0];
            $row->name = $band[1];
            $row->min_users = $band[2];
            $row->max_users = $band[3];
            $row->base_price = $band[4];
            $row->included_users = $band[5];
            $row->extra_user_price = $band[6];
            $row->enabled = 1;
            $row->sort_order = $band[7];
            $row->created_at = date('Y-m-d H:i:s');
            $row->updated_at = date('Y-m-d H:i:s');
            $row->save();
        }
    }

    public static function setting($key, $default = '')
    {
        $row = ORM::for_table('saas_billing_settings')->where('setting', $key)->find_one();
        return $row ? (string) $row['value'] : $default;
    }

    public static function saveSetting($key, $value)
    {
        $row = ORM::for_table('saas_billing_settings')->where('setting', $key)->find_one();
        if (!$row) {
            $row = ORM::for_table('saas_billing_settings')->create();
            $row->setting = $key;
        }
        $row->value = (string) $value;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
    }

    public static function settings()
    {
        self::installSchema();
        $keys = ['configuration_fee', 'first_month_payment', 'billing_day', 'grace_day', 'auto_suspend_unpaid', 'auto_disconnect_vpn', 'invoice_generation_mode', 'reminder_days_before_due', 'suspension_message', 'allow_tenant_invoice_preview', 'superadmin_2fa_enabled'];
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = self::setting($key, '');
        }
        $settings['sms_ready'] = self::smsReady();
        return $settings;
    }

    public static function saveSettingsFromPost($adminId)
    {
        $pairs = [
            'configuration_fee' => self::money(_post('configuration_fee', '1000')),
            'first_month_payment' => self::money(_post('first_month_payment', '500')),
            'billing_day' => (string) max(1, min(28, (int) _post('billing_day', 23))),
            'grace_day' => (string) max(1, min(31, (int) _post('grace_day', 28))),
            'auto_suspend_unpaid' => _post('auto_suspend_unpaid') === 'yes' ? 'yes' : 'no',
            'auto_disconnect_vpn' => _post('auto_disconnect_vpn') === 'yes' ? 'yes' : 'no',
            'invoice_generation_mode' => _post('invoice_generation_mode') === 'automatic' ? 'automatic' : 'manual',
            'reminder_days_before_due' => preg_replace('/[^0-9,]/', '', _post('reminder_days_before_due', '3,1')),
            'suspension_message' => trim(strip_tags((string) _post('suspension_message', ''))),
            'allow_tenant_invoice_preview' => _post('allow_tenant_invoice_preview') === 'no' ? 'no' : 'yes',
        ];
        foreach ($pairs as $key => $value) {
            self::saveSetting($key, $value);
        }
        Tenant::audit('saas.billing_settings_changed', 'SaaS billing settings updated.', 'billing', 'settings', Tenant::currentId(), $adminId);
    }

    public static function bands($serviceType = '')
    {
        self::installSchema();
        $query = ORM::for_table('saas_billing_bands')->order_by_asc('service_type')->order_by_asc('sort_order')->order_by_asc('min_users');
        if ($serviceType !== '') {
            $query->where('service_type', $serviceType);
        }
        return $query->find_many();
    }

    public static function saveBandFromPost($adminId)
    {
        $id = (int) _post('band_id');
        $service = strtolower(_post('service_type', 'hotspot'));
        if (!in_array($service, ['hotspot', 'pppoe'], true)) {
            throw new Exception('Invalid billing service type.');
        }
        $row = $id > 0 ? ORM::for_table('saas_billing_bands')->find_one($id) : ORM::for_table('saas_billing_bands')->create();
        if (!$row) {
            throw new Exception('Billing band not found.');
        }
        $row->service_type = $service;
        $row->name = substr(trim(strip_tags((string) _post('name'))), 0, 120);
        if ($row->name === '') {
            throw new Exception('Billing band name is required.');
        }
        $row->min_users = max(0, (int) _post('min_users', 0));
        $max = trim((string) _post('max_users'));
        $row->max_users = $max === '' ? null : max(0, (int) $max);
        $row->base_price = self::money(_post('base_price', '0'));
        $row->included_users = max(0, (int) _post('included_users', 0));
        $row->extra_user_price = self::money(_post('extra_user_price', '0'));
        $row->enabled = _post('enabled') === '0' ? 0 : 1;
        $row->sort_order = max(0, (int) _post('sort_order', 0));
        if (!$id) {
            $row->created_at = date('Y-m-d H:i:s');
        }
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        Tenant::audit('saas.billing_band_changed', 'SaaS billing band saved: ' . $row['name'], 'billing_band', (string) $row->id(), Tenant::currentId(), $adminId);
    }

    public static function usage($tenantId)
    {
        $tenantId = (int) $tenantId;
        $today = date('Y-m-d');
        $hotspot = ORM::for_table('tbl_user_recharges')
            ->where('tenant_id', $tenantId)
            ->where('status', 'on')
            ->where('type', 'Hotspot')
            ->where_gte('expiration', $today)
            ->count();
        $pppoe = ORM::for_table('tbl_user_recharges')
            ->where('tenant_id', $tenantId)
            ->where('status', 'on')
            ->where_in('type', ['PPPOE', 'PPPoE'])
            ->where_gte('expiration', $today)
            ->count();
        $routers = ORM::for_table('tbl_routers')->where('tenant_id', $tenantId)->count();
        return ['hotspot' => (int) $hotspot, 'pppoe' => (int) $pppoe, 'routers' => (int) $routers];
    }

    public static function previewInvoice($tenantId, $billingMonth = null)
    {
        self::installSchema();
        $tenant = ORM::for_table('tenants')->find_one((int) $tenantId);
        if (!$tenant) {
            throw new Exception('Tenant not found.');
        }
        if (!empty($tenant['billing_exempt'])) {
            return [
                'tenant' => $tenant,
                'billing_month' => $billingMonth ?: date('Y-m'),
                'period_start' => ($billingMonth ?: date('Y-m')) . '-01',
                'period_end' => date('Y-m-t', strtotime(($billingMonth ?: date('Y-m')) . '-01')),
                'due_date' => ($billingMonth ?: date('Y-m')) . '-' . str_pad((string) max(1, min(28, (int) self::setting('billing_day', '23'))), 2, '0', STR_PAD_LEFT),
                'grace_until' => ($billingMonth ?: date('Y-m')) . '-' . str_pad((string) max(1, min(31, (int) self::setting('grace_day', '28'))), 2, '0', STR_PAD_LEFT),
                'usage' => self::usage((int) $tenant['id']),
                'hotspot_band' => null,
                'pppoe_band' => null,
                'lines' => [self::line('billing_exempt', 'Billing exempt tenant: ' . ((string) $tenant['exemption_reason'] ?: 'internal/non-billable'), 1, 0, 0)],
                'subtotal' => 0,
                'total_due' => 0,
                'first_invoice' => false,
            ];
        }
        $month = $billingMonth ?: date('Y-m');
        $periodStart = $month . '-01';
        $periodEnd = date('Y-m-t', strtotime($periodStart));
        $billingDay = max(1, min(28, (int) self::setting('billing_day', '23')));
        $graceDay = max($billingDay, min(31, (int) self::setting('grace_day', '28')));
        $dueDate = $month . '-' . str_pad((string) $billingDay, 2, '0', STR_PAD_LEFT);
        $graceDate = $month . '-' . str_pad((string) min($graceDay, (int) date('t', strtotime($periodStart))), 2, '0', STR_PAD_LEFT);
        $usage = self::usage((int) $tenant['id']);
        $lines = [];

        $hotspotBand = self::bandFor('hotspot', $usage['hotspot']);
        if ($hotspotBand) {
            $amount = (float) $hotspotBand['base_price'];
            $lines[] = self::line('hotspot', 'Hotspot SaaS band: ' . $hotspotBand['name'] . ' (' . $usage['hotspot'] . ' active users)', 1, $amount, $amount, $hotspotBand);
        }

        $pppoeBand = self::bandFor('pppoe', $usage['pppoe']);
        if ($pppoeBand) {
            $extra = max(0, $usage['pppoe'] - (int) $pppoeBand['included_users']);
            $amount = (float) $pppoeBand['base_price'] + ($extra * (float) $pppoeBand['extra_user_price']);
            $desc = 'PPPoE SaaS band: ' . $pppoeBand['name'] . ' (' . $usage['pppoe'] . ' active users';
            if ($extra > 0) {
                $desc .= ', ' . $extra . ' extra @ ' . self::money($pppoeBand['extra_user_price']);
            }
            $desc .= ')';
            $lines[] = self::line('pppoe', $desc, 1, $amount, $amount, $pppoeBand);
        }

        $hasInvoice = (int) ORM::for_table('saas_invoices')->where('tenant_id', (int) $tenant['id'])->count() > 0;
        if (!$hasInvoice) {
            $setup = (float) self::setting('configuration_fee', '1000');
            $first = (float) self::setting('first_month_payment', '500');
            if ($setup > 0) {
                $lines[] = self::line('configuration_fee', 'One-time configuration fee', 1, $setup, $setup);
            }
            if ($first > 0) {
                $lines[] = self::line('first_month', 'First month platform payment', 1, $first, $first);
            }
        }

        $total = 0;
        foreach ($lines as $line) {
            $total += (float) $line['amount'];
        }

        return [
            'tenant' => $tenant,
            'billing_month' => $month,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'due_date' => $dueDate,
            'grace_until' => $graceDate,
            'usage' => $usage,
            'hotspot_band' => $hotspotBand,
            'pppoe_band' => $pppoeBand,
            'lines' => $lines,
            'subtotal' => $total,
            'total_due' => $total,
            'first_invoice' => !$hasInvoice,
        ];
    }

    public static function generateInvoice($tenantId, $billingMonth = null, $adminId = 0)
    {
        $preview = self::previewInvoice($tenantId, $billingMonth);
        $existing = ORM::for_table('saas_invoices')
            ->where('tenant_id', (int) $tenantId)
            ->where('billing_month', $preview['billing_month'])
            ->find_one();
        if ($existing) {
            return $existing;
        }

        $invoice = ORM::for_table('saas_invoices')->create();
        $invoice->tenant_id = (int) $tenantId;
        $invoice->invoice_number = self::invoiceNumber((int) $tenantId, $preview['billing_month']);
        $invoice->billing_month = $preview['billing_month'];
        $invoice->period_start = $preview['period_start'];
        $invoice->period_end = $preview['period_end'];
        $invoice->due_date = $preview['due_date'];
        $invoice->grace_until = $preview['grace_until'];
        $invoice->status = 'issued';
        $invoice->hotspot_users = $preview['usage']['hotspot'];
        $invoice->pppoe_users = $preview['usage']['pppoe'];
        $invoice->routers_count = $preview['usage']['routers'];
        $invoice->subtotal = $preview['subtotal'];
        $invoice->total_due = $preview['total_due'];
        $invoice->created_at = date('Y-m-d H:i:s');
        $invoice->updated_at = date('Y-m-d H:i:s');
        $invoice->save();

        foreach ($preview['lines'] as $line) {
            $item = ORM::for_table('saas_invoice_items')->create();
            $item->invoice_id = (int) $invoice->id();
            $item->tenant_id = (int) $tenantId;
            $item->item_type = $line['item_type'];
            $item->description = $line['description'];
            $item->quantity = $line['quantity'];
            $item->unit_price = $line['unit_price'];
            $item->amount = $line['amount'];
            $item->metadata = json_encode($line['metadata'] ?? []);
            $item->created_at = date('Y-m-d H:i:s');
            $item->save();
        }

        self::saveSnapshot((int) $tenantId, $preview, (int) $invoice->id());
        Tenant::audit('saas.invoice_generated', 'Invoice generated: ' . $invoice['invoice_number'], 'invoice', (string) $invoice->id(), (int) $tenantId, $adminId);
        return $invoice;
    }

    public static function markPaid($invoiceId, $adminId = 0)
    {
        $invoice = ORM::for_table('saas_invoices')->find_one((int) $invoiceId);
        if (!$invoice) {
            throw new Exception('Invoice not found.');
        }
        $invoice->status = 'paid';
        $invoice->paid_at = date('Y-m-d H:i:s');
        $invoice->updated_at = date('Y-m-d H:i:s');
        $invoice->save();
        self::restoreTenant((int) $invoice['tenant_id'], $adminId, 'Invoice paid: ' . $invoice['invoice_number']);
        Tenant::audit('saas.invoice_paid', 'Invoice marked paid: ' . $invoice['invoice_number'], 'invoice', (string) $invoice->id(), (int) $invoice['tenant_id'], $adminId);
        return $invoice;
    }

    public static function suspendTenant($tenantId, $invoiceId = null, $adminId = 0, $reason = 'Overdue SaaS invoice')
    {
        $tenant = ORM::for_table('tenants')->find_one((int) $tenantId);
        if (!$tenant) {
            throw new Exception('Tenant not found.');
        }
        if ((string) $tenant['slug'] === 'main') {
            throw new Exception('The mother system tenant cannot be suspended.');
        }
        $tenant->status = 'suspended';
        $tenant->subscription_status = 'suspended';
        $tenant->updated_at = date('Y-m-d H:i:s');
        $tenant->save();

        if (self::setting('auto_disconnect_vpn', 'no') === 'yes') {
            $routers = ORM::for_table('tbl_routers')->where('tenant_id', (int) $tenantId)->find_many();
            foreach ($routers as $router) {
                if (Tenant::hasColumn('tbl_routers', 'vpn_status')) {
                    $router->vpn_status = 'blocked';
                }
                if (Tenant::hasColumn('tbl_routers', 'provisioning_status')) {
                    $router->provisioning_status = 'tenant_suspended';
                }
                $router->save();
            }
        }

        $row = ORM::for_table('tenant_suspensions')->create();
        $row->tenant_id = (int) $tenantId;
        $row->invoice_id = $invoiceId ? (int) $invoiceId : null;
        $row->reason = $reason;
        $row->status = 'active';
        $row->message = self::suspensionMessage((int) $tenantId, $invoiceId);
        $row->metadata = json_encode(['vpn_blocked' => self::setting('auto_disconnect_vpn', 'no')]);
        $row->suspended_at = date('Y-m-d H:i:s');
        $row->created_by = $adminId ?: null;
        $row->save();

        Tenant::audit('tenant.suspended_for_billing', $reason, 'tenant', (string) $tenantId, (int) $tenantId, $adminId);
    }

    public static function restoreTenant($tenantId, $adminId = 0, $reason = 'Tenant restored')
    {
        $tenant = ORM::for_table('tenants')->find_one((int) $tenantId);
        if (!$tenant) {
            throw new Exception('Tenant not found.');
        }
        $tenant->status = 'active';
        $tenant->subscription_status = 'active';
        $tenant->updated_at = date('Y-m-d H:i:s');
        $tenant->save();

        $suspensions = ORM::for_table('tenant_suspensions')->where('tenant_id', (int) $tenantId)->where('status', 'active')->find_many();
        foreach ($suspensions as $row) {
            $row->status = 'restored';
            $row->restored_at = date('Y-m-d H:i:s');
            $row->save();
        }

        $routers = ORM::for_table('tbl_routers')->where('tenant_id', (int) $tenantId)->find_many();
        foreach ($routers as $router) {
            if (Tenant::hasColumn('tbl_routers', 'vpn_status') && $router['vpn_status'] === 'blocked') {
                $router->vpn_status = 'restored';
            }
            if (Tenant::hasColumn('tbl_routers', 'provisioning_status') && $router['provisioning_status'] === 'tenant_suspended') {
                $router->provisioning_status = 'ready';
            }
            $router->save();
        }

        Tenant::audit('tenant.restored_for_billing', $reason, 'tenant', (string) $tenantId, (int) $tenantId, $adminId);
    }

    public static function suspensionMessage($tenantId, $invoiceId = null)
    {
        $invoice = $invoiceId ? ORM::for_table('saas_invoices')->find_one((int) $invoiceId) : self::latestUnpaidInvoice($tenantId);
        $base = self::setting('suspension_message', 'Your FASTNETPAY SaaS account is suspended because an invoice is overdue.');
        if (!$invoice) {
            return $base;
        }
        return $base . ' Amount due: Ksh ' . number_format((float) $invoice['total_due'], 2) . '. Due date: ' . $invoice['due_date'] . '. Grace deadline: ' . $invoice['grace_until'] . '.';
    }

    public static function tenantCanLogin($tenant)
    {
        return $tenant && (string) $tenant['status'] !== 'suspended';
    }

    public static function runCron()
    {
        self::installSchema();
        $month = date('Y-m');
        $today = (int) date('j');
        $billingDay = (int) self::setting('billing_day', '23');
        $graceDay = (int) self::setting('grace_day', '28');
        $generated = 0;
        $suspended = 0;

        foreach (ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('billing_exempt', 0)->find_many() as $tenant) {
            if (self::setting('invoice_generation_mode', 'manual') === 'automatic' && $today >= $billingDay) {
                $invoice = self::generateInvoice((int) $tenant['id'], $month);
                if ($invoice) {
                    $generated++;
                }
            }
            if (self::setting('auto_suspend_unpaid', 'yes') === 'yes' && $today > $graceDay) {
                $invoice = self::latestUnpaidInvoice((int) $tenant['id']);
                if ($invoice && (string) $tenant['status'] !== 'suspended') {
                    self::suspendTenant((int) $tenant['id'], (int) $invoice['id'], 0, 'Automatic SaaS billing suspension.');
                    $suspended++;
                }
            }
        }

        return ['generated' => $generated, 'suspended' => $suspended];
    }

    public static function analytics()
    {
        self::installSchema();
        $month = date('Y-m');
        $tenantsTotal = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->count();
        $activeTenants = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('status', 'active')->count();
        $suspendedTenants = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('status', 'suspended')->count();
        $trialTenants = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('status', 'trial')->count();
        $newThisMonth = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where_gte('created_at', $month . '-01 00:00:00')->count();
        $billingExempt = (int) ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('billing_exempt', 1)->count();

        $routersTotal = (int) ORM::for_table('tbl_routers')->count();
        $onlineRouters = (int) ORM::for_table('tbl_routers')->where('status', 'Online')->count();
        $offlineRouters = max(0, $routersTotal - $onlineRouters);
        $vpnModes = [];
        foreach (['local', 'wireguard', 'sstp'] as $mode) {
            $vpnModes[$mode] = Tenant::hasColumn('tbl_routers', 'vpn_mode') ? (int) ORM::for_table('tbl_routers')->where('vpn_mode', $mode)->count() : 0;
        }

        $hotspot = (int) ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('type', 'Hotspot')->where_gte('expiration', date('Y-m-d'))->count();
        $pppoe = (int) ORM::for_table('tbl_user_recharges')->where('status', 'on')->where_in('type', ['PPPOE', 'PPPoE'])->where_gte('expiration', date('Y-m-d'))->count();
        $invoiceMonth = ORM::for_table('saas_invoices')->where('billing_month', $month);
        $invoiced = (float) $invoiceMonth->sum('total_due');
        $paid = (float) ORM::for_table('saas_invoices')->where('billing_month', $month)->where('status', 'paid')->sum('total_due');
        $unpaid = max(0, $invoiced - $paid);
        $overdue = (float) ORM::for_table('saas_invoices')->where_not_equal('status', 'paid')->where_lt('grace_until', date('Y-m-d'))->sum('total_due');
        $expected = 0;
        foreach (ORM::for_table('tenants')->where_not_equal('slug', 'main')->where('billing_exempt', 0)->find_many() as $tenant) {
            $expected += self::previewInvoice((int) $tenant['id'], $month)['total_due'];
        }

        return [
            'tenants' => ['total' => $tenantsTotal, 'active' => $activeTenants, 'suspended' => $suspendedTenants, 'trial' => $trialTenants, 'new_this_month' => $newThisMonth, 'billing_exempt' => $billingExempt],
            'routers' => ['total' => $routersTotal, 'online' => $onlineRouters, 'offline' => $offlineRouters, 'vpn_modes' => $vpnModes],
            'clients' => ['hotspot' => $hotspot, 'pppoe' => $pppoe, 'total' => $hotspot + $pppoe],
            'financial' => ['expected' => $expected, 'invoiced' => $invoiced, 'paid' => $paid, 'unpaid' => $unpaid, 'overdue' => $overdue],
            'billing_health' => [
                'due_today' => (int) ORM::for_table('saas_invoices')->where_not_equal('status', 'paid')->where('due_date', date('Y-m-d'))->count(),
                'in_grace' => (int) ORM::for_table('saas_invoices')->where_not_equal('status', 'paid')->where_gte('grace_until', date('Y-m-d'))->where_lte('due_date', date('Y-m-d'))->count(),
                'suspended' => $suspendedTenants,
            ],
            'recent_invoices' => ORM::for_table('saas_invoices')->order_by_desc('id')->limit(8)->find_many(),
            'top_tenants' => self::topTenantsByUsers(),
        ];
    }

    public static function smsReady()
    {
        global $config;
        if (function_exists('talksasa_config') && (talksasa_config('talksasa_api_token', '') !== '') && talksasa_config('talksasa_sender_id', '') !== '') {
            return true;
        }
        return !empty($config['sms_url']) && (strpos((string) $config['sms_url'], 'http') === 0 || (string) $config['sms_url'] === 'talksasa');
    }

    public static function superAdmin2FAEnabled($adminId)
    {
        $row = ORM::for_table('superadmin_2fa_settings')->where('admin_id', (int) $adminId)->find_one();
        return $row ? (int) $row['enabled'] === 1 : self::setting('superadmin_2fa_enabled', 'no') === 'yes';
    }

    public static function requiresSuperAdmin2FA($admin)
    {
        if (!$admin || (string) $admin['user_type'] !== 'SuperAdmin') {
            return false;
        }
        return self::superAdmin2FAEnabled((int) $admin['id']) && self::smsReady();
    }

    public static function save2FASettings($adminId, $enabled, $actorId)
    {
        if ($enabled && !self::smsReady()) {
            throw new Exception('Configure SMS gateway before enabling SuperAdmin 2FA.');
        }
        $row = ORM::for_table('superadmin_2fa_settings')->where('admin_id', (int) $adminId)->find_one();
        if (!$row) {
            $row = ORM::for_table('superadmin_2fa_settings')->create();
            $row->admin_id = (int) $adminId;
            $row->created_at = date('Y-m-d H:i:s');
        }
        $row->enabled = $enabled ? 1 : 0;
        $row->remember_days = 0;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        self::saveSetting('superadmin_2fa_enabled', $enabled ? 'yes' : 'no');
        Tenant::audit($enabled ? 'superadmin.2fa_enabled' : 'superadmin.2fa_disabled', 'SuperAdmin 2FA setting changed.', 'user', (string) $adminId, Tenant::currentId(), $actorId);
    }

    public static function issueSuperAdminOtp($admin)
    {
        if (!self::smsReady()) {
            throw new Exception('SMS gateway is not configured, so SuperAdmin 2FA cannot send OTP.');
        }
        $phone = trim((string) ($admin['phone'] ?? ''));
        if ($phone === '') {
            throw new Exception('SuperAdmin has no phone number for 2FA OTP.');
        }
        $otp = (string) random_int(100000, 999999);
        $row = ORM::for_table('superadmin_2fa_otps')->create();
        $row->admin_id = (int) $admin['id'];
        $row->otp_hash = hash('sha256', $otp);
        $row->expires_at = date('Y-m-d H:i:s', time() + 600);
        $row->attempts = 0;
        $row->ip = self::clientIp();
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
        Message::sendSMS($phone, 'FASTNETPAY SuperAdmin OTP: ' . $otp . '. It expires in 10 minutes.');
        Tenant::audit('superadmin.2fa_otp_sent', 'SuperAdmin 2FA OTP sent.', 'user', (string) $admin['id'], Tenant::currentId(), (int) $admin['id']);
        return true;
    }

    public static function verifySuperAdminOtp($adminId, $otp)
    {
        $otp = preg_replace('/[^0-9]/', '', (string) $otp);
        $row = ORM::for_table('superadmin_2fa_otps')
            ->where('admin_id', (int) $adminId)
            ->where_null('verified_at')
            ->where_gt('expires_at', date('Y-m-d H:i:s'))
            ->order_by_desc('id')
            ->find_one();
        if (!$row) {
            Tenant::audit('superadmin.2fa_failed', 'No active SuperAdmin OTP found.', 'user', (string) $adminId, Tenant::currentId(), (int) $adminId);
            return false;
        }
        $row->attempts = (int) $row['attempts'] + 1;
        if ((int) $row['attempts'] > 5) {
            $row->save();
            return false;
        }
        if (!hash_equals((string) $row['otp_hash'], hash('sha256', $otp))) {
            $row->save();
            Tenant::audit('superadmin.2fa_failed', 'Invalid SuperAdmin OTP.', 'user', (string) $adminId, Tenant::currentId(), (int) $adminId);
            return false;
        }
        $row->verified_at = date('Y-m-d H:i:s');
        $row->save();
        Tenant::audit('superadmin.2fa_verified', 'SuperAdmin 2FA verified.', 'user', (string) $adminId, Tenant::currentId(), (int) $adminId);
        return true;
    }

    public static function latestUnpaidInvoice($tenantId)
    {
        return ORM::for_table('saas_invoices')
            ->where('tenant_id', (int) $tenantId)
            ->where_not_equal('status', 'paid')
            ->order_by_desc('id')
            ->find_one();
    }

    public static function invoices($limit = 100)
    {
        return ORM::for_table('saas_invoices')->order_by_desc('id')->limit((int) $limit)->find_many();
    }

    public static function invoiceItems($invoiceId)
    {
        return ORM::for_table('saas_invoice_items')->where('invoice_id', (int) $invoiceId)->order_by_asc('id')->find_many();
    }

    private static function bandFor($serviceType, $users)
    {
        $query = ORM::for_table('saas_billing_bands')
            ->where('service_type', $serviceType)
            ->where('enabled', 1)
            ->where_lte('min_users', (int) $users)
            ->order_by_desc('min_users');
        foreach ($query->find_many() as $band) {
            if ($band['max_users'] === null || $band['max_users'] === '' || (int) $users <= (int) $band['max_users']) {
                return $band;
            }
        }
        return null;
    }

    private static function line($type, $description, $quantity, $unit, $amount, $metadata = [])
    {
        return [
            'item_type' => $type,
            'description' => $description,
            'quantity' => (float) $quantity,
            'unit_price' => (float) $unit,
            'amount' => (float) $amount,
            'metadata' => is_object($metadata) && method_exists($metadata, 'as_array') ? $metadata->as_array() : $metadata,
        ];
    }

    private static function invoiceNumber($tenantId, $month)
    {
        return 'FNP-SaaS-' . str_pad((string) $tenantId, 4, '0', STR_PAD_LEFT) . '-' . str_replace('-', '', $month);
    }

    private static function saveSnapshot($tenantId, $preview, $invoiceId)
    {
        $row = ORM::for_table('tenant_billing_snapshots')->where('tenant_id', (int) $tenantId)->where('billing_month', $preview['billing_month'])->find_one();
        if (!$row) {
            $row = ORM::for_table('tenant_billing_snapshots')->create();
            $row->tenant_id = (int) $tenantId;
            $row->billing_month = $preview['billing_month'];
        }
        $row->active_hotspot = $preview['usage']['hotspot'];
        $row->active_pppoe = $preview['usage']['pppoe'];
        $row->routers_count = $preview['usage']['routers'];
        $row->amount_due = $preview['total_due'];
        $row->invoice_id = (int) $invoiceId;
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
    }

    private static function topTenantsByUsers()
    {
        $rows = [];
        foreach (ORM::for_table('tenants')->where_not_equal('slug', 'main')->order_by_asc('name')->find_many() as $tenant) {
            $usage = self::usage((int) $tenant['id']);
            $rows[] = ['tenant' => $tenant, 'users' => $usage['hotspot'] + $usage['pppoe'], 'hotspot' => $usage['hotspot'], 'pppoe' => $usage['pppoe']];
        }
        usort($rows, function ($a, $b) {
            return $b['users'] <=> $a['users'];
        });
        return array_slice($rows, 0, 8);
    }

    private static function money($value)
    {
        return number_format((float) preg_replace('/[^0-9.]/', '', (string) $value), 2, '.', '');
    }

    private static function clientIp()
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'CLI'), 0, 80);
    }
}
