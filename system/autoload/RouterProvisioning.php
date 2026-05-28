<?php

use PEAR2\Net\RouterOS;
use PEAR2\Net\RouterOS\Client;
use PEAR2\Net\RouterOS\Request;
use PEAR2\Net\Transmitter\NetworkStream;

/**
 * FASTNETPAY Router Provisioning Wizard helpers.
 *
 * The wizard is intentionally additive: it previews RouterOS commands, creates
 * a backup/export first, then applies grouped scripts through the existing
 * PEAR2 RouterOS API client.
 */
class RouterProvisioning
{
    const API_USERNAME = 'fastnet-api-usr';
    const API_GROUP = 'fastnet-api';

    public static function installSchema()
    {
        ORM::raw_execute("CREATE TABLE IF NOT EXISTS router_provisioning_runs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            router_id INT UNSIGNED NULL,
            deployment_profile VARCHAR(64) NOT NULL DEFAULT '',
            routeros_version VARCHAR(64) NOT NULL DEFAULT '',
            status VARCHAR(32) NOT NULL DEFAULT 'draft',
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            created_by INT UNSIGNED NULL,
            backup_file VARCHAR(190) NULL,
            notes TEXT NULL,
            INDEX router_id_idx (router_id),
            INDEX status_idx (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS router_provisioning_steps (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            run_id INT UNSIGNED NOT NULL,
            step_name VARCHAR(120) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            output MEDIUMTEXT NULL,
            error_message MEDIUMTEXT NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            INDEX run_id_idx (run_id),
            INDEX status_idx (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS router_provisioning_templates (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            type VARCHAR(64) NOT NULL,
            description TEXT NULL,
            config_json MEDIUMTEXT NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            INDEX type_idx (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS router_port_mappings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            router_id INT UNSIGNED NOT NULL,
            wan_interface VARCHAR(80) NULL,
            lan_interface VARCHAR(80) NULL,
            hotspot_interface VARCHAR(80) NULL,
            pppoe_interface VARCHAR(80) NULL,
            management_interface VARCHAR(80) NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY router_id_unique (router_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS router_portal_tokens (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            router_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY router_id_unique (router_id),
            INDEX token_hash_idx (token_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        ORM::raw_execute("CREATE TABLE IF NOT EXISTS hotspot_api_attempts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            attempt_key VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX attempt_key_idx (attempt_key),
            INDEX created_at_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::seedTemplates();
        self::seedSamplePackages();
    }

    public static function seedTemplates()
    {
        $count = (int) ORM::for_table('router_provisioning_templates')->count();
        if ($count > 0) {
            return;
        }

        $templates = [
            [
                'Small Hotspot',
                'hotspot',
                'Single router hotspot for a cafe, small shop, or compact estate WiFi deployment.',
                [
                    'deployment_profile' => 'hotspot',
                    'lan_gateway' => '192.168.90.1/24',
                    'hotspot_pool' => '192.168.90.50-192.168.90.250',
                    'dhcp_range' => '192.168.90.50-192.168.90.250',
                    'dns_name' => 'login.fastnetpay.test',
                    'security_level' => 'recommended',
                ],
            ],
            [
                'Hostel WiFi',
                'hotspot',
                'Hotspot setup with shared-user controls and captive portal payments.',
                [
                    'deployment_profile' => 'hotspot',
                    'lan_gateway' => '10.10.0.1/22',
                    'hotspot_pool' => '10.10.1.10-10.10.3.250',
                    'dhcp_range' => '10.10.1.10-10.10.3.250',
                    'dns_name' => 'wifi.fastnetpay.test',
                    'security_level' => 'recommended',
                ],
            ],
            [
                'School WiFi',
                'hotspot',
                'Hotspot profile for controlled school WiFi with safer DNS and management separation.',
                [
                    'deployment_profile' => 'hotspot',
                    'lan_gateway' => '10.20.0.1/22',
                    'hotspot_pool' => '10.20.1.10-10.20.3.250',
                    'dhcp_range' => '10.20.1.10-10.20.3.250',
                    'dns_name' => 'school.fastnetpay.test',
                    'security_level' => 'strict',
                ],
            ],
            [
                'Market/Public WiFi',
                'hotspot',
                'High-churn public hotspot with stronger abuse controls and clear walled garden.',
                [
                    'deployment_profile' => 'hotspot',
                    'lan_gateway' => '172.16.10.1/23',
                    'hotspot_pool' => '172.16.10.50-172.16.11.250',
                    'dhcp_range' => '172.16.10.50-172.16.11.250',
                    'dns_name' => 'pay.fastnetpay.test',
                    'security_level' => 'strict',
                ],
            ],
            [
                'PPPoE ISP',
                'pppoe',
                'PPPoE access server profile for fiber/wireless last mile subscribers.',
                [
                    'deployment_profile' => 'pppoe',
                    'lan_gateway' => '100.64.0.1/24',
                    'pppoe_pool' => '100.64.1.10-100.64.1.250',
                    'security_level' => 'recommended',
                ],
            ],
            [
                'Mixed Hotspot + PPPoE ISP',
                'mixed',
                'Combined hotspot and PPPoE setup for operators serving prepaid WiFi and fixed clients.',
                [
                    'deployment_profile' => 'mixed',
                    'lan_gateway' => '192.168.90.1/24',
                    'hotspot_pool' => '192.168.90.50-192.168.90.250',
                    'pppoe_pool' => '100.64.10.10-100.64.10.250',
                    'dhcp_range' => '192.168.90.50-192.168.90.250',
                    'dns_name' => 'portal.fastnetpay.test',
                    'security_level' => 'recommended',
                ],
            ],
        ];

        foreach ($templates as $template) {
            $row = ORM::for_table('router_provisioning_templates')->create();
            $row->name = $template[0];
            $row->type = $template[1];
            $row->description = $template[2];
            $row->config_json = json_encode($template[3]);
            $row->is_default = 1;
            $row->created_at = date('Y-m-d H:i:s');
            $row->updated_at = date('Y-m-d H:i:s');
            $row->save();
        }
    }

    public static function seedSamplePackages()
    {
        $hasAccessPlan = (int) ORM::for_table('tbl_plans')
            ->where_in('type', ['Hotspot', 'PPPOE'])
            ->count();
        if ($hasAccessPlan > 0) {
            return;
        }

        $hotspotBw = self::ensureBandwidth('FNP Test 2M/2M', 2, 'Mbps', 2, 'Mbps');
        $pppoeBw = self::ensureBandwidth('FNP Test 3M/3M', 3, 'Mbps', 3, 'Mbps');

        self::ensurePlan([
            'name_plan' => 'Test Hotspot 1 Hour',
            'id_bw' => $hotspotBw,
            'price' => '10',
            'type' => 'Hotspot',
            'validity' => 1,
            'validity_unit' => 'Hrs',
            'shared_users' => 1,
            'device' => 'MikrotikHotspot',
        ]);

        self::ensurePlan([
            'name_plan' => 'Test PPPoE 1 Day',
            'id_bw' => $pppoeBw,
            'price' => '20',
            'type' => 'PPPOE',
            'validity' => 1,
            'validity_unit' => 'Days',
            'shared_users' => 1,
            'device' => 'MikrotikPppoe',
        ]);
    }

    private static function ensureBandwidth($name, $down, $downUnit, $up, $upUnit)
    {
        $row = ORM::for_table('tbl_bandwidth')->where('name_bw', $name)->find_one();
        if (!$row) {
            $row = ORM::for_table('tbl_bandwidth')->create();
            $row->name_bw = $name;
            $row->burst = '';
        }
        $row->rate_down = (int) $down;
        $row->rate_down_unit = $downUnit;
        $row->rate_up = (int) $up;
        $row->rate_up_unit = $upUnit;
        $row->save();

        return (int) $row->id();
    }

    private static function ensurePlan($data)
    {
        $row = ORM::for_table('tbl_plans')->where('name_plan', $data['name_plan'])->find_one();
        if ($row) {
            return;
        }

        $row = ORM::for_table('tbl_plans')->create();
        $row->name_plan = $data['name_plan'];
        $row->id_bw = (int) $data['id_bw'];
        $row->price = $data['price'];
        $row->price_old = '';
        $row->type = $data['type'];
        $row->typebp = 'Unlimited';
        $row->limit_type = 'Time_Limit';
        $row->time_limit = 0;
        $row->time_unit = 'Hrs';
        $row->data_limit = 0;
        $row->data_unit = 'MB';
        $row->validity = (int) $data['validity'];
        $row->validity_unit = $data['validity_unit'];
        $row->shared_users = (int) $data['shared_users'];
        $row->routers = '';
        $row->is_radius = 0;
        $row->pool = '';
        $row->plan_expired = 0;
        $row->expired_date = 20;
        $row->enabled = 1;
        $row->prepaid = 'yes';
        $row->plan_type = 'Personal';
        $row->device = $data['device'];
        $row->on_login = '';
        $row->on_logout = '';
        $row->save();
    }

    public static function templates()
    {
        self::installSchema();
        return ORM::for_table('router_provisioning_templates')->order_by_asc('id')->find_many();
    }

    public static function defaultSettings()
    {
        return [
            'router_name' => '',
            'host' => '',
            'api_port' => '8728',
            'api_ssl_port' => '8729',
            'prefer_ssl' => 'no',
            'username' => 'admin',
            'password' => '',
            'api_username' => self::API_USERNAME,
            'api_password' => '',
            'ensure_api_user' => 'yes',
            'deployment_profile' => 'mixed',
            'deployment_template' => 'Mixed Hotspot + PPPoE ISP',
            'wan_interface' => '',
            'lan_interface' => 'fastnetpay-bridge',
            'hotspot_interface' => 'fastnetpay-bridge',
            'pppoe_interface' => 'fastnetpay-bridge',
            'management_interface' => 'ether4',
            'lan_gateway' => '192.168.90.1/24',
            'hotspot_pool' => '192.168.90.50-192.168.90.250',
            'pppoe_pool' => '100.64.10.10-100.64.10.250',
            'dhcp_range' => '192.168.90.50-192.168.90.250',
            'dns_servers' => '1.1.1.1,8.8.8.8',
            'dns_name' => 'portal.fastnetpay.test',
            'dhcp_lease_time' => '12h',
            'portal_mode' => 'static',
            'payment_gateway' => 'mpesastkpush',
            'account_reference_prefix' => self::cfg('mpesastkpush_account_prefix', 'FASTNETPAY'),
            'callback_url' => self::cfg('mpesastkpush_callback_url', APP_URL . '/?_route=callback/mpesastkpush'),
            'support_phone' => self::cfg('mpesastkpush_support_phone', ''),
            'support_whatsapp' => '',
            'shortcode_label' => self::cfg('mpesastkpush_shortcode', ''),
            'fastnetpay_server_ip' => self::defaultServerAddress(),
            'radius_secret' => self::randomSecret(),
            'security_level' => 'recommended',
            'custom_walled_garden' => '',
            'create_sample_user' => 'no',
            'allow_backup_override' => 'no',
        ];
    }

    public static function settingsFromRequest($router = null)
    {
        self::installSchema();
        $defaults = self::defaultSettings();
        if ($router) {
            $hostParts = self::parseHostPort($router['ip_address'], $defaults['api_port']);
            $defaults['router_name'] = $router['name'];
            $defaults['host'] = $hostParts['host'];
            $defaults['api_port'] = (string) $hostParts['port'];
            if ($router['username'] === self::API_USERNAME) {
                $defaults['api_username'] = self::API_USERNAME;
                $defaults['api_password'] = $router['password'];
                $defaults['username'] = 'admin';
                $defaults['password'] = '';
            } else {
                $defaults['username'] = $router['username'] ?: 'admin';
                $defaults['password'] = $router['password'];
            }
            $mapping = self::portMapping((int) $router['id']);
            if ($mapping) {
                foreach (['wan_interface', 'lan_interface', 'hotspot_interface', 'pppoe_interface', 'management_interface'] as $field) {
                    if (!empty($mapping[$field])) {
                        $defaults[$field] = $mapping[$field];
                    }
                }
            }
        }

        $clean = [];
        foreach ($defaults as $key => $value) {
            $clean[$key] = self::cleanText($_POST[$key] ?? $value, 255);
        }

        $clean['api_port'] = (string) self::port($clean['api_port'], 8728);
        $clean['api_ssl_port'] = (string) self::port($clean['api_ssl_port'], 8729);
        $clean['prefer_ssl'] = $clean['prefer_ssl'] === 'yes' ? 'yes' : 'no';
        $clean['api_username'] = self::API_USERNAME;
        $clean['ensure_api_user'] = $clean['ensure_api_user'] === 'no' ? 'no' : 'yes';
        foreach (['wan_interface', 'lan_interface', 'hotspot_interface', 'pppoe_interface', 'management_interface'] as $field) {
            $clean[$field] = self::normalizeInterfaceName($clean[$field], $field === 'management_interface' ? 'ether4' : '');
        }
        $clean = self::protectManagementDefaults($clean);
        $clean['deployment_profile'] = self::choice($clean['deployment_profile'], ['hotspot', 'pppoe', 'mixed', 'base', 'security'], 'mixed');
        $clean['portal_mode'] = self::choice($clean['portal_mode'], ['fastnetpay', 'static'], 'fastnetpay');
        $clean['security_level'] = self::choice($clean['security_level'], ['basic', 'recommended', 'strict'], 'recommended');
        $clean['create_sample_user'] = $clean['create_sample_user'] === 'yes' ? 'yes' : 'no';
        $clean['allow_backup_override'] = $clean['allow_backup_override'] === 'yes' ? 'yes' : 'no';
        $clean['plan_ids'] = self::intArray($_POST['plan_ids'] ?? []);
        $clean['custom_walled_garden'] = self::cleanTextarea($_POST['custom_walled_garden'] ?? $clean['custom_walled_garden']);

        return $clean;
    }

    private static function protectManagementDefaults($settings)
    {
        $host = self::cidrIp($settings['host'] ?? '');
        $gateway = self::cidrIp($settings['lan_gateway'] ?? '');
        $management = self::rosName($settings['management_interface'] ?? 'ether4');
        $hotspot = self::rosName($settings['hotspot_interface'] ?: ($settings['lan_interface'] ?? 'fastnetpay-bridge'));

        if ($host !== '' && $gateway === $host && $management !== '' && $management !== $hotspot) {
            $settings['lan_gateway'] = '192.168.90.1/24';
            if (trim((string) ($settings['hotspot_pool'] ?? '')) === '' || strpos((string) $settings['hotspot_pool'], '192.168.88.') !== false) {
                $settings['hotspot_pool'] = '192.168.90.50-192.168.90.250';
            }
            if (trim((string) ($settings['dhcp_range'] ?? '')) === '' || strpos((string) $settings['dhcp_range'], '192.168.88.') !== false) {
                $settings['dhcp_range'] = '192.168.90.50-192.168.90.250';
            }
        }

        return $settings;
    }

    public static function router($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }
        return ORM::for_table('tbl_routers')->find_one($id);
    }

    public static function plans()
    {
        self::seedSamplePackages();

        return ORM::for_table('tbl_plans')
            ->select('tbl_plans.id', 'id')
            ->select('tbl_plans.name_plan', 'name_plan')
            ->select('tbl_plans.type', 'type')
            ->select('tbl_plans.price', 'price')
            ->select('tbl_plans.shared_users', 'shared_users')
            ->select('tbl_plans.validity', 'validity')
            ->select('tbl_plans.validity_unit', 'validity_unit')
            ->select('tbl_plans.pool', 'pool')
            ->select('tbl_plans.routers', 'routers')
            ->select('tbl_plans.enabled', 'enabled')
            ->select('tbl_plans.is_radius', 'is_radius')
            ->select('tbl_bandwidth.name_bw', 'name_bw')
            ->select('tbl_bandwidth.rate_up', 'rate_up')
            ->select('tbl_bandwidth.rate_up_unit', 'rate_up_unit')
            ->select('tbl_bandwidth.rate_down', 'rate_down')
            ->select('tbl_bandwidth.rate_down_unit', 'rate_down_unit')
            ->select('tbl_bandwidth.burst', 'burst')
            ->left_outer_join('tbl_bandwidth', ['tbl_plans.id_bw', '=', 'tbl_bandwidth.id'])
            ->where_in('tbl_plans.type', ['Hotspot', 'PPPOE'])
            ->order_by_asc('tbl_plans.type')
            ->order_by_asc('tbl_plans.name_plan')
            ->find_array();
    }

    public static function mpesaReadiness()
    {
        $missing = [];
        $required = [
            'mpesastkpush_shortcode' => 'shortcode/paybill/till',
            'mpesastkpush_consumer_key' => 'consumer key',
            'mpesastkpush_consumer_secret' => 'consumer secret',
            'mpesastkpush_passkey' => 'passkey',
        ];
        foreach ($required as $key => $label) {
            if (trim(self::cfg($key, '')) === '') {
                $missing[] = $label;
            }
        }

        $enabled = self::cfg('mpesastkpush_enabled', 'no') === 'yes';
        if (!$enabled) {
            $missing[] = 'gateway enabled';
        }

        return [
            'ready' => $enabled && empty($missing),
            'enabled' => $enabled,
            'missing' => $missing,
            'shortcode' => self::cfg('mpesastkpush_shortcode', ''),
            'callback_url' => self::cfg('mpesastkpush_callback_url', APP_URL . '/?_route=callback/mpesastkpush'),
            'account_prefix' => self::cfg('mpesastkpush_account_prefix', 'FASTNETPAY'),
            'environment' => self::cfg('mpesastkpush_environment', 'sandbox'),
        ];
    }

    public static function detect($router, $settings)
    {
        $api = self::prepareApiUser($router, $settings, 0, $router ? true : false);
        $client = $api['client'];
        $resource = self::firstRow($client, '/system/resource/print');
        $identity = self::firstRow($client, '/system/identity/print');
        $interfaces = self::rows($client, '/interface/print');
        $hotspots = self::safeRows($client, '/ip/hotspot/print');
        $pppoeServers = self::safeRows($client, '/interface/pppoe-server/server/print');
        $dhcpServers = self::safeRows($client, '/ip/dhcp-server/print');
        $firewallRules = self::safeRows($client, '/ip/firewall/filter/print');
        $natRules = self::safeRows($client, '/ip/firewall/nat/print');
        $wireless = self::safeRows($client, '/interface/wireless/print');
        $wifi = self::safeRows($client, '/interface/wifi/print');

        $version = self::prop($resource, 'version');
        $warnings = [];
        if (trim((string) $settings['password']) === '' && (!$router || trim((string) $router['password']) === '')) {
            $warnings[] = 'Bootstrap/admin password is empty. This is allowed only for reset-lab testing. FASTNETPAY will use fastnet-api-usr after it is created.';
        }
        if (count($hotspots) > 0) {
            $warnings[] = 'Existing hotspot configuration detected. The wizard will add FASTNETPAY rules and profiles without deleting existing ones.';
        }
        if (count($pppoeServers) > 0) {
            $warnings[] = 'Existing PPPoE server detected. Review the preview carefully before applying.';
        }
        if (count($firewallRules) > 0) {
            $warnings[] = 'Existing firewall rules detected. FASTNETPAY rules are added with comments and do not wipe current rules.';
        }
        if (count($wireless) === 0 && count($wifi) === 0) {
            $warnings[] = 'No wireless interface detected. Hotspot will be configured on the selected LAN/Hotspot interface. Use an external AP broadcasting FastNet Test.';
        }

        return [
            'ok' => true,
            'version' => $version,
            'major' => self::routerOsMajor($version),
            'board_name' => self::prop($resource, 'board-name'),
            'platform' => self::prop($resource, 'platform'),
            'identity' => self::prop($identity, 'name'),
            'api_user' => [
                'username' => self::API_USERNAME,
                'status' => $api['status'],
                'message' => $api['message'],
            ],
            'interfaces' => array_map(function ($row) {
                return [
                    'name' => self::prop($row, 'name'),
                    'type' => self::prop($row, 'type'),
                    'running' => self::prop($row, 'running'),
                    'disabled' => self::prop($row, 'disabled'),
                ];
            }, $interfaces),
            'existing' => [
                'hotspots' => count($hotspots),
                'pppoe_servers' => count($pppoeServers),
                'dhcp_servers' => count($dhcpServers),
                'firewall_rules' => count($firewallRules),
                'nat_rules' => count($natRules),
                'wireless_interfaces' => count($wireless) + count($wifi),
            ],
            'warnings' => $warnings,
        ];
    }

    public static function buildProvisioningScript($router, $settings, $plans = null, $payment = null)
    {
        $plans = $plans === null ? self::plans() : $plans;
        $payment = $payment === null ? self::mpesaReadiness() : $payment;
        $warnings = self::buildWarnings($router, $settings, $payment);
        $selectedPlans = self::selectedPlans($plans, $settings);
        $sections = [];

        $sections[] = [
            'key' => 'api_user',
            'name' => 'FASTNETPAY API User Bootstrap',
            'commands' => self::buildApiUserPreviewScript($settings),
        ];

        $sections[] = [
            'key' => 'base',
            'name' => 'Base ISP Setup',
            'commands' => self::buildBaseIspScript($router, $settings),
        ];

        if (in_array($settings['deployment_profile'], ['hotspot', 'mixed'], true)) {
            $sections[] = [
                'key' => 'hotspot',
                'name' => 'Hotspot Setup',
                'commands' => self::buildHotspotScript($settings, $selectedPlans),
            ];
        }

        if (in_array($settings['deployment_profile'], ['pppoe', 'mixed'], true)) {
            $sections[] = [
                'key' => 'pppoe',
                'name' => 'PPPoE Setup',
                'commands' => self::buildPppoeScript($settings, $selectedPlans),
            ];
        }

        if (self::cfg('radius_enable', '') === 'yes' || self::cfg('radius_enable', '') === '1') {
            $sections[] = [
                'key' => 'radius',
                'name' => 'RADIUS Integration',
                'commands' => self::buildRadiusScript($settings),
            ];
        } else {
            $warnings[] = 'FASTNETPAY RADIUS is not enabled. Provisioning will use RouterOS API profiles now; enable FreeRADIUS later for scalable PPPoE/accounting/CoA.';
        }

        $sections[] = [
            'key' => 'walled_garden',
            'name' => 'Walled Garden and Captive Portal',
            'commands' => self::buildWalledGardenScript($settings, $payment),
        ];

        if ($settings['portal_mode'] === 'static') {
            $sections[] = [
                'key' => 'static_portal',
                'name' => 'MikroTik Captive Portal Files',
                'commands' => self::buildCaptivePortalScript($router, $settings),
            ];
        }

        if (in_array($settings['deployment_profile'], ['security', 'base', 'hotspot', 'pppoe', 'mixed'], true)) {
            $sections[] = [
                'key' => 'security',
                'name' => 'Security Hardening',
                'commands' => self::buildSecurityScript($settings),
            ];
        }

        $script = [];
        foreach ($sections as $section) {
            $script[] = '# ===== ' . $section['name'] . ' =====';
            foreach ($section['commands'] as $command) {
                $script[] = $command;
            }
            $script[] = '';
        }

        return [
            'sections' => $sections,
            'warnings' => array_values(array_unique(array_filter($warnings))),
            'script' => trim(implode("\n", $script)) . "\n",
            'selected_plans' => $selectedPlans,
        ];
    }

    public static function runProvisioning($router, $settings, $admin)
    {
        self::installSchema();
        if (!$router) {
            throw new Exception('Save the router first, then run automatic provisioning from the router list or edit page.');
        }

        self::savePortMapping((int) $router['id'], $settings);
        $preview = self::buildProvisioningScript($router, $settings, self::plans(), self::mpesaReadiness());
        $detect = ['version' => 'unknown'];
        try {
            $detect = self::detect($router, $settings);
        } catch (Throwable $e) {
            $detect['version'] = 'unknown';
        }

        $run = ORM::for_table('router_provisioning_runs')->create();
        $run->router_id = (int) $router['id'];
        $run->deployment_profile = $settings['deployment_profile'];
        $run->routeros_version = $detect['version'] ?? '';
        $run->status = 'running';
        $run->started_at = date('Y-m-d H:i:s');
        $run->created_by = (int) ($admin['id'] ?? 0);
        $run->notes = json_encode(['warnings' => $preview['warnings']]);
        $run->save();
        $runId = (int) $run->id();

        $resultSteps = [];
        try {
            $api = self::prepareApiUser($router, $settings, $runId, true);
            $client = $api['client'];
            self::logStep($runId, 'Router API Connection', 'success', $api['message'], '');
            $resultSteps[] = ['name' => 'Router API Connection', 'status' => 'success', 'message' => $api['message']];

            $originalServerIp = (string) ($settings['fastnetpay_server_ip'] ?? '');
            $settings = self::withDetectedServerIp($client, $settings);
            if ($originalServerIp !== (string) ($settings['fastnetpay_server_ip'] ?? '')) {
                self::logStep($runId, 'FASTNETPAY Server IP Detection', 'success', 'Detected ' . $settings['fastnetpay_server_ip'] . ' from the live RouterOS API connection.', '');
                $resultSteps[] = ['name' => 'FASTNETPAY Server IP Detection', 'status' => 'success', 'message' => 'Using ' . $settings['fastnetpay_server_ip'] . ' for portal, walled garden, and API access.'];
                $preview = self::buildProvisioningScript($router, $settings, self::plans(), self::mpesaReadiness());
                $run->notes = json_encode(['warnings' => $preview['warnings'], 'fastnetpay_server_ip' => $settings['fastnetpay_server_ip']]);
                $run->save();
            }
        } catch (Throwable $e) {
            $message = self::redact($e->getMessage());
            self::logStep($runId, 'Router API Connection', 'failed', '', $message);
            $run->status = 'failed';
            $run->completed_at = date('Y-m-d H:i:s');
            $run->save();
            return [
                'ok' => false,
                'run_id' => $runId,
                'status' => 'failed',
                'backup_file' => '',
                'steps' => array_merge($resultSteps, [['name' => 'Router API Connection', 'status' => 'failed', 'message' => $message]]),
                'warnings' => $preview['warnings'],
            ];
        }

        $backup = self::createRouterBackups($client, $runId, (int) $router['id']);
        $run->backup_file = implode(', ', $backup['files']);
        $run->save();
        $resultSteps[] = [
            'name' => 'Backup Before Provisioning',
            'status' => $backup['ok'] ? 'success' : 'failed',
            'message' => $backup['message'],
        ];

        if (!$backup['ok'] && $settings['allow_backup_override'] !== 'yes') {
            $run->status = 'failed';
            $run->completed_at = date('Y-m-d H:i:s');
            $run->save();
            return [
                'ok' => false,
                'run_id' => $runId,
                'status' => 'failed',
                'backup_file' => $run->backup_file,
                'steps' => $resultSteps,
                'warnings' => array_merge($preview['warnings'], ['Provisioning stopped because router backup failed. Tick the backup override only when you have a manual backup.']),
            ];
        }

        if (!$backup['ok']) {
            self::logStep($runId, 'Backup Override', 'warning', 'Admin allowed provisioning without an automatic backup.', '');
            $resultSteps[] = ['name' => 'Backup Override', 'status' => 'warning', 'message' => 'Proceeding because backup override was explicitly enabled.'];
        }

        if ($settings['password'] === '') {
            self::logStep($runId, 'Security Warning', 'warning', 'Bootstrap/admin password is empty. FASTNETPAY uses fastnet-api-usr after bootstrap; set a strong admin password before production.', '');
            $resultSteps[] = ['name' => 'Security Warning', 'status' => 'warning', 'message' => 'Bootstrap/admin password is empty. Set a strong admin password before production.'];
        }

        $failed = false;
        foreach ($preview['sections'] as $index => $section) {
            if (($section['key'] ?? '') === 'api_user') {
                $resultSteps[] = ['name' => $section['name'], 'status' => 'success', 'message' => 'Handled before backup; password not included in generated scripts.'];
                continue;
            }
            $name = $section['name'];
            try {
                self::logStep($runId, $name, 'running', 'Applying ' . count($section['commands']) . ' command lines.', '');
                self::applySection($client, $runId, $index + 1, $section);
                self::logStep($runId, $name, 'success', implode("\n", $section['commands']), '');
                $resultSteps[] = ['name' => $name, 'status' => 'success', 'message' => 'Applied successfully.'];
            } catch (Throwable $e) {
                $failed = true;
                self::logStep($runId, $name, 'failed', implode("\n", $section['commands']), self::redact($e->getMessage()));
                $resultSteps[] = ['name' => $name, 'status' => 'failed', 'message' => self::redact($e->getMessage())];
                break;
            }
        }

        if (!$failed) {
            try {
                self::logStep($runId, 'RouterOS Direct Reconciliation', 'running', 'Verifying critical Hotspot, DHCP, PPPoE, bridge, and wireless objects through direct API calls.', '');
                $repairMessages = self::reconcileCriticalProvisioningState($client, $settings, $preview['selected_plans'], $router);
                self::logStep($runId, 'RouterOS Direct Reconciliation', 'success', implode("\n", $repairMessages), '');
                $resultSteps[] = ['name' => 'RouterOS Direct Reconciliation', 'status' => 'success', 'message' => 'Critical RouterOS objects were verified or repaired through direct API calls.'];
            } catch (Throwable $e) {
                $failed = true;
                self::logStep($runId, 'RouterOS Direct Reconciliation', 'failed', '', self::redact($e->getMessage()));
                $resultSteps[] = ['name' => 'RouterOS Direct Reconciliation', 'status' => 'failed', 'message' => self::redact($e->getMessage())];
            }
        }

        if (!$failed) {
            try {
                $final = self::collectFinalDiagnostics($client, $settings, self::mpesaReadiness());
                self::logStep($runId, 'Final Test', 'success', json_encode($final), '');
                $resultSteps[] = ['name' => 'Final Test', 'status' => $final['status'], 'message' => $final['message']];
            } catch (Throwable $e) {
                $final = null;
                self::logStep($runId, 'Final Test', 'warning', '', self::redact($e->getMessage()));
                $resultSteps[] = ['name' => 'Final Test', 'status' => 'warning', 'message' => 'Provisioning applied, but final checks need manual verification: ' . self::redact($e->getMessage())];
            }
        } else {
            $final = null;
        }

        $run->status = $failed ? 'failed' : 'completed';
        $run->completed_at = date('Y-m-d H:i:s');
        $run->save();

        return [
            'ok' => !$failed,
            'run_id' => $runId,
            'status' => $run->status,
            'backup_file' => $run->backup_file,
            'steps' => $resultSteps,
            'warnings' => $preview['warnings'],
            'final_result' => $final,
        ];
    }

    public static function finalTest($router, $settings)
    {
        if (!$router) {
            throw new Exception('Save the router first, then run the live final test.');
        }
        $api = self::prepareApiUser($router, $settings, 0, false);
        $settings = self::withDetectedServerIp($api['client'], $settings);
        return [
            'ok' => true,
            'connection' => $api['message'],
            'final_result' => self::collectFinalDiagnostics($api['client'], $settings, self::mpesaReadiness()),
        ];
    }

    private static function collectFinalDiagnostics($client, $settings, $payment)
    {
        $settings = self::withDetectedServerIp($client, $settings);
        $profile = $settings['deployment_profile'] ?? 'mixed';
        $expectsHotspot = in_array($profile, ['hotspot', 'mixed'], true);
        $expectsPppoe = in_array($profile, ['pppoe', 'mixed'], true);
        $hotspotInterface = self::rosName($settings['hotspot_interface'] ?: $settings['lan_interface'] ?: 'fastnetpay-bridge');
        $pppoeInterface = self::rosName($settings['pppoe_interface'] ?: $settings['lan_interface'] ?: 'fastnetpay-bridge');
        $bridge = self::bridgeNameFromSettings($settings);
        $gateway = self::cidrIp($settings['lan_gateway']);
        $dnsName = self::domain($settings['dns_name'], 'portal.fastnetpay.test');

        $resource = self::firstRow($client, '/system/resource/print');
        $interfaces = self::safeRows($client, '/interface/print');
        $bridges = self::safeRows($client, '/interface/bridge/print');
        $bridgePorts = self::safeRows($client, '/interface/bridge/port/print');
        $hotspots = self::safeRows($client, '/ip/hotspot/print');
        $hotspotProfiles = self::safeRows($client, '/ip/hotspot/profile/print');
        $dhcpServers = self::safeRows($client, '/ip/dhcp-server/print');
        $dhcpNetworks = self::safeRows($client, '/ip/dhcp-server/network/print');
        $addresses = self::safeRows($client, '/ip/address/print');
        $dnsStatic = self::safeRows($client, '/ip/dns/static/print');
        $pppoeServers = self::safeRows($client, '/interface/pppoe-server/server/print');
        $natRules = self::safeRows($client, '/ip/firewall/nat/print');
        $walledGarden = self::safeRows($client, '/ip/hotspot/walled-garden/print');
        $walledGardenIp = self::safeRows($client, '/ip/hotspot/walled-garden/ip/print');
        $files = self::safeRows($client, '/file/print');
        $wireless = self::safeRows($client, '/interface/wireless/print');
        $wifi = self::safeRows($client, '/interface/wifi/print');

        $items = [];
        $items[] = self::finalItem('api', 'RouterOS API connection', 'success', self::prop($resource, 'version', 'unknown'), 'Connected and read live router resources.');
        $items[] = self::finalItem('routeros', 'RouterOS version', 'success', self::prop($resource, 'version', 'unknown'), 'Board: ' . self::prop($resource, 'board-name', 'unknown'));

        if ($bridge) {
            $bridgeRow = self::findRowBy($bridges, 'name', $bridge);
            $ports = self::rowsBy($bridgePorts, 'bridge', $bridge);
            $items[] = self::finalItem('bridge', 'Hotspot bridge', $bridgeRow ? 'success' : 'failed', $bridge, $bridgeRow ? 'Bridge exists on MikroTik.' : 'Bridge is missing. Rerun provisioning to create it.');
            $items[] = self::finalItem('bridge_ports', 'Bridge ports', count($ports) > 0 ? 'success' : 'failed', (string) count($ports), count($ports) > 0 ? 'Ports on bridge: ' . implode(', ', self::rowPropertyList($ports, 'interface')) : 'No interfaces are attached to the Hotspot bridge, so SSID clients may not reach captive portal.');
        } else {
            $items[] = self::finalItem('hotspot_interface', 'Hotspot interface', self::findRowBy($interfaces, 'name', $hotspotInterface) ? 'success' : 'failed', $hotspotInterface, 'Selected interface must exist and receive client traffic.');
        }

        $legacySsid = self::rowsBy($wireless, 'ssid', 'FastNet Test');
        $wifiSsid = self::rowsBy($wifi, 'configuration.ssid', 'FastNet Test');
        $wirelessCount = count($wireless) + count($wifi);
        if ($wirelessCount === 0) {
            $items[] = self::finalItem('ssid', 'FastNet Test SSID', 'warning', 'No wireless interface', 'Use an external AP bridged to ' . $hotspotInterface . '.');
        } else {
            $items[] = self::finalItem('ssid', 'FastNet Test SSID', count($legacySsid) + count($wifiSsid) > 0 ? 'success' : 'warning', (string) (count($legacySsid) + count($wifiSsid)), 'Wireless interfaces should broadcast FastNet Test and be attached to the Hotspot bridge.');
        }

        if ($expectsHotspot) {
            $hotspot = self::findRowBy($hotspots, 'name', 'fastnetpay-hotspot');
            $hotspotProfile = self::findRowBy($hotspotProfiles, 'name', 'fastnetpay-hotspot-profile');
            $dhcp = self::findRowBy($dhcpServers, 'name', 'fastnetpay-dhcp');
            $portalFiles = self::countPortalFiles($files);
            $items[] = self::finalItem('hotspot', 'Hotspot server', $hotspot ? 'success' : 'failed', $hotspot ? self::prop($hotspot, 'interface') : 'missing', $hotspot ? 'Server is enabled on ' . self::prop($hotspot, 'interface') . '.' : 'Hotspot server fastnetpay-hotspot is missing.');
            $items[] = self::finalItem('hotspot_profile', 'Hotspot profile', ($hotspotProfile && self::prop($hotspotProfile, 'html-directory') === 'hotspot') ? 'success' : 'failed', $hotspotProfile ? self::prop($hotspotProfile, 'dns-name', $dnsName) : 'missing', $hotspotProfile ? 'HTML directory: ' . self::prop($hotspotProfile, 'html-directory', 'unknown') . ', login-by: ' . self::prop($hotspotProfile, 'login-by', 'unknown') : 'Hotspot profile is missing.');
            $items[] = self::finalItem('dhcp', 'Hotspot DHCP', ($dhcp && self::prop($dhcp, 'disabled', 'false') !== 'true') ? 'success' : 'failed', $dhcp ? self::prop($dhcp, 'interface') : 'missing', $dhcp ? 'DHCP server is active for client addressing.' : 'DHCP server fastnetpay-dhcp is missing.');
            $items[] = self::finalItem('dhcp_network', 'DHCP network/DNS', self::hasDhcpNetwork($dhcpNetworks, $gateway) ? 'success' : 'warning', $gateway, 'Clients should receive router gateway as DNS so captive portal detection works.');
            $items[] = self::finalItem('gateway', 'Hotspot gateway IP', self::hasAddress($addresses, $settings['lan_gateway'], $hotspotInterface) ? 'success' : 'warning', $settings['lan_gateway'], 'Gateway should be assigned to the Hotspot bridge/interface.');
            $items[] = self::finalItem('portal_dns', 'Portal DNS trigger', self::findRowBy($dnsStatic, 'name', $dnsName) ? 'success' : 'warning', $dnsName, 'Static DNS uses the portal name as a Hotspot trigger so clients are redirected to MikroTik login files.');
            $items[] = self::finalItem('portal_files', 'MikroTik portal files', $portalFiles['found'] === $portalFiles['total'] ? 'success' : 'failed', $portalFiles['found'] . '/' . $portalFiles['total'], 'Files: ' . implode(', ', $portalFiles['missing']) . ($portalFiles['missing'] ? ' missing' : ' all present'));
            $items[] = self::finalItem('walled_garden', 'Walled garden', count($walledGarden) + count($walledGardenIp) > 0 ? 'success' : 'warning', (string) (count($walledGarden) + count($walledGardenIp)), 'Unpaid users must reach FASTNETPAY portal/payment APIs only.');
        }

        if ($expectsPppoe) {
            $pppoe = self::findRowBy($pppoeServers, 'service-name', 'fastnetpay-pppoe') ?: self::findRowBy($pppoeServers, 'interface', $pppoeInterface);
            $items[] = self::finalItem('pppoe', 'PPPoE server', $pppoe ? 'success' : 'failed', $pppoe ? self::prop($pppoe, 'interface') : 'missing', $pppoe ? 'PPPoE server is available for fixed clients.' : 'PPPoE server is missing.');
        }

        $items[] = self::finalItem('nat', 'NAT masquerade', self::hasNat($natRules) ? 'success' : 'failed', (string) count($natRules), 'Clients need NAT to reach internet after authentication.');
        $items[] = self::finalItem('plans', 'FASTNETPAY packages', count(self::plans()) > 0 ? 'success' : 'failed', (string) count(self::plans()), 'Packages are available for captive portal display.');
        $items[] = self::finalItem('mpesa', 'MPESA STK Push readiness', $payment['ready'] ? 'success' : 'warning', $payment['ready'] ? 'Ready' : 'Incomplete', $payment['ready'] ? 'Gateway is enabled and credentials are present.' : 'Missing: ' . implode(', ', $payment['missing']));

        $status = 'success';
        foreach ($items as $item) {
            if ($item['status'] === 'failed') {
                $status = 'failed';
                break;
            }
            if ($item['status'] === 'warning' && $status !== 'failed') {
                $status = 'warning';
            }
        }

        return [
            'status' => $status,
            'message' => $status === 'success' ? 'All required live checks passed.' : ($status === 'warning' ? 'Provisioning completed with warnings. Review highlighted items.' : 'Some required live checks failed. Rerun provisioning or repair the failed items.'),
            'checked_at' => date('Y-m-d H:i:s'),
            'hotspot_interface' => $hotspotInterface,
            'pppoe_interface' => $pppoeInterface,
            'bridge' => $bridge,
            'portal_url' => self::hotspotApiBaseUrl($settings) . '/?_route=api/hotspot',
            'items' => $items,
        ];
    }

    private static function finalItem($key, $label, $status, $value, $message)
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'value' => (string) $value,
            'message' => (string) $message,
        ];
    }

    private static function findRowBy($rows, $property, $value)
    {
        foreach ($rows as $row) {
            if ((string) self::prop($row, $property) === (string) $value) {
                return $row;
            }
        }
        return null;
    }

    private static function rowsBy($rows, $property, $value)
    {
        $matches = [];
        foreach ($rows as $row) {
            if ((string) self::prop($row, $property) === (string) $value) {
                $matches[] = $row;
            }
        }
        return $matches;
    }

    private static function rowPropertyList($rows, $property)
    {
        $values = [];
        foreach ($rows as $row) {
            $value = self::prop($row, $property);
            if ($value !== '') {
                $values[] = $value;
            }
        }
        return array_values(array_unique($values));
    }

    private static function countPortalFiles($files)
    {
        $required = [
            'hotspot/index',
            'hotspot/index.html',
            'hotspot/login',
            'hotspot/login.html',
            'hotspot/rlogin.html',
            'hotspot/redirect.html',
            'hotspot/status.html',
            'hotspot/logout.html',
            'hotspot/alogin.html',
            'hotspot/error.html',
            'hotspot/radvert.html',
            'hotspot/capport.json',
            'hotspot/md5.js',
            'hotspot/fastnetpay-hotspot.css',
            'hotspot/fastnetpay-hotspot.js',
        ];
        $names = self::rowPropertyList($files, 'name');
        $missing = [];
        foreach ($required as $file) {
            if (!in_array($file, $names, true)) {
                $missing[] = $file;
            }
        }
        return [
            'found' => count($required) - count($missing),
            'total' => count($required),
            'missing' => $missing,
        ];
    }

    private static function hasDhcpNetwork($rows, $gateway)
    {
        foreach ($rows as $row) {
            $rowGateway = self::prop($row, 'gateway');
            $dns = self::prop($row, 'dns-server');
            if ($rowGateway === $gateway && strpos(',' . $dns . ',', ',' . $gateway . ',') !== false) {
                return true;
            }
        }
        return false;
    }

    private static function hasAddress($rows, $address, $interface)
    {
        $addressIp = self::cidrIp($address);
        foreach ($rows as $row) {
            if (self::cidrIp(self::prop($row, 'address')) === $addressIp && self::prop($row, 'interface') === $interface) {
                return true;
            }
        }
        return false;
    }

    private static function hasNat($rows)
    {
        foreach ($rows as $row) {
            if (self::prop($row, 'action') === 'masquerade' || self::prop($row, 'comment') === 'FASTNETPAY NAT masquerade') {
                return true;
            }
        }
        return false;
    }

    public static function runs($routerId = 0)
    {
        self::installSchema();
        $query = ORM::for_table('router_provisioning_runs')->order_by_desc('id');
        if ((int) $routerId > 0) {
            $query->where('router_id', (int) $routerId);
        }
        return $query->limit(50)->find_many();
    }

    public static function steps($runId)
    {
        return ORM::for_table('router_provisioning_steps')->where('run_id', (int) $runId)->order_by_asc('id')->find_many();
    }

    public static function stepsByRun($runs)
    {
        $grouped = [];
        foreach ($runs as $run) {
            $grouped[(int) $run['id']] = self::steps($run['id']);
        }
        return $grouped;
    }

    public static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function detectRouterOsVersion($client)
    {
        return self::prop(self::firstRow($client, '/system/resource/print'), 'version');
    }

    public static function isRouterOsV6($version)
    {
        return self::routerOsMajor($version) === 6;
    }

    public static function isRouterOsV7($version)
    {
        return self::routerOsMajor($version) === 7;
    }

    private static function buildApiUserPreviewScript($settings)
    {
        $serverIp = self::ip($settings['fastnetpay_server_ip'], self::defaultServerAddress());
        return [
            '# FASTNETPAY verifies or creates RouterOS API user "' . self::API_USERNAME . '" before backup/provisioning.',
            '# Bootstrap/admin credentials are used only for this setup step when the API user does not exist or needs its password reset.',
            '# API user password is never shown in previews, logs, downloaded scripts, or browser source.',
            '# RouterOS group: ' . self::API_GROUP . ' policies read,write,policy,test,api,sensitive.',
            '# RouterOS user: ' . self::API_USERNAME . ' with the password entered in the wizard.',
            '# API services are enabled in PHPNuxBill-compatible mode first; strict IP restrictions are applied only in the Security step after preview.',
            '# Planned FASTNETPAY server IP for strict security mode: ' . $serverIp,
        ];
    }

    private static function prepareApiUser($router, $settings, $runId = 0, $persistRouter = false)
    {
        $params = self::connectionParams($settings, $router);
        $apiUsername = self::API_USERNAME;
        $apiPassword = $settings['api_password'] ?: (($router && $router['username'] === self::API_USERNAME) ? $router['password'] : '');
        $errors = [];
        $defaultClient = null;
        $defaultParams = null;

        if ($router) {
            try {
                $defaultClient = self::nuxbillClientFromRouter($router);
                $defaultParams = self::paramsFromIpAddress($router['ip_address'], $params['port'], false);
            } catch (Throwable $e) {
                $errors[] = 'Saved PHPNuxBill router credentials: ' . self::redact($e->getMessage());
            }
        }

        if ($apiPassword !== '') {
            try {
                $apiParams = $defaultParams ?: $params;
                $client = self::clientForCredentials($apiParams, $apiUsername, $apiPassword);
                $status = self::apiUserStatus($client);
                if ($persistRouter) {
                    self::persistApiCredentials($router, $settings, $apiPassword, $apiParams);
                }
                return [
                    'client' => $client,
                    'status' => $status['exists'] ? 'verified' : 'connected',
                    'message' => 'Connected to MikroTik using FASTNETPAY API user "' . self::API_USERNAME . '". ' . $status['message'],
                ];
            } catch (Throwable $e) {
                $errors[] = 'FASTNETPAY API user: ' . self::redact($e->getMessage());
            }
        }

        if ($defaultClient && ($apiPassword === '' || $settings['ensure_api_user'] === 'no')) {
            return [
                'client' => $defaultClient,
                'status' => 'nuxbill-default',
                'message' => 'Connected using the saved PHPNuxBill router credentials from tbl_routers. Add a FASTNETPAY API user password to switch this router to "' . self::API_USERNAME . '".',
            ];
        }

        if ($defaultClient && $apiPassword !== '' && $settings['ensure_api_user'] !== 'no') {
            if ($runId > 0) {
                self::logStep($runId, 'FASTNETPAY API User Bootstrap', 'running', 'Creating/verifying RouterOS API user ' . self::API_USERNAME . ' through saved PHPNuxBill router credentials.', '');
            }
            try {
                self::ensureApiUser($defaultClient, $settings, $apiPassword);
                $apiParams = $defaultParams ?: $params;
                $client = self::clientForCredentials($apiParams, $apiUsername, $apiPassword);
                if ($persistRouter) {
                    self::persistApiCredentials($router, $settings, $apiPassword, $apiParams);
                }
                if ($runId > 0) {
                    self::logStep($runId, 'FASTNETPAY API User Bootstrap', 'success', 'Created or updated RouterOS API user ' . self::API_USERNAME . ' through saved PHPNuxBill router credentials.', '');
                }
                return [
                    'client' => $client,
                    'status' => 'created',
                    'message' => 'Created/verified "' . self::API_USERNAME . '" using the saved PHPNuxBill router connection and reconnected with it.',
                ];
            } catch (Throwable $e) {
                $errors[] = 'API user bootstrap through saved PHPNuxBill router credentials: ' . self::redact($e->getMessage());
                if ($runId > 0) {
                    self::logStep($runId, 'FASTNETPAY API User Bootstrap', 'warning', 'Falling back to saved PHPNuxBill router credentials.', self::redact($e->getMessage()));
                }
                return [
                    'client' => $defaultClient,
                    'status' => 'nuxbill-fallback',
                    'message' => 'API user setup failed, so FASTNETPAY fell back to the saved PHPNuxBill router credentials for compatibility. Error: ' . self::redact($e->getMessage()),
                ];
            }
        }

        if ($settings['ensure_api_user'] === 'no') {
            throw new Exception('FASTNETPAY API user "' . self::API_USERNAME . '" could not connect and automatic creation is disabled. Attempts: ' . implode(' | ', $errors));
        }

        $bootstrapUsername = trim((string) $settings['username']);
        $bootstrapPassword = (string) $settings['password'];
        if ($bootstrapUsername === '') {
            throw new Exception('FASTNETPAY API user is not ready. Enter a temporary bootstrap/admin username so the wizard can create "' . self::API_USERNAME . '". Attempts: ' . implode(' | ', $errors));
        }
        if ($router && $bootstrapUsername === (string) $router['username'] && $bootstrapPassword === (string) $router['password']) {
            throw new Exception('Could not connect to MikroTik using the saved PHPNuxBill router credentials or FASTNETPAY API user. Bootstrap form credentials match the saved router record, so they were not retried. Attempts: ' . implode(' | ', $errors));
        }

        try {
            $bootstrapClient = self::clientForCredentials($params, $bootstrapUsername, $bootstrapPassword);
        } catch (Throwable $e) {
            $errors[] = 'Bootstrap form credentials: ' . self::redact($e->getMessage());
            throw new Exception('Could not connect to MikroTik using the saved PHPNuxBill router credentials, FASTNETPAY API user, or bootstrap form credentials. Attempts: ' . implode(' | ', $errors));
        }

        if ($runId > 0) {
            self::logStep($runId, 'FASTNETPAY API User Bootstrap', 'running', 'Creating/verifying RouterOS API user ' . self::API_USERNAME, '');
        }

        if ($apiPassword === '') {
            return [
                'client' => $bootstrapClient,
                'status' => 'nuxbill-bootstrap',
                'message' => 'Connected using bootstrap credentials. Add a FASTNETPAY API user password to create "' . self::API_USERNAME . '".',
            ];
        }

        try {
            self::ensureApiUser($bootstrapClient, $settings, $apiPassword);
            if ($runId > 0) {
                self::logStep($runId, 'FASTNETPAY API User Bootstrap', 'success', 'Created or updated RouterOS API user ' . self::API_USERNAME . ' in PHPNuxBill-compatible API service mode.', '');
            }
        } catch (Throwable $e) {
            if ($runId > 0) {
                self::logStep($runId, 'FASTNETPAY API User Bootstrap', 'failed', '', self::redact($e->getMessage()));
            }
            throw new Exception('Failed to create FASTNETPAY API user "' . self::API_USERNAME . '": ' . self::redact($e->getMessage()));
        }

        try {
            $client = self::clientForCredentials($params, $apiUsername, $apiPassword);
        } catch (Throwable $e) {
            throw new Exception('Created FASTNETPAY API user, but reconnect with "' . self::API_USERNAME . '" failed: ' . self::redact($e->getMessage()));
        }

        if ($persistRouter) {
            self::persistApiCredentials($router, $settings, $apiPassword, $params);
        }

        return [
            'client' => $client,
            'status' => 'created',
            'message' => 'Created/verified "' . self::API_USERNAME . '" and reconnected with it. FASTNETPAY will use this API user for this router.',
        ];
    }

    private static function ensureApiUser($client, $settings, $apiPassword)
    {
        $apiPort = self::port($settings['api_port'], 8728);
        $apiSslPort = self::port($settings['api_ssl_port'], 8729);
        $policy = 'read,write,policy,test,api,sensitive';
        $commands = [
            ':if ([:len [/user group find name=' . self::q(self::API_GROUP) . ']] = 0) do={/user group add name=' . self::q(self::API_GROUP) . ' policy=' . $policy . ' comment=' . self::q('FASTNETPAY API-only automation group') . '} else={/user group set [find name=' . self::q(self::API_GROUP) . '] policy=' . $policy . ' comment=' . self::q('FASTNETPAY API-only automation group') . '}',
            ':if ([:len [/user find name=' . self::q(self::API_USERNAME) . ']] = 0) do={/user add name=' . self::q(self::API_USERNAME) . ' password=' . self::q($apiPassword) . ' group=' . self::q(self::API_GROUP) . ' disabled=no comment=' . self::q('FASTNETPAY API automation user') . '} else={/user set [find name=' . self::q(self::API_USERNAME) . '] password=' . self::q($apiPassword) . ' group=' . self::q(self::API_GROUP) . ' disabled=no comment=' . self::q('FASTNETPAY API automation user') . '}',
            '/ip service set api disabled=no port=' . (int) $apiPort . ' address=' . self::q('0.0.0.0/0'),
            ':do {/ip service set api-ssl disabled=no port=' . (int) $apiSslPort . ' address=' . self::q('0.0.0.0/0') . '} on-error={:log warning ' . self::q('FASTNETPAY API-SSL service could not be updated') . '}',
        ];

        self::runScriptSource($client, 'fnp_api_user_' . time(), implode("\r\n", $commands), 'FASTNETPAY API user bootstrap');
    }

    private static function apiUserStatus($client)
    {
        $users = self::safeRows($client, '/user/print');
        foreach ($users as $user) {
            if (self::prop($user, 'name') === self::API_USERNAME) {
                return [
                    'exists' => true,
                    'message' => 'API user exists in group "' . self::prop($user, 'group', 'unknown') . '".',
                ];
            }
        }
        return ['exists' => false, 'message' => 'API user is not visible to this session.'];
    }

    private static function persistApiCredentials($router, $settings, $apiPassword, $params)
    {
        if (!$router) {
            return;
        }
        $router->username = self::API_USERNAME;
        $router->password = $apiPassword;
        if (!empty($params['host'])) {
            $router->ip_address = $params['host'] . ':' . (int) $params['port'];
        }
        if (!empty($settings['router_name'])) {
            $router->name = self::routerName($router, $settings);
        }
        $router->save();
    }

    private static function buildBaseIspScript($router, $settings)
    {
        $routerName = self::routerName($router, $settings);
        $wan = self::rosName($settings['wan_interface'] ?: 'ether1');
        $lan = self::rosName($settings['lan_interface'] ?: ($settings['hotspot_interface'] ?: 'fastnetpay-bridge'));
        $management = self::rosName($settings['management_interface'] ?: 'ether4');
        $dnsServers = self::csvIps($settings['dns_servers'], '1.1.1.1,8.8.8.8');
        $bridge = self::bridgeNameFromSettings($settings);
        $bridgeCommands = $bridge ? self::buildBridgeScript($bridge, $wan, $settings) : [];
        $managementCommands = self::buildManagementInterfaceScript($management, $bridge, $wan);

        return array_values(array_filter(array_merge([
            '# FASTNETPAY base identity, DNS, interface lists, and NAT.',
            '/system identity set name=' . self::q($routerName),
            '/system clock set time-zone-name=' . self::q('Africa/Nairobi'),
            '/ip dns set allow-remote-requests=yes servers=' . self::q($dnsServers),
        ], $bridgeCommands, $managementCommands, [
            self::onceByFind('/interface list find name=' . self::q('WAN'), '/interface list add name=' . self::q('WAN') . ' comment=' . self::q('FASTNETPAY WAN interfaces')),
            self::onceByFind('/interface list find name=' . self::q('LAN'), '/interface list add name=' . self::q('LAN') . ' comment=' . self::q('FASTNETPAY LAN interfaces')),
            self::onceByFind('/interface list member find list=' . self::q('WAN') . ' interface=' . self::q($wan), '/interface list member add list=' . self::q('WAN') . ' interface=' . self::q($wan) . ' comment=' . self::q('FASTNETPAY WAN mapping')),
            self::onceByFind('/interface list member find list=' . self::q('LAN') . ' interface=' . self::q($lan), '/interface list member add list=' . self::q('LAN') . ' interface=' . self::q($lan) . ' comment=' . self::q('FASTNETPAY LAN mapping')),
            self::onceByFind('/ip firewall nat find comment=' . self::q('FASTNETPAY NAT masquerade'), '/ip firewall nat add chain=srcnat out-interface-list=' . self::q('WAN') . ' action=masquerade comment=' . self::q('FASTNETPAY NAT masquerade')),
        ])));
    }

    private static function bridgeNameFromSettings($settings)
    {
        foreach (['hotspot_interface', 'lan_interface', 'pppoe_interface'] as $field) {
            $name = self::rosName($settings[$field] ?? '');
            if ($name !== '' && self::isBridgeName($name)) {
                return $name;
            }
        }
        return 'fastnetpay-bridge';
    }

    private static function isBridgeName($name)
    {
        return preg_match('/(^bridge|bridge$|-bridge$)/i', (string) $name) === 1;
    }

    private static function normalizeInterfaceName($name, $default = '')
    {
        $name = trim((string) $name);
        if ($name === '') {
            return $default;
        }
        $compact = strtolower(preg_replace('/\s+/', '', $name));
        if (preg_match('/^port([0-9]+)$/', $compact, $match)) {
            return 'ether' . $match[1];
        }
        if (in_array($compact, ['bridge-fastnetpay', 'fastnetpaybridge', 'fnpbridge', 'hotspot-fastnetpay', 'pppoe-fastnetpay'], true)) {
            return 'fastnetpay-bridge';
        }
        if ($default !== '' && preg_match('/^brigde/i', $name)) {
            return $default;
        }
        return $name;
    }

    private static function buildBridgeScript($bridge, $wan, $settings)
    {
        $management = self::rosName($settings['management_interface'] ?: 'ether4');
        $commands = [
            self::onceByFind('/interface bridge find name=' . self::q($bridge), '/interface bridge add name=' . self::q($bridge) . ' protocol-mode=none comment=' . self::q('FASTNETPAY hotspot/LAN bridge')),
        ];

        foreach (['lan_interface', 'hotspot_interface', 'pppoe_interface'] as $field) {
            $interface = self::rosName($settings[$field] ?? '');
            if ($interface === '' || $interface === $bridge || $interface === $wan || $interface === $management || self::isBridgeName($interface)) {
                continue;
            }
            $commands[] = self::onceByFind('/interface bridge port find interface=' . self::q($interface), '/interface bridge port add bridge=' . self::q($bridge) . ' interface=' . self::q($interface) . ' comment=' . self::q('FASTNETPAY selected bridge port'));
        }

        $commands[] = ':do {:foreach i in=[/interface ethernet find] do={:local n [/interface ethernet get $i name]; :if (($n != ' . self::q($wan) . ') and ($n != ' . self::q($bridge) . ') and ($n != ' . self::q($management) . ')) do={:if ([:len [/interface bridge port find interface=$n]] > 0) do={/interface bridge port set [find interface=$n] bridge=' . self::q($bridge) . ' comment=' . self::q('FASTNETPAY auto LAN bridge port') . '} else={/interface bridge port add bridge=' . self::q($bridge) . ' interface=$n comment=' . self::q('FASTNETPAY auto LAN bridge port') . '}}}} on-error={:log warning ' . self::q('FASTNETPAY could not auto-add ethernet ports to bridge') . '}';
        $commands[] = ':do {:foreach i in=[/interface wireless find] do={:local n [/interface wireless get $i name]; :if ([:len [/interface bridge port find interface=$n]] > 0) do={/interface bridge port set [find interface=$n] bridge=' . self::q($bridge) . ' comment=' . self::q('FASTNETPAY wireless hotspot bridge port') . '} else={/interface bridge port add bridge=' . self::q($bridge) . ' interface=$n comment=' . self::q('FASTNETPAY wireless hotspot bridge port') . '}}} on-error={:log warning ' . self::q('FASTNETPAY no legacy wireless bridge ports added') . '}';
        $commands[] = ':do {:foreach i in=[/interface wifi find] do={:local n [/interface wifi get $i name]; :if ([:len [/interface bridge port find interface=$n]] > 0) do={/interface bridge port set [find interface=$n] bridge=' . self::q($bridge) . ' comment=' . self::q('FASTNETPAY WiFi hotspot bridge port') . '} else={/interface bridge port add bridge=' . self::q($bridge) . ' interface=$n comment=' . self::q('FASTNETPAY WiFi hotspot bridge port') . '}}} on-error={:log warning ' . self::q('FASTNETPAY no WiFi bridge ports added') . '}';

        return $commands;
    }

    private static function buildManagementInterfaceScript($management, $hotspotBridge, $wan)
    {
        if ($management === '' || $management === $hotspotBridge || $management === $wan) {
            return [];
        }

        $commands = [
            self::onceByFind('/interface list find name=' . self::q('MGMT'), '/interface list add name=' . self::q('MGMT') . ' comment=' . self::q('FASTNETPAY management interfaces')),
        ];

        if (self::isBridgeName($management)) {
            $commands[] = self::onceByFind('/interface bridge find name=' . self::q($management), '/interface bridge add name=' . self::q($management) . ' protocol-mode=rstp comment=' . self::q('FASTNETPAY management bridge'));
        }

        $commands[] = ':do {:if ([:len [/interface list member find list=' . self::q('MGMT') . ' interface=' . self::q($management) . ']] = 0) do={/interface list member add list=' . self::q('MGMT') . ' interface=' . self::q($management) . ' comment=' . self::q('FASTNETPAY management mapping') . '}} on-error={:log warning ' . self::q('FASTNETPAY management interface not found or not mapped') . '}';
        $commands[] = ':do {:foreach p in=[/interface bridge port find interface=' . self::q($management) . '] do={:local b [/interface bridge port get $p bridge]; :if ($b = ' . self::q($hotspotBridge) . ') do={/interface bridge port remove $p}}} on-error={:log warning ' . self::q('FASTNETPAY preserved management interface on existing management bridge') . '}';

        return $commands;
    }

    private static function gatewayAddressScript($address, $interface)
    {
        return ':if ([:len [/ip address find comment=' . self::q('FASTNETPAY hotspot gateway') . ']] > 0) do={/ip address set [find comment=' . self::q('FASTNETPAY hotspot gateway') . '] address=' . self::q($address) . ' interface=' . self::q($interface) . '} else={:if ([:len [/ip address find address=' . self::q($address) . ']] > 0) do={/ip address set [find address=' . self::q($address) . '] interface=' . self::q($interface) . ' comment=' . self::q('FASTNETPAY hotspot gateway') . '} else={/ip address add address=' . self::q($address) . ' interface=' . self::q($interface) . ' comment=' . self::q('FASTNETPAY hotspot gateway') . '}}';
    }

    private static function dhcpNetworkScript($network, $gateway)
    {
        return ':if ([:len [/ip dhcp-server network find comment=' . self::q('FASTNETPAY DHCP network') . ']] > 0) do={/ip dhcp-server network set [find comment=' . self::q('FASTNETPAY DHCP network') . '] address=' . self::q($network) . ' gateway=' . self::q($gateway) . ' dns-server=' . self::q($gateway) . '} else={:if ([:len [/ip dhcp-server network find address=' . self::q($network) . ']] > 0) do={/ip dhcp-server network set [find address=' . self::q($network) . '] gateway=' . self::q($gateway) . ' dns-server=' . self::q($gateway) . ' comment=' . self::q('FASTNETPAY DHCP network') . '} else={/ip dhcp-server network add address=' . self::q($network) . ' gateway=' . self::q($gateway) . ' dns-server=' . self::q($gateway) . ' comment=' . self::q('FASTNETPAY DHCP network') . '}}';
    }

    private static function buildHotspotScript($settings, $plans)
    {
        $gateway = self::cidrIp($settings['lan_gateway']);
        $network = self::networkFromCidr($settings['lan_gateway']);
        $hotspotInterface = self::rosName($settings['hotspot_interface'] ?: $settings['lan_interface'] ?: 'fastnetpay-bridge');
        $dnsName = self::domain($settings['dns_name'], 'portal.fastnetpay.test');
        $bridge = self::bridgeNameFromSettings($settings);
        $commands = [
            '# FASTNETPAY hotspot, DHCP, and package profiles.',
        ];
        if ($bridge) {
            $commands = array_merge($commands, self::buildBridgeScript($bridge, self::rosName($settings['wan_interface'] ?: 'ether1'), $settings));
        }
        $commands = array_merge($commands, [
            self::onceByFind('/ip pool find name=' . self::q('fastnetpay-hotspot-pool'), '/ip pool add name=' . self::q('fastnetpay-hotspot-pool') . ' ranges=' . self::q(self::pool($settings['hotspot_pool'], '192.168.90.50-192.168.90.250')) . ' comment=' . self::q('FASTNETPAY hotspot client pool')),
            self::gatewayAddressScript($settings['lan_gateway'], $hotspotInterface),
            self::upsertByFind('/ip dhcp-server find name=' . self::q('fastnetpay-dhcp'), '/ip dhcp-server add name=' . self::q('fastnetpay-dhcp') . ' interface=' . self::q($hotspotInterface) . ' address-pool=' . self::q('fastnetpay-hotspot-pool') . ' lease-time=' . self::q(self::lease($settings['dhcp_lease_time'])) . ' disabled=no comment=' . self::q('FASTNETPAY DHCP server'), '/ip dhcp-server set [find name=' . self::q('fastnetpay-dhcp') . '] interface=' . self::q($hotspotInterface) . ' address-pool=' . self::q('fastnetpay-hotspot-pool') . ' lease-time=' . self::q(self::lease($settings['dhcp_lease_time'])) . ' disabled=no'),
            self::dhcpNetworkScript($network, $gateway),
            self::upsertByFind('/ip dns static find name=' . self::q($dnsName), '/ip dns static add name=' . self::q($dnsName) . ' address=' . self::q($gateway) . ' comment=' . self::q('FASTNETPAY hotspot portal DNS'), '/ip dns static set [find name=' . self::q($dnsName) . '] address=' . self::q($gateway) . ' comment=' . self::q('FASTNETPAY hotspot portal DNS')),
            self::upsertByFind('/ip hotspot profile find name=' . self::q('fastnetpay-hotspot-profile'), '/ip hotspot profile add name=' . self::q('fastnetpay-hotspot-profile') . ' hotspot-address=' . self::q($gateway) . ' dns-name=' . self::q($dnsName) . ' html-directory=' . self::q('hotspot') . ' login-by=http-chap,http-pap,cookie comment=' . self::q('FASTNETPAY hosted captive portal profile'), '/ip hotspot profile set [find name=' . self::q('fastnetpay-hotspot-profile') . '] hotspot-address=' . self::q($gateway) . ' dns-name=' . self::q($dnsName) . ' html-directory=' . self::q('hotspot') . ' login-by=http-chap,http-pap,cookie'),
            self::upsertByFind('/ip hotspot find name=' . self::q('fastnetpay-hotspot'), '/ip hotspot add name=' . self::q('fastnetpay-hotspot') . ' interface=' . self::q($hotspotInterface) . ' address-pool=' . self::q('fastnetpay-hotspot-pool') . ' profile=' . self::q('fastnetpay-hotspot-profile') . ' disabled=no comment=' . self::q('FASTNETPAY hotspot server'), '/ip hotspot set [find name=' . self::q('fastnetpay-hotspot') . '] interface=' . self::q($hotspotInterface) . ' address-pool=' . self::q('fastnetpay-hotspot-pool') . ' profile=' . self::q('fastnetpay-hotspot-profile') . ' disabled=no'),
            ':do {/interface wireless set [find] mode=ap-bridge ssid=' . self::q('FastNet Test') . ' disabled=no} on-error={:log warning ' . self::q('FASTNETPAY no legacy wireless interface detected; use external AP for FastNet Test') . '}',
            ':do {/interface wifi set [find] configuration.ssid=' . self::q('FastNet Test') . ' disabled=no} on-error={:log warning ' . self::q('FASTNETPAY no WiFi interface detected; use external AP for FastNet Test') . '}',
        ]);

        $hotspotCount = 0;
        foreach ($plans as $plan) {
            if (strcasecmp($plan['type'], 'Hotspot') !== 0) {
                continue;
            }
            $hotspotCount++;
            $profile = self::profileName($plan);
            $add = '/ip hotspot user profile add name=' . self::q($profile) .
                ' shared-users=' . self::q(max(1, (int) ($plan['shared_users'] ?: 1))) .
                ' rate-limit=' . self::q(self::planRate($plan)) .
                ' comment=' . self::q('FASTNETPAY plan ' . $plan['name_plan']);
            $set = '/ip hotspot user profile set [find name=' . self::q($profile) . '] shared-users=' . self::q(max(1, (int) ($plan['shared_users'] ?: 1))) . ' rate-limit=' . self::q(self::planRate($plan));
            $commands[] = self::upsertByFind('/ip hotspot user profile find name=' . self::q($profile), $add, $set);
        }

        if ($hotspotCount === 0) {
            $commands[] = self::onceByFind('/ip hotspot user profile find name=' . self::q('FNP-Sample-Hotspot'), '/ip hotspot user profile add name=' . self::q('FNP-Sample-Hotspot') . ' shared-users=1 rate-limit=' . self::q('2M/2M') . ' comment=' . self::q('FASTNETPAY sample hotspot profile'));
        }

        return $commands;
    }

    private static function buildPppoeScript($settings, $plans)
    {
        $gateway = self::cidrIp($settings['lan_gateway']);
        $pppoeInterface = self::rosName($settings['pppoe_interface'] ?: $settings['lan_interface'] ?: 'fastnetpay-bridge');
        $commands = [
            '# FASTNETPAY PPPoE pool, profiles, and server.',
            self::onceByFind('/ip pool find name=' . self::q('fastnetpay-pppoe-pool'), '/ip pool add name=' . self::q('fastnetpay-pppoe-pool') . ' ranges=' . self::q(self::pool($settings['pppoe_pool'], '100.64.10.10-100.64.10.250')) . ' comment=' . self::q('FASTNETPAY PPPoE client pool')),
        ];
        $defaultProfile = 'FNP-PPPoE-Default';
        $pppoeCount = 0;
        foreach ($plans as $plan) {
            if (strcasecmp($plan['type'], 'PPPOE') !== 0) {
                continue;
            }
            $pppoeCount++;
            $profile = self::profileName($plan);
            $defaultProfile = $defaultProfile === 'FNP-PPPoE-Default' ? $profile : $defaultProfile;
            $pool = trim((string) ($plan['pool'] ?? '')) ?: 'fastnetpay-pppoe-pool';
            $add = '/ppp profile add name=' . self::q($profile) .
                ' local-address=' . self::q($gateway) .
                ' remote-address=' . self::q($pool) .
                ' rate-limit=' . self::q(self::planRate($plan)) .
                ' only-one=yes comment=' . self::q('FASTNETPAY PPPoE plan ' . $plan['name_plan']);
            $set = '/ppp profile set [find name=' . self::q($profile) . '] local-address=' . self::q($gateway) . ' remote-address=' . self::q($pool) . ' rate-limit=' . self::q(self::planRate($plan)) . ' only-one=yes';
            $commands[] = self::upsertByFind('/ppp profile find name=' . self::q($profile), $add, $set);
        }
        if ($pppoeCount === 0) {
            $commands[] = self::onceByFind('/ppp profile find name=' . self::q($defaultProfile), '/ppp profile add name=' . self::q($defaultProfile) . ' local-address=' . self::q($gateway) . ' remote-address=' . self::q('fastnetpay-pppoe-pool') . ' rate-limit=' . self::q('5M/5M') . ' only-one=yes comment=' . self::q('FASTNETPAY sample PPPoE profile'));
        }
        $commands[] = self::onceByFind('/interface pppoe-server server find service-name=' . self::q('fastnetpay-pppoe'), '/interface pppoe-server server add service-name=' . self::q('fastnetpay-pppoe') . ' interface=' . self::q($pppoeInterface) . ' default-profile=' . self::q($defaultProfile) . ' authentication=pap,chap disabled=no comment=' . self::q('FASTNETPAY PPPoE server'));

        if ($settings['create_sample_user'] === 'yes') {
            $commands[] = self::onceByFind('/ppp secret find name=' . self::q('fnp-test'), '/ppp secret add name=' . self::q('fnp-test') . ' password=' . self::q('ChangeMe123') . ' service=pppoe profile=' . self::q($defaultProfile) . ' comment=' . self::q('FASTNETPAY sample test user - change/remove before production'));
        }

        return $commands;
    }

    private static function buildRadiusScript($settings)
    {
        $serverIp = self::ip($settings['fastnetpay_server_ip'], self::defaultServerAddress());
        $secret = trim($settings['radius_secret']) ?: self::randomSecret();
        return [
            '# FASTNETPAY RADIUS setup. Keep the shared secret private.',
            self::onceByFind('/radius find comment=' . self::q('FASTNETPAY RADIUS server'), '/radius add address=' . self::q($serverIp) . ' secret=' . self::q($secret) . ' service=hotspot,ppp timeout=3s comment=' . self::q('FASTNETPAY RADIUS server')),
            ':if ([:len [/ip hotspot profile find name=' . self::q('fastnetpay-hotspot-profile') . ']] > 0) do={/ip hotspot profile set [find name=' . self::q('fastnetpay-hotspot-profile') . '] use-radius=yes}',
            '/ppp aaa set use-radius=yes accounting=yes interim-update=5m',
        ];
    }

    private static function buildWalledGardenScript($settings, $payment)
    {
        $domains = self::walledGardenDomains($settings, $payment);
        $commands = [
            '# FASTNETPAY walled garden lets unpaid hotspot users reach the billing portal and payment endpoints only.',
        ];
        foreach ($domains as $domain) {
            $commands[] = self::onceByFind('/ip hotspot walled-garden find dst-host=' . self::q($domain), '/ip hotspot walled-garden add dst-host=' . self::q($domain) . ' action=allow comment=' . self::q('FASTNETPAY walled garden ' . $domain));
        }
        $serverIp = self::ip($settings['fastnetpay_server_ip'], self::defaultServerAddress());
        if ($serverIp) {
            $commands[] = self::onceByFind('/ip hotspot walled-garden ip find dst-address=' . self::q($serverIp), '/ip hotspot walled-garden ip add dst-address=' . self::q($serverIp) . ' action=accept comment=' . self::q('FASTNETPAY portal server IP'));
        }
        $commands[] = '# Portal URL: ' . self::portalUrl();
        $commands[] = '# M-Pesa callback URL: ' . ($payment['callback_url'] ?: APP_URL . '/?_route=callback/mpesastkpush');

        return $commands;
    }

    private static function buildCaptivePortalScript($router, $settings)
    {
        $routerId = $router ? (int) $router['id'] : 0;
        $token = $routerId > 0 ? self::portalTokenForRouter($routerId) : 'SAVE_ROUTER_FIRST';
        $base = self::hotspotApiBaseUrl($settings);
        $gateway = self::cidrIp($settings['lan_gateway'] ?? '192.168.90.1/24') ?: '192.168.90.1';
        $portal = self::domain($settings['dns_name'] ?? 'portal.fastnetpay.test', 'portal.fastnetpay.test');
        $files = [
            'index',
            'index.html',
            'login',
            'login.html',
            'rlogin.html',
            'redirect.html',
            'status.html',
            'logout.html',
            'alogin.html',
            'error.html',
            'radvert.html',
            'capport.json',
            'md5.js',
            'fastnetpay-hotspot.css',
            'fastnetpay-hotspot.js',
        ];

        $commands = [
            '# FASTNETPAY MikroTik-hosted captive portal files.',
        ];

        foreach ($files as $file) {
            $url = $base . '/?_route=api/hotspot/portal-file&router=' . $routerId . '&token=' . rawurlencode($token) . '&base=' . rawurlencode($base) . '&gateway=' . rawurlencode($gateway) . '&portal=' . rawurlencode($portal) . '&file=' . rawurlencode($file);
            $commands[] = '/tool fetch url=' . self::q($url) . ' dst-path=' . self::q('hotspot/' . $file) . ' keep-result=yes';
        }

        $commands[] = '# Portal API base: ' . $base . '/?_route=api/hotspot';

        return $commands;
    }

    private static function buildSecurityScript($settings)
    {
        $level = $settings['security_level'];
        $serverIp = self::ip($settings['fastnetpay_server_ip'], self::defaultServerAddress());
        $commands = [
            '# FASTNETPAY security hardening. Review management IPs before applying strict mode.',
            self::onceByFind('/ip firewall address-list find list=' . self::q('fastnetpay-management') . ' address=' . self::q($serverIp), '/ip firewall address-list add list=' . self::q('fastnetpay-management') . ' address=' . self::q($serverIp) . ' comment=' . self::q('FASTNETPAY server management access')),
            self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY drop invalid input'), '/ip firewall filter add chain=input connection-state=invalid action=drop comment=' . self::q('FASTNETPAY drop invalid input')),
            self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY accept established input'), '/ip firewall filter add chain=input connection-state=established,related action=accept comment=' . self::q('FASTNETPAY accept established input')),
            self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY connection abuse watch'), '/ip firewall filter add chain=input protocol=tcp connection-limit=40,32 action=add-src-to-address-list address-list=' . self::q('fastnetpay-connection-abuse') . ' address-list-timeout=10m comment=' . self::q('FASTNETPAY connection abuse watch')),
            self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY drop connection abuse'), '/ip firewall filter add chain=input src-address-list=' . self::q('fastnetpay-connection-abuse') . ' action=drop comment=' . self::q('FASTNETPAY drop connection abuse')),
            self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY rate-limit ICMP'), '/ip firewall filter add chain=input protocol=icmp limit=10,5:packet action=accept comment=' . self::q('FASTNETPAY rate-limit ICMP')),
        ];

        if (in_array($level, ['recommended', 'strict'], true)) {
            $commands[] = self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY detect port scans'), '/ip firewall filter add chain=input protocol=tcp psd=21,3s,3,1 action=add-src-to-address-list address-list=' . self::q('fastnetpay-port-scanners') . ' address-list-timeout=1d comment=' . self::q('FASTNETPAY detect port scans'));
            $commands[] = self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY drop port scanners'), '/ip firewall filter add chain=input src-address-list=' . self::q('fastnetpay-port-scanners') . ' action=drop comment=' . self::q('FASTNETPAY drop port scanners'));
            $commands[] = self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY drop invalid forward'), '/ip firewall filter add chain=forward connection-state=invalid action=drop comment=' . self::q('FASTNETPAY drop invalid forward'));
            $commands[] = self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY accept established forward'), '/ip firewall filter add chain=forward connection-state=established,related action=accept comment=' . self::q('FASTNETPAY accept established forward'));
            $commands[] = '/ip dns set allow-remote-requests=yes';
            $commands[] = self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY block open DNS resolver from WAN'), '/ip firewall filter add chain=input protocol=udp dst-port=53 in-interface-list=WAN action=drop comment=' . self::q('FASTNETPAY block open DNS resolver from WAN'));
        }

        if ($level === 'strict') {
            $commands[] = self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY allow RouterOS API from server'), '/ip firewall filter add chain=input protocol=tcp dst-port=8728,8729 src-address=' . self::q($serverIp) . ' action=accept comment=' . self::q('FASTNETPAY allow RouterOS API from server'));
            $commands[] = '/ip service set api address=' . self::q($serverIp . '/32') . ' disabled=no';
            $commands[] = '/ip service set api-ssl address=' . self::q($serverIp . '/32') . ' disabled=no';
            $commands[] = '/ip service set telnet disabled=yes';
            $commands[] = '/ip service set ftp disabled=yes';
            $commands[] = '/ip service set www disabled=yes';
            $commands[] = '/ip service set www-ssl disabled=yes';
            $commands[] = '/ip service set ssh address=' . self::q($serverIp . '/32');
            $commands[] = '/ip service set winbox address=' . self::q($serverIp . '/32');
            $commands[] = self::onceByFind('/ip firewall filter find comment=' . self::q('FASTNETPAY strict drop unmanaged WAN input'), '/ip firewall filter add chain=input in-interface-list=WAN src-address-list=!fastnetpay-management action=drop comment=' . self::q('FASTNETPAY strict drop unmanaged WAN input'));
        } else {
            $commands[] = '# Strict mode can additionally restrict API/Winbox/SSH/WWW to FASTNETPAY management IPs.';
        }

        return $commands;
    }

    private static function buildWarnings($router, $settings, $payment)
    {
        $warnings = [];
        if (!$router && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $warnings[] = 'Previewing a draft router. Automatic apply requires a saved router record so FASTNETPAY can log the run.';
        }
        if (!$payment['ready']) {
            $warnings[] = 'MPESA STK Push is not fully ready: missing ' . implode(', ', $payment['missing']) . '. Users will not get automatic payment activation until this is fixed.';
        }
        if (trim((string) ($settings['api_password'] ?? '')) === '') {
            $warnings[] = 'FASTNETPAY API user password is empty. The wizard can still test with saved PHPNuxBill router credentials, but production provisioning should use fastnet-api-usr.';
        }
        if ($settings['security_level'] === 'strict') {
            $warnings[] = 'Strict ISP Mode can restrict management services. Confirm FASTNETPAY server IP and out-of-band router access before applying.';
        }
        $routerHost = self::cidrIp($settings['host'] ?? '');
        if ($routerHost !== '' && self::cidrIp($settings['lan_gateway']) === $routerHost && self::rosName($settings['management_interface'] ?? 'ether4') !== self::rosName($settings['hotspot_interface'] ?? 'fastnetpay-bridge')) {
            $warnings[] = 'Hotspot gateway matches the management/API router IP. FASTNETPAY will protect management by using a separate Hotspot subnet; keep ether4 connected for Winbox/API.';
        }
        if (trim($settings['wan_interface']) === '') {
            $warnings[] = 'WAN interface is empty. Preview uses ether1; confirm the correct upstream interface.';
        }
        if (trim($settings['fastnetpay_server_ip']) === '' || $settings['fastnetpay_server_ip'] === '127.0.0.1') {
            $warnings[] = 'FASTNETPAY server IP should be reachable from MikroTik. Do not use localhost for a production router.';
        }
        return $warnings;
    }

    private static function selectedPlans($plans, $settings)
    {
        $selected = $settings['plan_ids'];
        $profile = $settings['deployment_profile'];
        $filtered = [];
        foreach ($plans as $plan) {
            if (!empty($selected) && !in_array((int) $plan['id'], $selected, true)) {
                continue;
            }
            if ($profile === 'hotspot' && strcasecmp($plan['type'], 'Hotspot') !== 0) {
                continue;
            }
            if ($profile === 'pppoe' && strcasecmp($plan['type'], 'PPPOE') !== 0) {
                continue;
            }
            if ((string) ($plan['enabled'] ?? '1') === '0') {
                continue;
            }
            $filtered[] = $plan;
        }
        return $filtered;
    }

    private static function createRouterBackups($client, $runId, $routerId)
    {
        $base = 'before_fastnetpay_' . (int) $routerId . '_' . date('Ymd_His');
        $files = [];
        $errors = [];

        self::logStep($runId, 'Backup Before Provisioning', 'running', 'Creating RouterOS export and binary backup named ' . $base, '');

        try {
            $client->sendSync((new Request('/export'))->setArgument('file', $base));
            $files[] = $base . '.rsc';
        } catch (Throwable $e) {
            $errors[] = 'export: ' . self::redact($e->getMessage());
        }

        try {
            $client->sendSync((new Request('/system/backup/save'))->setArgument('name', $base));
            $files[] = $base . '.backup';
        } catch (Throwable $e) {
            $errors[] = 'system backup: ' . self::redact($e->getMessage());
        }

        if (!empty($files)) {
            $message = 'Backup saved on MikroTik Files: ' . implode(', ', $files);
            if (!empty($errors)) {
                $message .= '. Some backup methods failed: ' . implode(' | ', $errors);
                self::logStep($runId, 'Backup Before Provisioning', 'warning', $message, '');
            } else {
                self::logStep($runId, 'Backup Before Provisioning', 'success', $message, '');
            }
            return ['ok' => true, 'files' => $files, 'message' => $message, 'errors' => $errors];
        }

        $message = 'Automatic backup failed: ' . implode(' | ', $errors);
        self::logStep($runId, 'Backup Before Provisioning', 'failed', '', $message);

        return ['ok' => false, 'files' => [], 'message' => $message, 'errors' => $errors];
    }

    public static function reconcileCriticalProvisioningState($client, $settings, $plans = null, $router = null)
    {
        $settings = self::withDetectedServerIp($client, $settings);
        $plans = $plans === null ? self::selectedPlans(self::plans(), $settings) : $plans;
        $messages = [];
        $profile = $settings['deployment_profile'] ?? 'mixed';
        $expectsHotspot = in_array($profile, ['hotspot', 'mixed'], true);
        $expectsPppoe = in_array($profile, ['pppoe', 'mixed'], true);

        $wan = self::rosName($settings['wan_interface'] ?: 'ether1');
        $management = self::rosName($settings['management_interface'] ?: 'ether4');
        $bridge = self::bridgeNameFromSettings($settings) ?: 'fastnetpay-bridge';
        $hotspotInterface = self::rosName($settings['hotspot_interface'] ?: $settings['lan_interface'] ?: $bridge);
        $pppoeInterface = self::rosName($settings['pppoe_interface'] ?: $settings['lan_interface'] ?: $bridge);
        $gateway = self::cidrIp($settings['lan_gateway']);
        $network = self::networkFromCidr($settings['lan_gateway']);
        $dnsName = self::domain($settings['dns_name'], 'portal.fastnetpay.test');
        $lease = self::lease($settings['dhcp_lease_time']);

        self::apiUpsert($client, '/interface/bridge/print', '/interface/bridge/add', '/interface/bridge/set', 'name', $bridge, [
            'name' => $bridge,
            'protocol-mode' => 'none',
            'comment' => 'FASTNETPAY hotspot/LAN bridge',
        ], [
            'protocol-mode' => 'none',
            'comment' => 'FASTNETPAY hotspot/LAN bridge',
        ]);
        $messages[] = 'Bridge verified: ' . $bridge;

        self::reconcileBridgePorts($client, $bridge, $wan, $management);
        self::reconcileBridgeMac($client, $bridge, $wan, $management);
        $messages[] = 'Bridge ports verified: client ethernet and wireless interfaces mapped to ' . $bridge . ', management kept on ' . $management . '.';

        if ($expectsHotspot) {
            self::apiUpsert($client, '/ip/pool/print', '/ip/pool/add', '/ip/pool/set', 'name', 'fastnetpay-hotspot-pool', [
                'name' => 'fastnetpay-hotspot-pool',
                'ranges' => self::pool($settings['hotspot_pool'], '192.168.90.50-192.168.90.250'),
                'comment' => 'FASTNETPAY hotspot client pool',
            ], [
                'ranges' => self::pool($settings['hotspot_pool'], '192.168.90.50-192.168.90.250'),
                'comment' => 'FASTNETPAY hotspot client pool',
            ]);

            self::apiUpsertFlexible($client, '/ip/address/print', '/ip/address/add', '/ip/address/set', [
                ['comment', 'FASTNETPAY hotspot gateway'],
                ['address', $settings['lan_gateway']],
            ], [
                'address' => $settings['lan_gateway'],
                'interface' => $hotspotInterface,
                'comment' => 'FASTNETPAY hotspot gateway',
            ], [
                'address' => $settings['lan_gateway'],
                'interface' => $hotspotInterface,
                'comment' => 'FASTNETPAY hotspot gateway',
            ]);

            self::apiUpsert($client, '/ip/dhcp-server/print', '/ip/dhcp-server/add', '/ip/dhcp-server/set', 'name', 'fastnetpay-dhcp', [
                'name' => 'fastnetpay-dhcp',
                'interface' => $hotspotInterface,
                'address-pool' => 'fastnetpay-hotspot-pool',
                'lease-time' => $lease,
                'disabled' => 'no',
            ], [
                'interface' => $hotspotInterface,
                'address-pool' => 'fastnetpay-hotspot-pool',
                'lease-time' => $lease,
                'disabled' => 'no',
            ]);

            self::apiUpsertFlexible($client, '/ip/dhcp-server/network/print', '/ip/dhcp-server/network/add', '/ip/dhcp-server/network/set', [
                ['comment', 'FASTNETPAY DHCP network'],
                ['address', $network],
            ], [
                'address' => $network,
                'gateway' => $gateway,
                'dns-server' => $gateway,
                'comment' => 'FASTNETPAY DHCP network',
            ], [
                'address' => $network,
                'gateway' => $gateway,
                'dns-server' => $gateway,
                'comment' => 'FASTNETPAY DHCP network',
            ]);

            self::apiUpsert($client, '/ip/dns/static/print', '/ip/dns/static/add', '/ip/dns/static/set', 'name', $dnsName, [
                'name' => $dnsName,
                'address' => $gateway,
                'comment' => 'FASTNETPAY hotspot portal DNS',
            ], [
                'address' => $gateway,
                'comment' => 'FASTNETPAY hotspot portal DNS',
            ]);

            self::reconcileCaptiveDetection($client, $gateway, $network, $dnsName, $hotspotInterface);

            $profileId = self::apiUpsert($client, '/ip/hotspot/profile/print', '/ip/hotspot/profile/add', '/ip/hotspot/profile/set', 'name', 'fastnetpay-hotspot-profile', [
                'name' => 'fastnetpay-hotspot-profile',
                'hotspot-address' => $gateway,
                'dns-name' => $dnsName,
                'html-directory' => 'hotspot',
                'login-by' => 'http-chap,http-pap,cookie',
            ], [
                'hotspot-address' => $gateway,
                'dns-name' => $dnsName,
                'html-directory' => 'hotspot',
                'login-by' => 'http-chap,http-pap,cookie',
            ]);

            self::apiUpsert($client, '/ip/hotspot/print', '/ip/hotspot/add', '/ip/hotspot/set', 'name', 'fastnetpay-hotspot', [
                'name' => 'fastnetpay-hotspot',
                'interface' => $hotspotInterface,
                'address-pool' => 'fastnetpay-hotspot-pool',
                'profile' => 'fastnetpay-hotspot-profile',
                'disabled' => 'no',
            ], [
                'interface' => $hotspotInterface,
                'address-pool' => 'fastnetpay-hotspot-pool',
                'profile' => 'fastnetpay-hotspot-profile',
                'disabled' => 'no',
            ]);

            self::reconcileHotspotProfiles($client, $plans);
            self::reconcileWirelessSsid($client, 'FastNet Test');
            self::reconcilePortalAccess($client, $settings, $router);
            self::refreshHotspotClientLeases($client);
            $messages[] = 'Hotspot verified: profile, server, DHCP, DNS/captive detection, pool, package profiles, and SSID.';
        }

        if ($expectsPppoe) {
            self::apiUpsert($client, '/ip/pool/print', '/ip/pool/add', '/ip/pool/set', 'name', 'fastnetpay-pppoe-pool', [
                'name' => 'fastnetpay-pppoe-pool',
                'ranges' => self::pool($settings['pppoe_pool'], '100.64.10.10-100.64.10.250'),
                'comment' => 'FASTNETPAY PPPoE client pool',
            ], [
                'ranges' => self::pool($settings['pppoe_pool'], '100.64.10.10-100.64.10.250'),
                'comment' => 'FASTNETPAY PPPoE client pool',
            ]);

            $defaultProfile = self::reconcilePppoeProfiles($client, $plans, $gateway);
            self::apiUpsert($client, '/interface/pppoe-server/server/print', '/interface/pppoe-server/server/add', '/interface/pppoe-server/server/set', 'service-name', 'fastnetpay-pppoe', [
                'service-name' => 'fastnetpay-pppoe',
                'interface' => $pppoeInterface,
                'default-profile' => $defaultProfile,
                'authentication' => 'pap,chap',
                'disabled' => 'no',
            ], [
                'interface' => $pppoeInterface,
                'default-profile' => $defaultProfile,
                'authentication' => 'pap,chap',
                'disabled' => 'no',
            ]);
            $messages[] = 'PPPoE verified: pool, profiles, and server on ' . $pppoeInterface . '.';
        }

        return $messages;
    }

    private static function reconcileCaptiveDetection($client, $gateway, $network, $dnsName, $hotspotInterface)
    {
        $portalAliases = array_values(array_unique(array_filter([
            $dnsName,
            'portal.fastnetpay.test',
            'portal.fastnetpay.lan',
            'portal.fastnetpay.local',
        ])));

        foreach ($portalAliases as $portalName) {
            self::apiUpsert($client, '/ip/dns/static/print', '/ip/dns/static/add', '/ip/dns/static/set', 'name', $portalName, [
                'name' => $portalName,
                'address' => $gateway,
                'comment' => $portalName === $dnsName ? 'FASTNETPAY hotspot portal DNS' : 'FASTNETPAY hotspot portal DNS alias',
            ], [
                'address' => $gateway,
                'comment' => $portalName === $dnsName ? 'FASTNETPAY hotspot portal DNS' : 'FASTNETPAY hotspot portal DNS alias',
            ]);
        }

        $staticRows = self::safeRows($client, '/ip/dns/static/print');
        foreach ($staticRows as $row) {
            $name = self::prop($row, 'name');
            $comment = self::prop($row, 'comment');
            if (!in_array($name, $portalAliases, true) && (
                substr($name, -strlen('.fastnetpay.local')) === '.fastnetpay.local' ||
                in_array($comment, [
                    'FASTNETPAY hotspot portal DNS trigger',
                    'FASTNETPAY hotspot portal DNS',
                    'FASTNETPAY hotspot portal DNS alias',
                    'FASTNETPAY captive portal detection',
                ], true)
            )) {
                self::apiRemove($client, '/ip/dns/static/remove', self::prop($row, '.id'));
            }
        }

        foreach (self::safeRows($client, '/ip/firewall/nat/print') as $row) {
            if (self::prop($row, 'comment') === 'FASTNETPAY force hotspot HTTP login') {
                self::apiRemove($client, '/ip/firewall/nat/remove', self::prop($row, '.id'));
            }
        }

        $portalHttpRuleId = self::apiUpsert($client, '/ip/firewall/nat/print', '/ip/firewall/nat/add', '/ip/firewall/nat/set', 'comment', 'FASTNETPAY local portal HTTP to hotspot login', [
            'chain' => 'dstnat',
            'in-interface' => $hotspotInterface,
            'dst-address' => $gateway,
            'protocol' => 'tcp',
            'dst-port' => '80',
            'action' => 'redirect',
            'to-ports' => '64874',
            'place-before' => '0',
            'comment' => 'FASTNETPAY local portal HTTP to hotspot login',
        ], [
            'chain' => 'dstnat',
            'in-interface' => $hotspotInterface,
            'dst-address' => $gateway,
            'protocol' => 'tcp',
            'dst-port' => '80',
            'action' => 'redirect',
            'to-ports' => '64874',
        ]);
        self::apiMoveToTop($client, '/ip/firewall/nat/move', $portalHttpRuleId);

        foreach (self::safeRows($client, '/ip/firewall/filter/print') as $row) {
            if (in_array(self::prop($row, 'comment'), [
                'FASTNETPAY reject private DNS during captive login',
                'FASTNETPAY pre-hotspot reject TCP private DNS',
                'FASTNETPAY pre-hotspot reject UDP private DNS',
            ], true)) {
                self::apiRemove($client, '/ip/firewall/filter/remove', self::prop($row, '.id'));
            }
        }

        self::apiUpsert($client, '/ip/firewall/filter/print', '/ip/firewall/filter/add', '/ip/firewall/filter/set', 'comment', 'FASTNETPAY allow authenticated hotspot clients to WAN', [
            'chain' => 'forward',
            'in-interface' => $hotspotInterface,
            'out-interface-list' => 'WAN',
            'action' => 'accept',
            'comment' => 'FASTNETPAY allow authenticated hotspot clients to WAN',
        ], [
            'chain' => 'forward',
            'in-interface' => $hotspotInterface,
            'out-interface-list' => 'WAN',
            'action' => 'accept',
        ]);

        foreach (['udp', 'tcp'] as $protocol) {
            self::apiUpsert($client, '/ip/firewall/nat/print', '/ip/firewall/nat/add', '/ip/firewall/nat/set', 'comment', 'FASTNETPAY force hotspot ' . strtoupper($protocol) . ' DNS to router', [
                'chain' => 'dstnat',
                'in-interface' => $hotspotInterface,
                'protocol' => $protocol,
                'dst-port' => '53',
                'action' => 'redirect',
                'to-ports' => '53',
                'comment' => 'FASTNETPAY force hotspot ' . strtoupper($protocol) . ' DNS to router',
            ], [
                'chain' => 'dstnat',
                'in-interface' => $hotspotInterface,
                'protocol' => $protocol,
                'dst-port' => '53',
                'action' => 'redirect',
                'to-ports' => '53',
            ]);
        }

        foreach (['1.1.1.1', '1.0.0.1', '8.8.8.8', '8.8.4.4', '9.9.9.9', '149.112.112.112'] as $resolverIp) {
            self::apiUpsert($client, '/ip/hotspot/walled-garden/ip/print', '/ip/hotspot/walled-garden/ip/add', '/ip/hotspot/walled-garden/ip/set', 'comment', 'FASTNETPAY captive DNS over TLS ' . $resolverIp, [
                'dst-address' => $resolverIp,
                'protocol' => 'tcp',
                'dst-port' => '853',
                'action' => 'accept',
                'comment' => 'FASTNETPAY captive DNS over TLS ' . $resolverIp,
            ], [
                'dst-address' => $resolverIp,
                'protocol' => 'tcp',
                'dst-port' => '853',
                'action' => 'accept',
            ]);
        }

        try {
            self::apiUpsert($client, '/ip/dhcp-server/option/print', '/ip/dhcp-server/option/add', '/ip/dhcp-server/option/set', 'name', 'fastnetpay-captive-portal', [
                'name' => 'fastnetpay-captive-portal',
                'code' => '114',
                'value' => "'http://" . $dnsName . "/capport.json'",
            ], [
                'code' => '114',
                'value' => "'http://" . $dnsName . "/capport.json'",
            ]);
            self::apiUpsertFlexible($client, '/ip/dhcp-server/network/print', '/ip/dhcp-server/network/add', '/ip/dhcp-server/network/set', [
                ['comment', 'FASTNETPAY DHCP network'],
                ['address', $network],
            ], [
                'address' => $network,
                'gateway' => $gateway,
                'dns-server' => $gateway,
                'dhcp-option' => 'fastnetpay-captive-portal',
                'comment' => 'FASTNETPAY DHCP network',
            ], [
                'dhcp-option' => 'fastnetpay-captive-portal',
            ]);
        } catch (Throwable $ignored) {
        }
    }

    private static function reconcilePortalAccess($client, $settings, $router = null)
    {
        $serverIp = self::ip($settings['fastnetpay_server_ip'] ?? '', '');
        if ($serverIp === '') {
            return;
        }

        foreach (self::safeRows($client, '/ip/hotspot/walled-garden/ip/print') as $row) {
            $comment = self::prop($row, 'comment');
            $dst = self::prop($row, 'dst-address');
            if ($comment === 'FASTNETPAY portal server IP' && $dst !== '' && $dst !== $serverIp) {
                self::apiRemove($client, '/ip/hotspot/walled-garden/ip/remove', self::prop($row, '.id'));
            }
        }

        foreach (self::safeRows($client, '/ip/hotspot/walled-garden/print') as $row) {
            $comment = self::prop($row, 'comment');
            if (preg_match('/^FASTNETPAY walled garden ([0-9.]+)$/', $comment, $match) && filter_var($match[1], FILTER_VALIDATE_IP)) {
                self::apiRemove($client, '/ip/hotspot/walled-garden/remove', self::prop($row, '.id'));
            }
            if (in_array($comment, ['FASTNETPAY walled garden localhost', 'FASTNETPAY walled garden 127.0.0.1'], true)) {
                self::apiRemove($client, '/ip/hotspot/walled-garden/remove', self::prop($row, '.id'));
            }
        }

        self::apiUpsert($client, '/ip/hotspot/walled-garden/ip/print', '/ip/hotspot/walled-garden/ip/add', '/ip/hotspot/walled-garden/ip/set', 'comment', 'FASTNETPAY portal server IP', [
            'dst-address' => $serverIp,
            'action' => 'accept',
            'comment' => 'FASTNETPAY portal server IP',
        ], [
            'dst-address' => $serverIp,
            'action' => 'accept',
        ]);

        foreach (self::walledGardenDomains($settings, self::mpesaReadiness()) as $domain) {
            self::apiUpsert($client, '/ip/hotspot/walled-garden/print', '/ip/hotspot/walled-garden/add', '/ip/hotspot/walled-garden/set', 'comment', 'FASTNETPAY walled garden ' . $domain, [
                'dst-host' => $domain,
                'action' => 'allow',
                'comment' => 'FASTNETPAY walled garden ' . $domain,
            ], [
                'dst-host' => $domain,
                'action' => 'allow',
            ]);
        }

        if ($router && (int) ($router['id'] ?? 0) > 0) {
            self::uploadPortalFiles($client, $router, $settings);
        }

        foreach (self::safeRows($client, '/ip/hotspot/host/print') as $row) {
            if (self::prop($row, 'authorized', 'false') === 'false') {
                self::apiRemove($client, '/ip/hotspot/host/remove', self::prop($row, '.id'));
            }
        }
    }

    private static function refreshHotspotClientLeases($client)
    {
        foreach (self::safeRows($client, '/ip/dhcp-server/lease/print') as $row) {
            if (self::prop($row, 'active-server') === 'fastnetpay-dhcp' || self::prop($row, 'server') === 'fastnetpay-dhcp') {
                self::apiRemove($client, '/ip/dhcp-server/lease/remove', self::prop($row, '.id'));
            }
        }
    }

    private static function uploadPortalFiles($client, $router, $settings)
    {
        $routerId = (int) ($router['id'] ?? 0);
        if ($routerId <= 0) {
            return;
        }

        $token = self::portalTokenForRouter($routerId);
        $base = self::hotspotApiBaseUrl($settings);
        $gateway = self::cidrIp($settings['lan_gateway'] ?? '192.168.90.1/24') ?: '192.168.90.1';
        $portal = self::domain($settings['dns_name'] ?? 'portal.fastnetpay.test', 'portal.fastnetpay.test');
        foreach (self::portalFileNames() as $file) {
            $url = $base . '/?_route=api/hotspot/portal-file&router=' . $routerId . '&token=' . rawurlencode($token) . '&base=' . rawurlencode($base) . '&gateway=' . rawurlencode($gateway) . '&portal=' . rawurlencode($portal) . '&file=' . rawurlencode($file);
            $request = (new RouterOS\Request('/tool/fetch'))
                ->setArgument('url', $url)
                ->setArgument('dst-path', 'hotspot/' . $file)
                ->setArgument('keep-result', 'yes');
            self::throwOnTrap($client->sendSync($request), '/tool/fetch ' . $file);
        }
    }

    private static function portalFileNames()
    {
        return [
            'index',
            'index.html',
            'login',
            'login.html',
            'rlogin.html',
            'redirect.html',
            'status.html',
            'logout.html',
            'alogin.html',
            'error.html',
            'radvert.html',
            'capport.json',
            'md5.js',
            'fastnetpay-hotspot.css',
            'fastnetpay-hotspot.js',
        ];
    }

    private static function reconcileBridgePorts($client, $bridge, $wan, $management)
    {
        $interfaces = [];
        foreach (self::safeRows($client, '/interface/ethernet/print') as $row) {
            $name = self::prop($row, 'name');
            if ($name !== '' && $name !== $wan && $name !== $management) {
                $interfaces[] = $name;
            }
        }
        foreach (['/interface/wireless/print', '/interface/wifi/print'] as $path) {
            foreach (self::safeRows($client, $path) as $row) {
                $name = self::prop($row, 'name');
                if ($name !== '' && $name !== $wan && $name !== $management) {
                    $interfaces[] = $name;
                }
            }
        }

        foreach (array_values(array_unique($interfaces)) as $interface) {
            self::apiUpsert($client, '/interface/bridge/port/print', '/interface/bridge/port/add', '/interface/bridge/port/set', 'interface', $interface, [
                'bridge' => $bridge,
                'interface' => $interface,
                'comment' => 'FASTNETPAY hotspot/PPPoE bridge port',
            ], [
                'bridge' => $bridge,
                'comment' => 'FASTNETPAY hotspot/PPPoE bridge port',
            ]);
        }

        foreach (self::rowsBy(self::safeRows($client, '/interface/bridge/port/print'), 'interface', $management) as $row) {
            if (self::prop($row, 'bridge') === $bridge) {
                self::apiRemove($client, '/interface/bridge/port/remove', self::prop($row, '.id'));
            }
        }
    }

    private static function reconcileBridgeMac($client, $bridge, $wan, $management)
    {
        $mac = '';
        foreach (['/interface/wireless/print', '/interface/wifi/print', '/interface/ethernet/print'] as $path) {
            foreach (self::safeRows($client, $path) as $row) {
                $name = self::prop($row, 'name');
                $candidate = self::prop($row, 'mac-address');
                if ($candidate !== '' && $name !== '' && $name !== $wan && $name !== $management) {
                    $mac = $candidate;
                    break 2;
                }
            }
        }

        if ($mac === '') {
            return;
        }

        $row = self::findRowBy(self::safeRows($client, '/interface/bridge/print'), 'name', $bridge);
        if (!$row) {
            return;
        }

        try {
            self::apiSet($client, '/interface/bridge/set', self::prop($row, '.id'), [
                'auto-mac' => 'no',
                'admin-mac' => $mac,
            ]);
        } catch (Throwable $ignored) {
        }
    }

    private static function reconcileHotspotProfiles($client, $plans)
    {
        $count = 0;
        foreach ($plans as $plan) {
            if (strcasecmp($plan['type'], 'Hotspot') !== 0) {
                continue;
            }
            $count++;
            $profile = self::profileName($plan);
            $args = [
                'shared-users' => (string) max(1, (int) ($plan['shared_users'] ?: 1)),
                'rate-limit' => self::planRate($plan),
            ];
            self::apiUpsert($client, '/ip/hotspot/user/profile/print', '/ip/hotspot/user/profile/add', '/ip/hotspot/user/profile/set', 'name', $profile, [
                'name' => $profile,
                'shared-users' => $args['shared-users'],
                'rate-limit' => $args['rate-limit'],
            ], $args);
        }

        if ($count === 0) {
            self::apiUpsert($client, '/ip/hotspot/user/profile/print', '/ip/hotspot/user/profile/add', '/ip/hotspot/user/profile/set', 'name', 'FNP-Sample-Hotspot', [
                'name' => 'FNP-Sample-Hotspot',
                'shared-users' => '1',
                'rate-limit' => '2M/2M',
            ], [
                'shared-users' => '1',
                'rate-limit' => '2M/2M',
            ]);
        }
    }

    private static function reconcilePppoeProfiles($client, $plans, $gateway)
    {
        $defaultProfile = 'FNP-PPPoE-Default';
        $count = 0;
        foreach ($plans as $plan) {
            if (strcasecmp($plan['type'], 'PPPOE') !== 0) {
                continue;
            }
            $count++;
            $profile = self::profileName($plan);
            if ($defaultProfile === 'FNP-PPPoE-Default') {
                $defaultProfile = $profile;
            }
            $pool = trim((string) ($plan['pool'] ?? '')) ?: 'fastnetpay-pppoe-pool';
            $args = [
                'local-address' => $gateway,
                'remote-address' => $pool,
                'rate-limit' => self::planRate($plan),
                'only-one' => 'yes',
            ];
            self::apiUpsert($client, '/ppp/profile/print', '/ppp/profile/add', '/ppp/profile/set', 'name', $profile, [
                'name' => $profile,
                'local-address' => $args['local-address'],
                'remote-address' => $args['remote-address'],
                'rate-limit' => $args['rate-limit'],
                'only-one' => 'yes',
                'comment' => 'FASTNETPAY PPPoE plan ' . $plan['name_plan'],
            ], $args);
        }

        if ($count === 0) {
            self::apiUpsert($client, '/ppp/profile/print', '/ppp/profile/add', '/ppp/profile/set', 'name', $defaultProfile, [
                'name' => $defaultProfile,
                'local-address' => $gateway,
                'remote-address' => 'fastnetpay-pppoe-pool',
                'rate-limit' => '5M/5M',
                'only-one' => 'yes',
                'comment' => 'FASTNETPAY sample PPPoE profile',
            ], [
                'local-address' => $gateway,
                'remote-address' => 'fastnetpay-pppoe-pool',
                'rate-limit' => '5M/5M',
                'only-one' => 'yes',
            ]);
        }

        return $defaultProfile;
    }

    private static function reconcileWirelessSsid($client, $ssid)
    {
        foreach (self::safeRows($client, '/interface/wireless/print') as $row) {
            $id = self::prop($row, '.id');
            if ($id !== '') {
                self::apiSet($client, '/interface/wireless/set', $id, [
                    'mode' => 'ap-bridge',
                    'ssid' => $ssid,
                    'disabled' => 'no',
                ]);
            }
        }

        foreach (self::safeRows($client, '/interface/wifi/print') as $row) {
            $id = self::prop($row, '.id');
            if ($id !== '') {
                try {
                    self::apiSet($client, '/interface/wifi/set', $id, [
                        'configuration.ssid' => $ssid,
                        'disabled' => 'no',
                    ]);
                } catch (Throwable $ignored) {
                }
            }
        }
    }

    private static function apiUpsert($client, $printPath, $addPath, $setPath, $property, $value, $addArgs, $setArgs)
    {
        $row = self::findRowBy(self::safeRows($client, $printPath), $property, $value);
        if ($row) {
            self::apiSet($client, $setPath, self::prop($row, '.id'), $setArgs);
            return self::prop($row, '.id');
        }
        return self::apiAdd($client, $addPath, $addArgs);
    }

    private static function apiUpsertFlexible($client, $printPath, $addPath, $setPath, $finders, $addArgs, $setArgs)
    {
        $rows = self::safeRows($client, $printPath);
        $row = null;
        foreach ($finders as $finder) {
            $row = self::findRowBy($rows, $finder[0], $finder[1]);
            if ($row) {
                break;
            }
        }
        if ($row) {
            self::apiSet($client, $setPath, self::prop($row, '.id'), $setArgs);
            return self::prop($row, '.id');
        }
        return self::apiAdd($client, $addPath, $addArgs);
    }

    private static function apiAdd($client, $path, $args)
    {
        $request = new RouterOS\Request($path);
        foreach ($args as $key => $value) {
            if ($value !== null && $value !== '') {
                $request->setArgument($key, (string) $value);
            }
        }
        $response = $client->sendSync($request);
        self::throwOnTrap($response, $path);
        return self::responseRet($response);
    }

    private static function apiSet($client, $path, $id, $args)
    {
        if ($id === '') {
            throw new Exception('RouterOS set failed because target id is empty for ' . $path);
        }
        $request = (new RouterOS\Request($path))->setArgument('numbers', $id);
        foreach ($args as $key => $value) {
            if ($value !== null && $value !== '') {
                $request->setArgument($key, (string) $value);
            }
        }
        self::throwOnTrap($client->sendSync($request), $path);
    }

    private static function apiSetAllowEmpty($client, $path, $id, $args)
    {
        if ($id === '') {
            throw new Exception('RouterOS set failed because target id is empty for ' . $path);
        }
        $request = (new RouterOS\Request($path))->setArgument('numbers', $id);
        foreach ($args as $key => $value) {
            if ($value !== null) {
                $request->setArgument($key, (string) $value);
            }
        }
        self::throwOnTrap($client->sendSync($request), $path);
    }

    private static function apiRemove($client, $path, $id)
    {
        if ($id === '') {
            return;
        }
        self::throwOnTrap($client->sendSync((new RouterOS\Request($path))->setArgument('numbers', $id)), $path);
    }

    private static function apiMoveToTop($client, $path, $id)
    {
        if ($id === '') {
            return;
        }
        try {
            self::throwOnTrap(
                $client->sendSync(
                    (new RouterOS\Request($path))
                        ->setArgument('numbers', $id)
                        ->setArgument('destination', '0')
                ),
                $path
            );
        } catch (Throwable $ignored) {
        }
    }

    private static function throwOnTrap($response, $path)
    {
        foreach ($response as $row) {
            if (method_exists($row, 'getType') && $row->getType() === RouterOS\Response::TYPE_ERROR) {
                throw new Exception($path . ': ' . self::prop($row, 'message', 'RouterOS returned an error'));
            }
        }
    }

    private static function responseRet($response)
    {
        foreach ($response as $row) {
            if (!method_exists($row, 'getProperty')) {
                continue;
            }
            $ret = $row->getProperty('ret');
            if ($ret !== null && $ret !== '') {
                return $ret;
            }
        }
        return '';
    }

    private static function applySection($client, $runId, $index, $section)
    {
        foreach ($section['commands'] as $line => $command) {
            $command = trim((string) $command);
            if ($command === '' || strpos($command, '#') === 0) {
                continue;
            }

            $scriptName = 'fnp_' . (int) $runId . '_' . (int) $index . '_' . ((int) $line + 1);
            $stepName = $section['name'] . ' command ' . ((int) $line + 1);
            self::logStep($runId, $stepName, 'running', $command, '');

            try {
                self::runScriptSource($client, $scriptName, $command, 'FASTNETPAY provisioning run ' . (int) $runId . ' - ' . $section['name']);
                self::logStep($runId, $stepName, 'success', $command, '');
            } catch (Throwable $e) {
                $message = 'Failed command #' . ((int) $line + 1) . ' in ' . $section['name'] . ': ' . self::redact($command) . ' | RouterOS error: ' . self::redact($e->getMessage());
                self::logStep($runId, $stepName, 'failed', $command, $message);
                throw new Exception($message);
            }
        }
    }

    private static function runScriptSource($client, $name, $source, $comment)
    {
        $client->sendSync(
            (new Request('/system/script/add'))
                ->setArgument('name', $name)
                ->setArgument('source', $source)
                ->setArgument('comment', $comment)
        );

        try {
            $client->sendSync((new Request('/system/script/run'))->setArgument('number', $name));
            try {
                $client->sendSync((new Request('/system/script/remove'))->setArgument('numbers', $name));
            } catch (Throwable $ignored) {
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }

    private static function logStep($runId, $name, $status, $output = '', $error = '')
    {
        $row = null;
        if (in_array($status, ['success', 'failed', 'warning'], true)) {
            $row = ORM::for_table('router_provisioning_steps')
                ->where('run_id', (int) $runId)
                ->where('step_name', $name)
                ->where('status', 'running')
                ->order_by_desc('id')
                ->find_one();
        }
        if (!$row) {
            $row = ORM::for_table('router_provisioning_steps')->create();
            $row->run_id = (int) $runId;
            $row->step_name = $name;
            $row->started_at = date('Y-m-d H:i:s');
        }
        $row->status = $status;
        $row->output = self::redact($output);
        $row->error_message = self::redact($error);
        $row->completed_at = in_array($status, ['success', 'failed', 'warning'], true) ? date('Y-m-d H:i:s') : null;
        $row->save();
    }

    private static function portMapping($routerId)
    {
        if ((int) $routerId <= 0) {
            return null;
        }
        return ORM::for_table('router_port_mappings')->where('router_id', (int) $routerId)->find_one();
    }

    private static function savePortMapping($routerId, $settings)
    {
        if ((int) $routerId <= 0) {
            return;
        }
        $row = self::portMapping($routerId);
        if (!$row) {
            $row = ORM::for_table('router_port_mappings')->create();
            $row->router_id = (int) $routerId;
        }
        $row->wan_interface = $settings['wan_interface'];
        $row->lan_interface = $settings['lan_interface'];
        $row->hotspot_interface = $settings['hotspot_interface'];
        $row->pppoe_interface = $settings['pppoe_interface'];
        $row->management_interface = $settings['management_interface'];
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
    }

    private static function clientFromSettings($settings, $router = null)
    {
        $params = self::connectionParams($settings, $router);
        $username = $settings['api_username'] ?: (($router && $router['username']) ? $router['username'] : self::API_USERNAME);
        $password = $settings['api_password'] ?: (($router && $router['username'] === $username) ? $router['password'] : '');
        return self::clientForCredentials($params, $username, $password);
    }

    private static function nuxbillClientFromRouter($router)
    {
        if (!$router || trim((string) $router['ip_address']) === '' || trim((string) $router['username']) === '') {
            throw new Exception('Saved PHPNuxBill router record is missing IP address or username.');
        }
        try {
            $params = self::paramsFromIpAddress($router['ip_address'], 8728, false);
            return self::clientForCredentials($params, $router['username'], $router['password']);
        } catch (Throwable $e) {
            throw new Exception('Router record ' . $router['ip_address'] . ' as ' . $router['username'] . ' failed: ' . self::redact($e->getMessage()), 0, $e);
        }
    }

    private static function connectionParams($settings, $router = null)
    {
        $hostValue = trim((string) ($settings['host'] ?? ''));
        if ($hostValue === '' && $router) {
            $hostValue = (string) $router['ip_address'];
        }
        $preferSsl = ($settings['prefer_ssl'] ?? 'no') === 'yes';
        $defaultPort = $preferSsl ? ($settings['api_ssl_port'] ?? 8729) : ($settings['api_port'] ?? 8728);
        $parsed = self::parseHostPort($hostValue, $defaultPort);

        return [
            'host' => $parsed['host'],
            'port' => (int) $parsed['port'],
            'ssl' => $preferSsl,
        ];
    }

    private static function paramsFromIpAddress($ipAddress, $defaultPort = 8728, $ssl = false)
    {
        $parsed = self::parseHostPort($ipAddress, $defaultPort);
        return [
            'host' => $parsed['host'],
            'port' => (int) $parsed['port'],
            'ssl' => (bool) $ssl,
        ];
    }

    private static function clientForCredentials($params, $username, $password)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            throw new Exception('Router API is disabled in demo mode.');
        }
        $host = trim((string) ($params['host'] ?? ''));
        $port = (int) ($params['port'] ?? 8728);
        $usingSsl = !empty($params['ssl']);
        $username = trim((string) $username);
        $password = (string) $password;
        if ($host === '' || $username === '') {
            throw new Exception('Router host and username are required.');
        }
        $crypto = $usingSsl ? NetworkStream::CRYPTO_TLS : NetworkStream::CRYPTO_OFF;
        try {
            return new Client($host, $username, $password, $port, false, 6, $crypto);
        } catch (Throwable $e) {
            throw new Exception(self::routerApiErrorMessage($host, $port, $username, $password, $usingSsl, $e), 0, $e);
        }
    }

    private static function rows($client, $path)
    {
        $response = $client->sendSync(new RouterOS\Request($path));
        $rows = [];
        foreach ($response as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    private static function safeRows($client, $path)
    {
        try {
            return self::rows($client, $path);
        } catch (Throwable $e) {
            return [];
        }
    }

    private static function firstRow($client, $path)
    {
        $rows = self::rows($client, $path);
        return $rows[0] ?? null;
    }

    private static function prop($row, $property, $default = '')
    {
        if (!$row || !method_exists($row, 'getProperty')) {
            return $default;
        }
        $value = $row->getProperty($property);
        return $value === null || $value === '' ? $default : $value;
    }

    private static function routerOsMajor($version)
    {
        if (preg_match('/^(\d+)/', (string) $version, $match)) {
            return (int) $match[1];
        }
        return 0;
    }

    private static function routerApiErrorMessage($host, $port, $username, $password, $usingSsl, Throwable $error)
    {
        $base = 'RouterOS API connection failed for ' . $host . ':' . (int) $port . '. ' . self::redact($error->getMessage());
        if ($usingSsl) {
            return $base . ' API-SSL often needs a valid router certificate. Try plain API on port 8728 for local testing, then enable API-SSL for production.';
        }

        $probe = self::probePlainApi($host, (int) $port, $username, $password);
        if (!$probe['tcp']) {
            return $base . ' TCP probe failed: ' . $probe['message'] . '. In Winbox, enable /ip service api and confirm firewall input allows FASTNETPAY.';
        }
        if (!$probe['answered']) {
            return $base . ' TCP port is open, but RouterOS did not answer the API /login request. Check /ip service api disabled/address, firewall input rules, and whether RouterOS refuses API login while the admin password is empty after reset.';
        }
        if ($probe['trap'] !== '') {
            return $base . ' RouterOS API answered with: ' . $probe['trap'];
        }

        return $base . ' RouterOS answered the raw API probe, so this may be an API client compatibility problem. Try disabling API-SSL, confirm credentials, or set a temporary admin password.';
    }

    private static function probePlainApi($host, $port, $username, $password)
    {
        $fp = @stream_socket_client('tcp://' . $host . ':' . (int) $port, $errno, $errstr, 4);
        if (!$fp) {
            return ['tcp' => false, 'answered' => false, 'trap' => '', 'message' => trim($errstr ?: ('error ' . $errno))];
        }

        stream_set_timeout($fp, 4);
        foreach (['/login', '=name=' . $username, '=password=' . $password, ''] as $word) {
            fwrite($fp, self::rosLength(strlen($word)) . $word);
        }

        $words = [];
        for ($i = 0; $i < 20; $i++) {
            $word = self::readRosWord($fp);
            if ($word === null) {
                fclose($fp);
                return ['tcp' => true, 'answered' => false, 'trap' => '', 'message' => 'no API response'];
            }
            if ($word === '') {
                break;
            }
            $words[] = $word;
        }
        fclose($fp);

        $trap = '';
        foreach ($words as $word) {
            if (strpos($word, '=message=') === 0) {
                $trap = substr($word, 9);
                break;
            }
        }

        return ['tcp' => true, 'answered' => !empty($words), 'trap' => $trap, 'message' => implode(' ', $words)];
    }

    private static function rosLength($length)
    {
        $length = (int) $length;
        if ($length < 0x80) {
            return chr($length);
        }
        if ($length < 0x4000) {
            return chr(($length >> 8) | 0x80) . chr($length & 0xff);
        }
        if ($length < 0x200000) {
            return chr(($length >> 16) | 0xC0) . chr(($length >> 8) & 0xff) . chr($length & 0xff);
        }
        if ($length < 0x10000000) {
            return chr(($length >> 24) | 0xE0) . chr(($length >> 16) & 0xff) . chr(($length >> 8) & 0xff) . chr($length & 0xff);
        }
        return chr(0xF0) . chr(($length >> 24) & 0xff) . chr(($length >> 16) & 0xff) . chr(($length >> 8) & 0xff) . chr($length & 0xff);
    }

    private static function readRosWord($fp)
    {
        $length = self::readRosLength($fp);
        if ($length === null) {
            return null;
        }
        if ($length === 0) {
            return '';
        }
        $data = '';
        while (strlen($data) < $length && !feof($fp)) {
            $chunk = fread($fp, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $data .= $chunk;
        }
        return strlen($data) === $length ? $data : null;
    }

    private static function readRosLength($fp)
    {
        $byte = fread($fp, 1);
        if ($byte === false || $byte === '') {
            return null;
        }
        $c = ord($byte);
        if (($c & 0x80) === 0) {
            return $c;
        }
        if (($c & 0xC0) === 0x80) {
            return (($c & ~0xC0) << 8) + ord(fread($fp, 1));
        }
        if (($c & 0xE0) === 0xC0) {
            return (($c & ~0xE0) << 16) + (ord(fread($fp, 1)) << 8) + ord(fread($fp, 1));
        }
        if (($c & 0xF0) === 0xE0) {
            return (($c & ~0xF0) << 24) + (ord(fread($fp, 1)) << 16) + (ord(fread($fp, 1)) << 8) + ord(fread($fp, 1));
        }
        return (ord(fread($fp, 1)) << 24) + (ord(fread($fp, 1)) << 16) + (ord(fread($fp, 1)) << 8) + ord(fread($fp, 1));
    }

    private static function parseHostPort($value, $defaultPort = 8728)
    {
        $value = trim((string) $value);
        if (preg_match('#^https?://#i', $value)) {
            $parts = parse_url($value);
            return [
                'host' => $parts['host'] ?? '',
                'port' => isset($parts['port']) ? (int) $parts['port'] : self::port($defaultPort, 8728),
            ];
        }
        if (preg_match('/^(.+):([0-9]{2,5})$/', $value, $match) && substr_count($value, ':') === 1) {
            return ['host' => trim($match[1]), 'port' => self::port($match[2], self::port($defaultPort, 8728))];
        }
        return ['host' => $value, 'port' => self::port($defaultPort, 8728)];
    }

    private static function cfg($key, $default = '')
    {
        global $config;
        if (isset($config[$key])) {
            return $config[$key];
        }
        return $default;
    }

    private static function randomSecret()
    {
        try {
            return bin2hex(random_bytes(12));
        } catch (Exception $e) {
            return substr(sha1(uniqid('fnp', true)), 0, 24);
        }
    }

    private static function defaultServerAddress()
    {
        $host = parse_url(APP_URL, PHP_URL_HOST);
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }
        return '192.168.88.10';
    }

    private static function withDetectedServerIp($client, $settings)
    {
        $settings = (array) $settings;
        $detected = self::detectServerIpFromApiConnection($client);
        if ($detected === '') {
            return $settings;
        }

        $current = self::ip($settings['fastnetpay_server_ip'] ?? '', '');
        if ($current === '' || in_array($current, ['127.0.0.1', '0.0.0.0', '192.168.88.10'], true)) {
            $settings['fastnetpay_server_ip'] = $detected;
        }

        return $settings;
    }

    private static function detectServerIpFromApiConnection($client)
    {
        $candidates = [];
        foreach (self::safeRows($client, '/ip/firewall/connection/print') as $row) {
            if (self::prop($row, 'protocol') !== 'tcp') {
                continue;
            }
            $dst = self::parseRosIpPort(self::prop($row, 'dst-address'));
            $src = self::parseRosIpPort(self::prop($row, 'src-address'));
            if (!$dst || !$src || !in_array((int) $dst['port'], [8728, 8729], true)) {
                continue;
            }
            if (!filter_var($src['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }
            if (strpos($src['ip'], '192.168.90.') === 0 || $src['ip'] === '127.0.0.1') {
                continue;
            }
            $state = self::prop($row, 'tcp-state');
            $score = $state === 'established' ? 10 : 1;
            $candidates[$src['ip']] = max($candidates[$src['ip']] ?? 0, $score);
        }

        if (empty($candidates)) {
            return '';
        }
        arsort($candidates);
        return (string) array_key_first($candidates);
    }

    private static function parseRosIpPort($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^([0-9.]+):([0-9]{1,5})$/', $value, $match)) {
            return null;
        }
        return ['ip' => $match[1], 'port' => (int) $match[2]];
    }

    private static function routerName($router, $settings)
    {
        $name = $settings['router_name'] ?: ($router ? $router['name'] : 'FASTNETPAY-Router');
        return substr(preg_replace('/[^A-Za-z0-9_.-]+/', '-', $name), 0, 60);
    }

    private static function profileName($plan)
    {
        return substr('FNP-' . preg_replace('/[^A-Za-z0-9_.-]+/', '-', $plan['name_plan']), 0, 55);
    }

    private static function planRate($plan)
    {
        $up = trim((string) ($plan['rate_up'] ?? ''));
        $down = trim((string) ($plan['rate_down'] ?? ''));
        if ($up === '' || $down === '') {
            return '2M/2M';
        }
        $upUnit = self::rosRateUnit($plan['rate_up_unit'] ?? 'Mbps');
        $downUnit = self::rosRateUnit($plan['rate_down_unit'] ?? 'Mbps');
        $rate = $up . $upUnit . '/' . $down . $downUnit;
        if (!empty($plan['burst'])) {
            $rate .= ' ' . trim($plan['burst']);
        }
        return $rate;
    }

    private static function rosRateUnit($unit)
    {
        return stripos((string) $unit, 'K') !== false ? 'K' : 'M';
    }

    private static function walledGardenDomains($settings, $payment)
    {
        $domains = [
            parse_url(APP_URL, PHP_URL_HOST),
            parse_url(self::hotspotApiBaseUrl($settings), PHP_URL_HOST),
            'api.safaricom.co.ke',
            'sandbox.safaricom.co.ke',
            '*.safaricom.co.ke',
            '*.daraja.safaricom.co.ke',
            'wa.me',
            '*.whatsapp.com',
        ];
        if (!empty($payment['callback_url'])) {
            $domains[] = parse_url($payment['callback_url'], PHP_URL_HOST);
        }
        foreach (preg_split('/[\r\n,]+/', (string) $settings['custom_walled_garden']) as $domain) {
            $domain = trim($domain);
            if ($domain !== '') {
                $domains[] = $domain;
            }
        }
        $clean = [];
        foreach ($domains as $domain) {
            $domain = strtolower(trim((string) $domain));
            if ($domain === '' || in_array($domain, ['localhost', '127.0.0.1'], true) || filter_var($domain, FILTER_VALIDATE_IP)) {
                continue;
            }
            if (preg_match('/^[a-z0-9*.-]+$/', $domain)) {
                $clean[] = $domain;
            }
        }
        return array_values(array_unique($clean));
    }

    public static function portalTokenForRouter($routerId)
    {
        self::installSchema();
        $routerId = (int) $routerId;
        if ($routerId <= 0) {
            return '';
        }

        $row = ORM::for_table('router_portal_tokens')->where('router_id', $routerId)->find_one();
        if ($row && !empty($row['token_hash'])) {
            $savedToken = self::cfg('portal_token_' . $routerId, '');
            if ($savedToken !== '' && hash_equals((string) $row['token_hash'], hash('sha256', $savedToken))) {
                return $savedToken;
            }
        }

        $token = self::randomSecret() . self::randomSecret();
        if (!$row) {
            $row = ORM::for_table('router_portal_tokens')->create();
            $row->router_id = $routerId;
            $row->created_at = date('Y-m-d H:i:s');
        }
        $row->token_hash = hash('sha256', $token);
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        self::saveSetting('portal_token_' . $routerId, $token);

        return $token;
    }

    public static function validatePortalToken($routerId, $token)
    {
        self::installSchema();
        $routerId = (int) $routerId;
        $token = trim((string) $token);
        if ($routerId <= 0 || $token === '') {
            return false;
        }
        $row = ORM::for_table('router_portal_tokens')->where('router_id', $routerId)->find_one();
        return $row && hash_equals((string) $row['token_hash'], hash('sha256', $token));
    }

    public static function hotspotApiBaseUrl($settings = [])
    {
        $parts = parse_url(APP_URL);
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? 'localhost';
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        $serverIp = self::ip($settings['fastnetpay_server_ip'] ?? '', '');

        if ($serverIp !== '' && in_array($host, ['localhost', '127.0.0.1'], true)) {
            $host = $serverIp;
            if ($port === '' && $scheme === 'http') {
                $externalPort = getenv('FNP_EXTERNAL_HTTP_PORT') ?: getenv('APP_PORT') ?: '8088';
                $port = ':' . self::port($externalPort, 8088);
            }
        }

        return $scheme . '://' . $host . $port . $path;
    }

    public static function hotspotPortalFile($file, $router, $token, $base = '', $gateway = '192.168.90.1', $portal = 'portal.fastnetpay.test')
    {
        $file = trim(str_replace('\\', '/', (string) $file), '/');
        $routerId = (int) ($router['id'] ?? 0);
        $base = self::cleanBaseUrl($base);
        $apiBase = ($base !== '' ? $base : self::currentRequestBaseUrl()) . '/?_route=api/hotspot';
        $support = self::cfg('mpesastkpush_support_phone', '');
        $gateway = self::ip($gateway, '192.168.90.1');
        $portal = self::domain($portal, 'portal.fastnetpay.test');

        switch ($file) {
            case 'index':
            case 'index.html':
            case 'login':
            case 'login.html':
            case 'rlogin.html':
                return self::hotspotLoginHtml($apiBase, $routerId, $token, $support, $gateway, $portal);
            case 'redirect.html':
                return self::hotspotRedirectHtml($gateway, $portal);
            case 'status.html':
                return self::hotspotSimpleHtml('Connected', 'Your FASTNETPAY internet session is active.', $gateway, $portal);
            case 'logout.html':
                return self::hotspotSimpleHtml('Logged Out', 'You have logged out from FASTNETPAY WiFi.', $gateway, $portal);
            case 'alogin.html':
                return self::hotspotSimpleHtml('Authorising', 'FASTNETPAY is authorising your hotspot session. Please wait.', $gateway, $portal);
            case 'error.html':
                return self::hotspotSimpleHtml('Connection Error', 'FASTNETPAY could not authorise this session. Try again or contact support.', $gateway, $portal);
            case 'radvert.html':
                return self::hotspotSimpleHtml('FASTNETPAY WiFi', 'Your session is being prepared. Continue to your internet package page.', $gateway, $portal);
            case 'capport.json':
                return self::hotspotCapportJson($gateway, $portal);
            case 'md5.js':
                return "function hexMD5(s){return s;}\n";
            case 'fastnetpay-hotspot.css':
                return self::hotspotCss();
            case 'fastnetpay-hotspot.js':
                return self::hotspotJs();
        }

        return null;
    }

    private static function cleanBaseUrl($base)
    {
        $base = trim((string) $base);
        if ($base === '' || !preg_match('#^https?://#i', $base)) {
            return '';
        }
        $parts = parse_url($base);
        if (empty($parts['host'])) {
            return '';
        }
        $scheme = strtolower($parts['scheme'] ?? 'http') === 'https' ? 'https' : 'http';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        return $scheme . '://' . $host . $port . $path;
    }

    private static function currentRequestBaseUrl()
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $https ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host !== '') {
            return $scheme . '://' . $host;
        }
        return self::hotspotApiBaseUrl(self::defaultSettings());
    }

    public static function throttleHotspotAttempt($key, $limit = 6, $windowSeconds = 300)
    {
        self::installSchema();
        $key = substr(preg_replace('/[^A-Za-z0-9_.:-]+/', '-', (string) $key), 0, 180);
        $cutoff = date('Y-m-d H:i:s', time() - (int) $windowSeconds);
        ORM::raw_execute('DELETE FROM hotspot_api_attempts WHERE created_at < ?', [$cutoff]);
        $count = (int) ORM::for_table('hotspot_api_attempts')->where('attempt_key', $key)->count();
        if ($count >= (int) $limit) {
            return false;
        }
        $row = ORM::for_table('hotspot_api_attempts')->create();
        $row->attempt_key = $key;
        $row->created_at = date('Y-m-d H:i:s');
        $row->save();
        return true;
    }

    private static function saveSetting($setting, $value)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $setting)->find_one();
        if (!$row) {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $setting;
        }
        $row->value = $value;
        $row->save();
        global $config;
        $config[$setting] = $value;
    }

    private static function hotspotLoginHtml($apiBase, $routerId, $token, $support, $gateway, $portal)
    {
        $apiBase = htmlspecialchars($apiBase, ENT_QUOTES, 'UTF-8');
        $token = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $support = htmlspecialchars($support, ENT_QUOTES, 'UTF-8');
        $gateway = self::ip($gateway, '192.168.90.1');
        $portal = self::domain($portal, 'portal.fastnetpay.test');
        $assetBase = 'http://' . htmlspecialchars($portal, ENT_QUOTES, 'UTF-8');
        $portalUrl = 'http://' . htmlspecialchars($portal, ENT_QUOTES, 'UTF-8') . '/login.html';
        $routerId = (int) $routerId;

        return '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FASTNETPAY WiFi</title>
  <link rel="stylesheet" href="' . $assetBase . '/fastnetpay-hotspot.css">
  <script>
    (function(){
      var host = location.hostname || "";
      if (host !== "' . htmlspecialchars($portal, ENT_QUOTES, 'UTF-8') . '" && host !== "' . htmlspecialchars($gateway, ENT_QUOTES, 'UTF-8') . '" && host.indexOf("portal.fastnetpay.") !== 0) {
        location.replace("' . $portalUrl . '");
      }
    })();
  </script>
</head>
<body>
  <main class="fnp-hotspot">
    <section class="fnp-hero">
      <div class="fnp-mark">FASTNETPAY</div>
      <h1>FastNet Test WiFi</h1>
      <p>Select a package, pay with M-Pesa STK Push, and get connected automatically.</p>
    </section>
    <section id="fnpMessage" class="fnp-message">Loading packages...</section>
    <section id="fnpPackages" class="fnp-packages"></section>
    <section class="fnp-pay">
      <h2>Pay with M-Pesa</h2>
      <input id="fnpPhone" type="tel" inputmode="tel" placeholder="07XXXXXXXX">
      <button id="fnpPay" type="button">Pay Now</button>
      <small>Check your phone and enter your M-Pesa PIN.</small>
    </section>
    <section class="fnp-voucher">
      <h2>Have a voucher?</h2>
      <input id="fnpVoucher" type="text" placeholder="Voucher code">
      <button id="fnpVoucherBtn" type="button">Activate Voucher</button>
    </section>
    <footer>Support: ' . $support . '</footer>
  </main>
  <form name="login" action="$(link-login-only)" method="post" style="display:none">
    <input type="hidden" name="username" id="fnpLoginUser">
    <input type="hidden" name="password" id="fnpLoginPass">
    <input type="hidden" name="dst" value="$(link-orig)">
    <input type="hidden" name="popup" value="true">
  </form>
  <script>
    window.FNP_HOTSPOT = {
      apiBase: "' . $apiBase . '",
      routerId: ' . $routerId . ',
      token: "' . $token . '",
      mac: "$(mac)",
      ip: "$(ip)",
      linkLoginOnly: "$(link-login-only)",
      error: "$(error)"
    };
  </script>
  <script src="' . $assetBase . '/fastnetpay-hotspot.js"></script>
</body>
</html>';
    }

    private static function hotspotSimpleHtml($title, $message, $gateway, $portal)
    {
        $assetBase = 'http://' . htmlspecialchars(self::domain($portal, 'portal.fastnetpay.test'), ENT_QUOTES, 'UTF-8');
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>FASTNETPAY - ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title><link rel="stylesheet" href="' . $assetBase . '/fastnetpay-hotspot.css"></head><body><main class="fnp-hotspot fnp-simple"><div class="fnp-mark">FASTNETPAY</div><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p><a href="$(link-login)">Back to WiFi login</a></main></body></html>';
    }

    private static function hotspotRedirectHtml($gateway, $portal)
    {
        $assetBase = 'http://' . htmlspecialchars(self::domain($portal, 'portal.fastnetpay.test'), ENT_QUOTES, 'UTF-8');
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta http-equiv="refresh" content="1; url=$(link-redirect)"><title>FASTNETPAY - Redirecting</title><link rel="stylesheet" href="' . $assetBase . '/fastnetpay-hotspot.css"></head><body><main class="fnp-hotspot fnp-simple"><div class="fnp-mark">FASTNETPAY</div><h1>Connected</h1><p>Taking you to the internet now.</p><a href="$(link-redirect)">Continue</a></main></body></html>';
    }

    private static function hotspotCapportJson($gateway, $portal)
    {
        $portal = self::domain($portal, 'portal.fastnetpay.test');
        return json_encode([
            'captive' => true,
            'user-portal-url' => 'http://' . $portal . '/login.html',
            'venue-info-url' => 'http://' . $portal . '/login.html',
            'can-extend-session' => true,
        ], JSON_UNESCAPED_SLASHES) . "\n";
    }

    private static function hotspotCss()
    {
        return <<<'CSS'
:root{--green:#41a146;--gold:#f9c02b;--bg:#f1f1f1;--text:#1f2933;--muted:#667085;--line:#e5e7eb;--white:#fff}*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:var(--bg);color:var(--text)}.fnp-hotspot{width:min(980px,100%);margin:0 auto;padding:18px}.fnp-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#41a146,#2f7f34);color:#fff;border-radius:22px;padding:24px;box-shadow:0 18px 45px rgba(31,41,51,.18)}.fnp-hero:after{content:"";position:absolute;right:-40px;top:-50px;width:160px;height:160px;border-radius:50%;background:rgba(249,192,43,.35)}.fnp-mark{display:inline-flex;background:#f9c02b;color:#1f2933;font-weight:800;border-radius:999px;padding:7px 12px;margin-bottom:10px}.fnp-hero h1{margin:0;font-size:clamp(26px,7vw,44px);letter-spacing:0}.fnp-hero p{margin:10px 0 0;line-height:1.5;max-width:620px}.fnp-message{margin:14px 0;padding:12px 14px;border-radius:14px;background:#fff;box-shadow:0 8px 24px rgba(31,41,51,.1);font-size:14px}.fnp-message.ok{border-left:5px solid #41a146}.fnp-message.err{border-left:5px solid #dc3545}.fnp-packages{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}.fnp-plan{position:relative;border:1px solid var(--line);background:#fff;text-align:left;border-radius:18px;padding:15px;box-shadow:0 10px 28px rgba(31,41,51,.09);min-height:174px;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}.fnp-plan:hover{transform:translateY(-2px);box-shadow:0 16px 34px rgba(31,41,51,.13);border-color:rgba(65,161,70,.35)}.fnp-plan.active{outline:3px solid rgba(249,192,43,.75);border-color:#f9c02b}.fnp-plan b{display:block;font-size:16px;line-height:1.25;padding-right:28px}.fnp-plan .price{display:block;color:#41a146;margin-top:10px;font-size:24px;font-weight:900}.fnp-plan .meta{display:grid;gap:7px;margin:12px 0}.fnp-chip{display:inline-flex;align-items:center;width:max-content;max-width:100%;border-radius:999px;padding:6px 9px;background:#f3f7f4;color:#1f2933;font-size:12px;font-weight:700}.fnp-chip.gold{background:#fff6d8}.fnp-plan .select{position:absolute;right:12px;top:12px;width:28px;height:28px;border-radius:50%;display:grid;place-items:center;background:#eef7ef;color:#41a146;font-weight:900}.fnp-plan.active .select{background:#f9c02b;color:#1f2933}.fnp-plan .cta{display:block;margin-top:10px;color:#41a146;font-weight:800;font-size:13px}.fnp-pay,.fnp-voucher{margin-top:14px;background:#fff;border-radius:18px;padding:16px;box-shadow:0 10px 28px rgba(31,41,51,.1)}h2{font-size:18px;margin:0 0 10px}input{width:100%;height:46px;border:1px solid #d6dde5;border-radius:14px;padding:0 14px;font-size:16px;margin-bottom:10px}input:focus{outline:3px solid rgba(65,161,70,.18);border-color:#41a146}button{width:100%;min-height:46px;border:0;border-radius:14px;background:#41a146;color:#fff;font-weight:800;font-size:16px}button:disabled{opacity:.65}.fnp-voucher button{background:#f9c02b;color:#1f2933}small,footer{display:block;color:#667085;margin-top:10px}.fnp-simple{text-align:center;min-height:100vh;display:grid;place-content:center}.fnp-simple a{color:#41a146;font-weight:800}@media(max-width:520px){.fnp-hotspot{padding:12px}.fnp-hero{border-radius:18px;padding:22px}.fnp-packages{grid-template-columns:1fr}.fnp-plan{min-height:0}}
CSS;
    }

    private static function hotspotJs()
    {
        return <<<'JS'
(function(){var c=window.FNP_HOTSPOT||{},selected=null,payment=null,timer=null;function qs(id){return document.getElementById(id)}function esc(v){return String(v==null?"":v).replace(/[&<>"']/g,function(s){return({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"})[s]})}function msg(t,k){var el=qs("fnpMessage");if(el){el.className="fnp-message "+(k||"");el.textContent=t}}function api(action,data){data=data||{};data.router=c.routerId;data.token=c.token;data.mac=c.mac;data.ip=c.ip;return fetch(c.apiBase+"/"+action,{method:"POST",mode:"cors",credentials:"omit",cache:"no-store",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:new URLSearchParams(data)}).then(function(r){return r.json().then(function(j){if(!r.ok||j.ok===false){throw new Error(j.message||"Request failed")}return j})})}function planCard(p){var b=document.createElement("button");b.type="button";b.className="fnp-plan";b.innerHTML='<span class="select">+</span><b>'+esc(p.name)+'</b><strong class="price">KES '+esc(p.price)+'</strong><span class="meta"><span class="fnp-chip">'+esc(p.validity||"Valid package")+'</span><span class="fnp-chip gold">'+esc(p.bandwidth||"Best available speed")+'</span><span class="fnp-chip">'+esc(p.limit||"Unlimited data")+'</span></span><span class="cta">Select package and proceed</span>';b.onclick=function(){selected=p.id;document.querySelectorAll(".fnp-plan").forEach(function(x){x.classList.remove("active");var s=x.querySelector(".select");if(s)s.textContent="+"});b.classList.add("active");var mark=b.querySelector(".select");if(mark)mark.textContent="OK";msg("Selected "+p.name+". Enter your M-Pesa phone.","ok");var phone=qs("fnpPhone");if(phone)phone.focus()};return b}function load(){api("packages",{}).then(function(j){var box=qs("fnpPackages");box.innerHTML="";(j.packages||[]).forEach(function(p){box.appendChild(planCard(p))});msg(j.packages&&j.packages.length?"Choose a package to continue.":"No packages are available yet.",j.packages&&j.packages.length?"ok":"err")}).catch(function(e){msg(e.message,"err")})}function poll(){if(!payment)return;api("payment-status",{payment_id:payment.payment_id,payment_token:payment.payment_token}).then(function(j){if(j.status==="paid"){msg("Payment received. Logging you in...","ok");if(j.username){qs("fnpLoginUser").value=j.username;qs("fnpLoginPass").value=j.password||j.username;setTimeout(function(){document.login.submit()},1200)}}else if(j.status==="failed"){msg(j.message||"Payment failed or was cancelled.","err");clearInterval(timer)}else{msg(j.message||"Payment pending. Check your phone.","ok")}}).catch(function(e){msg(e.message,"err")})}qs("fnpPay").onclick=function(){var phone=qs("fnpPhone").value;if(!selected){msg("Select a package first.","err");return}this.disabled=true;api("pay",{plan_id:selected,phone:phone}).then(function(j){payment=j;msg(j.message||"Check your phone and enter your M-Pesa PIN.","ok");timer=setInterval(poll,5000)}).catch(function(e){msg(e.message,"err")}).finally(function(){qs("fnpPay").disabled=false})};qs("fnpVoucherBtn").onclick=function(){api("voucher-login",{voucher:qs("fnpVoucher").value}).then(function(j){msg(j.message,"ok");if(j.username){qs("fnpLoginUser").value=j.username;qs("fnpLoginPass").value=j.password||j.username;setTimeout(function(){document.login.submit()},1000)}}).catch(function(e){msg(e.message,"err")})};load()})();
JS;
    }

    private static function portalUrl()
    {
        return APP_URL . '/?_route=home';
    }

    private static function networkFromCidr($cidr)
    {
        $cidr = trim((string) $cidr);
        if (!preg_match('/^([0-9.]+)\/([0-9]{1,2})$/', $cidr, $match)) {
            return '192.168.88.0/24';
        }
        $ip = ip2long($match[1]);
        $mask = max(0, min(32, (int) $match[2]));
        if ($ip === false) {
            return '192.168.88.0/24';
        }
        $network = $ip & (-1 << (32 - $mask));
        return long2ip($network) . '/' . $mask;
    }

    private static function cidrIp($cidr)
    {
        $parts = explode('/', (string) $cidr);
        return self::ip($parts[0] ?? '', '192.168.88.1');
    }

    private static function q($value)
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value) . '"';
    }

    private static function onceByFind($findCommand, $command)
    {
        return ':if ([:len [' . $findCommand . ']] = 0) do={' . $command . '}';
    }

    private static function upsertByFind($findCommand, $addCommand, $setCommand)
    {
        return ':if ([:len [' . $findCommand . ']] = 0) do={' . $addCommand . '} else={' . $setCommand . '}';
    }

    private static function rosName($value)
    {
        $value = trim((string) $value);
        return $value === '' ? 'ether1' : substr($value, 0, 80);
    }

    private static function domain($value, $default)
    {
        $value = strtolower(trim((string) $value));
        return preg_match('/^[a-z0-9.-]+$/', $value) ? $value : $default;
    }

    private static function ip($value, $default = '')
    {
        $value = trim((string) $value);
        return filter_var($value, FILTER_VALIDATE_IP) ? $value : $default;
    }

    private static function csvIps($value, $default)
    {
        $ips = [];
        foreach (explode(',', (string) $value) as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $ips[] = $ip;
            }
        }
        return empty($ips) ? $default : implode(',', $ips);
    }

    private static function pool($value, $default)
    {
        $value = trim((string) $value);
        return preg_match('/^[0-9., -]+$/', $value) ? str_replace(' ', '', $value) : $default;
    }

    private static function lease($value)
    {
        $value = trim((string) $value);
        return preg_match('/^[0-9]+[smhdw]?$/', $value) ? $value : '12h';
    }

    private static function port($value, $default)
    {
        $port = (int) $value;
        return $port > 0 && $port <= 65535 ? $port : (int) $default;
    }

    private static function choice($value, $choices, $default)
    {
        return in_array($value, $choices, true) ? $value : $default;
    }

    private static function intArray($value)
    {
        if (!is_array($value)) {
            $value = $value === '' ? [] : [$value];
        }
        $out = [];
        foreach ($value as $item) {
            $id = (int) $item;
            if ($id > 0) {
                $out[] = $id;
            }
        }
        return array_values(array_unique($out));
    }

    private static function cleanText($value, $max = 255)
    {
        $value = trim(strip_tags((string) $value));
        return substr($value, 0, $max);
    }

    private static function cleanTextarea($value)
    {
        $value = strip_tags((string) $value);
        return substr($value, 0, 2000);
    }

    private static function redact($value)
    {
        $value = (string) $value;
        $patterns = [
            '/(password=)"[^"]*"/i',
            '/(secret=)"[^"]*"/i',
            '/(passkey=)"[^"]*"/i',
            '/(Authorization:\s*Bearer\s+)[A-Za-z0-9._-]+/i',
        ];
        return preg_replace($patterns, '$1"***"', $value);
    }
}
