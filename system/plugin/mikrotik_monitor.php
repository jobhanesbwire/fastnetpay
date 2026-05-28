<?php

use PEAR2\Net\RouterOS;
use PEAR2\Net\RouterOS\Client;
use PEAR2\Net\RouterOS\Request;


// FASTNETPAY renders MikroTik monitoring as a first-class sidebar group in ui/ui/admin/header.tpl.

function mikrotik_monitor_ui()
{
    global $ui, $routes;
    _admin();
    $admin = Admin::_info();
    mikrotik_monitor_require_admin($admin);
    $routers = mikrotik_monitor_enabled_routers();
    $router = mikrotik_monitor_selected_router($routers, $routes['2'] ?? null);
    $ui->assign('_title', 'Mikrotik Dashboard');
    $ui->assign('_system_menu', 'network');
    $ui->assign('_admin', $admin);
    $ui->assign('routers', $routers);
    $ui->assign('router', $router);
    $ui->assign('csrf_token', Csrf::generateAndStoreToken());
    $ui->assign('xfooter', '<script src="' . APP_URL . '/ui/ui/scripts/fastnetpay-monitor.js?2026.5.22"></script>');
    $ui->display('mikrotik_monitor.tpl');
}

function mikrotik_monitor_pppoe()
{
    global $ui, $routes;
    _admin();
    $admin = Admin::_info();
    mikrotik_monitor_require_admin($admin);
    $routers = mikrotik_monitor_enabled_routers();
    $router = mikrotik_monitor_selected_router($routers, $routes['2'] ?? null);
    $ui->assign('_title', 'PPPoE Monitor');
    $ui->assign('_system_menu', 'network');
    $ui->assign('_admin', $admin);
    $ui->assign('routers', $routers);
    $ui->assign('router', $router);
    $ui->assign('csrf_token', Csrf::generateAndStoreToken());
    $ui->assign('xfooter', '<script src="' . APP_URL . '/ui/ui/scripts/fastnetpay-monitor.js?2026.5.22"></script>');
    $ui->display('mikrotik_pppoe_monitor.tpl');
}

function mikrotik_monitor_hotspot()
{
    global $ui, $routes;
    _admin();
    $admin = Admin::_info();
    mikrotik_monitor_require_admin($admin);
    $routers = mikrotik_monitor_enabled_routers();
    $router = mikrotik_monitor_selected_router($routers, $routes['2'] ?? null);
    $ui->assign('_title', 'Hotspot Monitor');
    $ui->assign('_system_menu', 'network');
    $ui->assign('_admin', $admin);
    $ui->assign('routers', $routers);
    $ui->assign('router', $router);
    $ui->assign('csrf_token', Csrf::generateAndStoreToken());
    $ui->assign('xfooter', '<script src="' . APP_URL . '/ui/ui/scripts/fastnetpay-monitor.js?2026.5.22"></script>');
    $ui->display('mikrotik_hotspot_monitor.tpl');
}

function mikrotik_monitor_get_wlan()
{
    global $routes;
    $router = $routes['2'];
    $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($router);
    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
    $wlan = $client->sendSync(new RouterOS\Request('/interface/wireless/registration-table/print'));

    $signalList = [];
    foreach ($wlan as $signal) {
        $interface = $signal->getProperty('interface');
        $mac_address = $signal->getProperty('mac-address');
        $uptime = $signal->getProperty('uptime');
        $last_ip = $signal->getProperty('last-ip');
        $last_activity = $signal->getProperty('last-activity');
        $signal_strength = $signal->getProperty('signal-strength');
        $tx_ccq = $signal->getProperty('tx-ccq');
        $rx_ccq = $signal->getProperty('rx-ccq');
        $rx_rate = $signal->getProperty('rx-rate');
        $tx_rate = $signal->getProperty('tx-rate');


        $signalList[] = [
            'interface' => $interface,
            'mac_address' => $mac_address,
            'uptime' => $uptime,
            'last_ip' => $last_ip,
            'last_activity' => $last_activity,
            'signal_strength' => $signal_strength,
            'tx_ccq' => $tx_ccq,
            'rx_ccq' => $rx_ccq,
            'rx_rate' => $rx_rate,
            'tx_rate' => $tx_rate,
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($signalList);
}

function mikrotik_monitor_get_resources()
{
    global $routes;
    $router = $routes['2'];
    $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($router);
    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
    $health = $client->sendSync(new RouterOS\Request('/system health print'));
    $res = $client->sendSync(new RouterOS\Request('/system resource print'));
    // Function to round the value and append the appropriate unit
    function mikrotik_monitor_formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        return round($size, 2) . ' ' . $units[$unitIndex];
    }


    $table = '
<style>
    .column-card-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        margin-top: 20px;
    }

    .column-card {
        flex-basis: calc(50% - 20px); /* Dua kartu per baris di layar kecil */
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .column-card-header {
        background-color: #009879;
        color: #fff;
        padding: 10px;
        border-radius: 5px 5px 0 0;
    }

    .column-card-content {
        padding: 15px;
    }

    .column-card-content table {
        width: 100%;
        border-collapse: collapse;
    }

    .column-card-content th,
    .column-card-content td {
        padding: 8px;
        border-bottom: 1px solid #ddd;
    }

    .column-card-content th {
        text-align: left;
        background-color: #f2f2f2;
    }

    .column-card-content td {
        text-align: right;
    }

    @media only screen and (max-width: 768px) {
        .column-card {
            flex-basis: calc(100% - 20px); /* Satu kartu per baris di layar kecil */
        }
    }

    @media only screen and (min-width: 769px) {
        .column-card {
            flex-basis: calc(33.33% - 20px); /* Tiga kartu per baris di layar desktop */
        }
    }
    /* Progress Bar Style */
    .column-card-header_progres {
        background-color: #009879;
        color: #fff;
        padding: 10px;
        border-radius: 5px 5px 0 0;
        font-size: 14px; /* Mengatur ukuran font lebih kecil */
    }

    /* Styles lainnya */
    .progress {
        margin-top: 5px;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
    }

    .progress-bar {
        background-color: rgb(192, 192, 192);
        height: 20px;
        border-radius: 10px;
        margin-bottom: 5px;
        width: 100%;
        position: relative;
    }

    .progress-bar-container {
        background-color: rgb(116, 194, 92);
        color: white;
        padding: 0.25%;
        text-align: right;
        font-size: 14px;
        border-radius: 10px;
        width: 100%;
    }

    .progress-value {
        position: absolute;
        top: 0;
        right: 5px;
        transform: translateY(-50%);
        color: white;
        font-weight: bold;
    }
</style>

<div class="column-card-container">
    <div class="column-card">
        <div class="column-card-header">Platform Information</div>
        <div class="column-card-content">
            <table>
                <tbody>
                    <tr>
                        <th>Platform</th>
                        <td>' . $res->getProperty('platform') . '</td>
                    </tr>
                    <tr>
                        <th>Board</th>
                        <td>' . $res->getProperty('board-name') . '</td>
                    </tr>
                    <tr>
                        <th>Arch</th>
                        <td>' . $res->getProperty('architecture-name') . '</td>
                    </tr>
                    <tr>
                        <th>Version</th>
                        <td>' . $res->getProperty('version') . '</td>
                    </tr>
                    <tr>
                        <th>Mem used/free</th>
                        <td>' . mikrotik_monitor_formatSize($res->getProperty('total-memory') - $res->getProperty('free-memory')) . ' / ' . mikrotik_monitor_formatSize($res->getProperty('free-memory')) . '</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="column-card">
        <div class="column-card-header">System Information</div>
        <div class="column-card-content">
            <table>
                <tbody>
                    <tr>
                        <th>Uptime</th>
                        <td>' . $res->getProperty('uptime') . '</td>
                    </tr>
                    <tr>
                        <th>Build time</th>
                        <td>' . $res->getProperty('build-time') . '</td>
                    </tr>
                    <tr>
                        <th>Factory Software</th>
                        <td>' . $res->getProperty('factory-software') . '</td>
                    </tr>
                    <tr>
                        <th>Free Hdd Space</th>
                        <td>' . mikrotik_monitor_formatSize($res->getProperty('free-hdd-space')) . '</td>
                    </tr>
                    <tr>
                        <th>Total Memory</th>
                        <td>' . mikrotik_monitor_formatSize($res->getProperty('total-memory')) . '</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="column-card">
        <div class="column-card-header">Hardware Information</div>
        <div class="column-card-content">
            <table>
                <tbody>
                    <tr>
                        <th>CPU</th>
                        <td>' . $res->getProperty('cpu') . '</td>
                    </tr>
                    <tr>
                        <th>CPU count/freq/load</th>
                        <td>' . $res->getProperty('cpu-count') . '/' . $res->getProperty('cpu-frequency') . '/' . $res->getProperty('cpu-load') . '</td>
                    </tr>
                    <tr>
                        <th>Hdd</th>
                        <td>' . mikrotik_monitor_formatSize($res->getProperty('free-hdd-space')) . ' / ' . mikrotik_monitor_formatSize($res->getProperty('total-hdd-space')) . '</td>
                    </tr>
                    <tr>
                        <th>Write Total</th>
                        <td>' . $res->getProperty('write-sect-total') . '</td>
                    </tr>
                    <tr>
                        <th>Write Since Reboot</th>
                        <td>' . $res->getProperty('write-sect-since-reboot') . '</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
';
    echo $table;
}

function mikrotik_monitor_get_interfaces_list()
{
    global $routes;
    $router = $routes['2'];
    $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($router);
    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
    
    try {
        $interfaces = $client->sendSync(new RouterOS\Request('/interface/print'));
        $interfaceList = [];
        foreach ($interfaces as $interface) {
            $name = $interface->getProperty('name');
            if (!empty($name)) {
                // Escape HTML characters
                $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                $interfaceList[] = $safeName;
            }
        }
        return $interfaceList;
    } catch (Exception $e) {
        _log('Mikrotik Monitor Error fetching interface list: ' . $e->getMessage());
        sendTelegram('Mikrotik Monitor Error fetching interface list: ' . $e->getMessage());
        return [];
    }
}

function mikrotik_monitor_get_traffic()
{
    global $routes;
    $router = $routes['2'];
    $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($router);
    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
    $traffic = $client->sendSync(new RouterOS\Request('/interface/print'));

    $interfaceData = [];
    foreach ($traffic as $interface) {
        $name = $interface->getProperty('name');
        // Skip interfaces with missing names
        if (empty($name)) {
            continue;
        }

        $txBytes = intval($interface->getProperty('tx-byte'));
        $rxBytes = intval($interface->getProperty('rx-byte'));
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $interfaceData[] = [
            'name' => $name,
            'status' => $interface->getProperty('running') === 'true' ? '
<small class="label bg-green">up</small>' : '
<small class="label bg-red">down</small>',
            'tx' => mikrotik_monitor_formatBytes($txBytes),
            'rx' => mikrotik_monitor_formatBytes($rxBytes),
            'total' => mikrotik_monitor_formatBytes($txBytes + $rxBytes)
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($interfaceData);
}

// Function to format bytes into KB, MB, GB or TB
function mikrotik_monitor_formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function mikrotik_monitor_get_ppp_online_users()
{
    global $routes;
    $router = $routes['2'];
    $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($router);
    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
    $pppUsers = $client->sendSync(new RouterOS\Request('/ppp/active/print'));

    $interfaceTraffic = $client->sendSync(new RouterOS\Request('/interface/print'));
    $interfaceData = [];
    foreach ($interfaceTraffic as $interface) {
        $name = $interface->getProperty('name');
        // Skip interfaces with missing names
        if (empty($name)) {
            continue;
        }

        $interfaceData[$name] = [
            'txBytes' => intval($interface->getProperty('tx-byte')),
            'rxBytes' => intval($interface->getProperty('rx-byte')),
        ];
    }

    $userList = [];
    foreach ($pppUsers as $pppUser) {
        $username = $pppUser->getProperty('name');
        $address = $pppUser->getProperty('address');
        $uptime = $pppUser->getProperty('uptime');
        $service = $pppUser->getProperty('service');
        $callerid = $pppUser->getProperty('caller-id');
        //$bytes_in = $pppUser->getProperty('limit-bytes-in');
        //$bytes_out = $pppUser->getProperty('limit-bytes-out');

        // Retrieve user usage based on interface name
        $interfaceName = "<pppoe-$username>";

        if (isset($interfaceData[$interfaceName])) {
            $trafficData = $interfaceData[$interfaceName];
            $txBytes = $trafficData['txBytes'];
            $rxBytes = $trafficData['rxBytes'];
        } else {
            $txBytes = 0;
            $rxBytes = 0;
        }

        $userList[] = [
            'username' => $username,
            'address' => $address,
            'uptime' => $uptime,
            'service' => $service,
            'caller_id' => $callerid,
            //  'bytes_in' => $bytes_in,
            //  'bytes_out' => $bytes_out,
            'tx' => mikrotik_monitor_formatBytes($txBytes),
            'rx' => mikrotik_monitor_formatBytes($rxBytes),
            'total' => mikrotik_monitor_formatBytes($txBytes + $rxBytes),
        ];
    }
    //  var_dump(isset($interfaceData[$interfaceName]));

    // Return the PPP online user list as JSON
    header('Content-Type: application/json');
    echo json_encode($userList);
}



function mikrotik_monitor_get_hotspot_online_users()
{
    global $routes;
    $router = $routes['2'];
    $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($router);
    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
    $hotspotActive = $client->sendSync(new RouterOS\Request('/ip/hotspot/active/print'));

    $hotspotList = [];
    foreach ($hotspotActive as $hotspot) {
        $username = $hotspot->getProperty('user');
        $address = $hotspot->getProperty('address');
        $uptime = $hotspot->getProperty('uptime');
        $server = $hotspot->getProperty('server');
        $mac = $hotspot->getProperty('mac-address');
        $sessionTime = $hotspot->getProperty('session-time-left');
        $rxBytes = $hotspot->getProperty('bytes-in');
        $txBytes = $hotspot->getProperty('bytes-out');

        $hotspotList[] = [
            'username' => $username,
            'address' => $address,
            'uptime' => $uptime,
            'server' => $server,
            'mac' => $mac,
            'session_time' => $sessionTime,
            'rx_bytes' => mikrotik_monitor_formatBytes($rxBytes),
            'tx_bytes' => mikrotik_monitor_formatBytes($txBytes),
            'total' => mikrotik_monitor_formatBytes($txBytes + $rxBytes),
        ];
    }

    // Return the Hotspot online user list as JSON
    header('Content-Type: application/json');
    echo json_encode($hotspotList);
}

function mikrotik_monitor_disconnect_online_user($router, $username, $userType)
{
    // Check if the form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve the form data
        $router = $_POST['router'];
        $username = $_POST['username'];
        $userType = $_POST['userType'];

        $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($router);

        if (!$mikrotik) {
            // Handle the error response or redirection
            return;
        }

        try {
            $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);

            if ($userType == 'hotspot') {
                Mikrotik::removeHotspotActiveUser($client, $username);
                // Handle the success response or redirection
            } elseif ($userType == 'pppoe') {
                Mikrotik::removePpoeActive($client, $username);
                // Handle the success response or redirection
            } else {
                // Handle the error response or redirection
                return;
            }
        } catch (Exception $e) {
            // Handle the error response or redirection
        } finally {
            // Disconnect from the MikroTik router
            if (isset($client)) {
                $client->disconnect();
            }
        }
    }
}

function mikrotik_monitor_traffic_update()
{
    $interface  = $_GET["interface"];
    global $routes;
    $router = $routes['2'];
    $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($router);
    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);

    try {
        $results = $client->sendSync(
            (new RouterOS\Request('/interface/monitor-traffic'))
                ->setArgument('interface', $interface)
                ->setArgument('once', '')
        );

        $rows = array();
        $rows2 = array();
        $labels = array();

        foreach ($results as $result) {
            $ftx = $result->getProperty('tx-bits-per-second');
            $frx = $result->getProperty('rx-bits-per-second');

            $rows[] = $ftx;
            $rows2[] = $frx;
            $labels[] = date('H:i:s');
        }

        $result = array(
            'labels' => $labels,
            'rows' => array(
                'tx' => $rows,
                'rx' => $rows2
            )
        );
    } catch (Exception $e) {
        $result = array('error' => $e->getMessage());
    }

    // Return the result as JSON
    header('Content-Type: application/json');
    echo json_encode($result);
}

function mikrotik_monitor_get_resources_json()
{
    global $routes;
    $router = $routes['2'];
    $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($router);
    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
    $health = $client->sendSync(new RouterOS\Request('/system health print'));
    $res = $client->sendSync(new RouterOS\Request('/system resource print'));

    $data = [
        'cpu_load' => $res->getProperty('cpu-load') ?? 'N/A',
        'temperature' => $health->getProperty('temperature') ?? 'N/A',
        'voltage' => $health->getProperty('voltage') ?? 'N/A'
    ];

    header('Content-Type: application/json');
    echo json_encode($data);
}

function mikrotik_monitor_require_admin($admin)
{
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'], true)) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
}

function mikrotik_monitor_enabled_routers()
{
    return ORM::for_table('tbl_routers')->where('enabled', '1')->order_by_asc('name')->find_many();
}

function mikrotik_monitor_selected_router($routers, $requested = null)
{
    $requested = (int) $requested;
    foreach ($routers as $router) {
        if ((int) $router['id'] === $requested) {
            return (int) $router['id'];
        }
    }

    return isset($routers[0]) ? (int) $routers[0]['id'] : 0;
}

function mikrotik_monitor_router($routerId)
{
    if (!$routerId) {
        return null;
    }
    return ORM::for_table('tbl_routers')->where('enabled', '1')->find_one((int) $routerId);
}

function mikrotik_monitor_json($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function mikrotik_monitor_client($routerId)
{
    global $_app_stage;
    $router = mikrotik_monitor_router($routerId);
    if (!$router) {
        throw new Exception('Router not found or disabled.');
    }
    if ($_app_stage == 'demo') {
        throw new Exception('Router API is disabled in demo mode.');
    }
    $ipParts = explode(':', $router['ip_address']);
    $host = $ipParts[0];
    $port = isset($ipParts[1]) && $ipParts[1] !== '' ? (int) $ipParts[1] : 8728;
    return new Client($host, $router['username'], $router['password'], $port, false, 3);
}

function mikrotik_monitor_rows($client, $path)
{
    $response = $client->sendSync(new RouterOS\Request($path));
    $rows = [];
    foreach ($response as $row) {
        $rows[] = $row;
    }
    return $rows;
}

function mikrotik_monitor_prop($row, $property, $default = '')
{
    if (!$row || !method_exists($row, 'getProperty')) {
        return $default;
    }
    $value = $row->getProperty($property);
    return $value === null || $value === '' ? $default : $value;
}

function mikrotik_monitor_safe($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function mikrotik_monitor_bool($value)
{
    return $value === true || $value === 'true' || $value === 'yes';
}

function mikrotik_monitor_router_payload($router)
{
    if (!$router) {
        return null;
    }

    return [
        'id' => (int) $router['id'],
        'name' => $router['name'],
        'ip_address' => $router['ip_address'],
        'status' => $router['status'],
        'last_seen' => $router['last_seen'],
        'description' => $router['description'],
    ];
}

function mikrotik_monitor_interfaces_payload($interfaces)
{
    $items = [];
    $trafficTotal = 0;
    foreach ($interfaces as $interface) {
        $name = mikrotik_monitor_prop($interface, 'name');
        if ($name === '') {
            continue;
        }
        $tx = (int) mikrotik_monitor_prop($interface, 'tx-byte', 0);
        $rx = (int) mikrotik_monitor_prop($interface, 'rx-byte', 0);
        $trafficTotal += $tx + $rx;
        $items[] = [
            'name' => $name,
            'running' => mikrotik_monitor_bool(mikrotik_monitor_prop($interface, 'running')),
            'tx' => mikrotik_monitor_formatBytes($tx),
            'rx' => mikrotik_monitor_formatBytes($rx),
            'total' => mikrotik_monitor_formatBytes($tx + $rx),
            'tx_raw' => $tx,
            'rx_raw' => $rx,
        ];
    }

    return [$items, $trafficTotal];
}

function mikrotik_monitor_snapshot()
{
    global $routes;
    _admin();
    $routerId = (int) ($routes['2'] ?? 0);
    $router = mikrotik_monitor_router($routerId);
    $routers = mikrotik_monitor_enabled_routers();

    $data = [
        'ok' => true,
        'router' => mikrotik_monitor_router_payload($router),
        'routers' => array_map('mikrotik_monitor_router_payload', iterator_to_array($routers)),
        'router_count' => count($routers),
        'online_router_count' => (int) ORM::for_table('tbl_routers')->where('enabled', '1')->where('status', 'Online')->count(),
        'local_hotspot_clients' => (int) ORM::for_table('tbl_customers')->where('service_type', 'Hotspot')->count(),
        'local_pppoe_clients' => (int) ORM::for_table('tbl_customers')->where('service_type', 'PPPoE')->count(),
        'active_pppoe' => 0,
        'active_hotspot' => 0,
        'total_pppoe' => 0,
        'total_hotspot' => 0,
        'hotspot_servers' => 0,
        'total_traffic' => '0 B',
        'interfaces' => [],
        'resource' => [],
        'error' => '',
    ];

    if (!$router) {
        $data['ok'] = false;
        $data['error'] = 'No enabled router selected.';
        mikrotik_monitor_json($data);
    }

    try {
        $client = mikrotik_monitor_client($routerId);
        $activePpp = mikrotik_monitor_rows($client, '/ppp/active/print');
        $activeHotspot = mikrotik_monitor_rows($client, '/ip/hotspot/active/print');
        $pppSecrets = mikrotik_monitor_rows($client, '/ppp/secret/print');
        $hotspotUsers = mikrotik_monitor_rows($client, '/ip/hotspot/user/print');
        $hotspotServers = mikrotik_monitor_rows($client, '/ip/hotspot/print');
        $interfaces = mikrotik_monitor_rows($client, '/interface/print');
        list($interfacePayload, $trafficTotal) = mikrotik_monitor_interfaces_payload($interfaces);

        $resourceRows = mikrotik_monitor_rows($client, '/system/resource/print');
        $resource = $resourceRows[0] ?? null;

        $data['active_pppoe'] = count($activePpp);
        $data['active_hotspot'] = count($activeHotspot);
        $data['total_pppoe'] = count($pppSecrets);
        $data['total_hotspot'] = count($hotspotUsers);
        $data['hotspot_servers'] = count($hotspotServers);
        $data['total_traffic'] = mikrotik_monitor_formatBytes($trafficTotal);
        $data['interfaces'] = $interfacePayload;
        $data['resource'] = [
            'uptime' => mikrotik_monitor_prop($resource, 'uptime', 'N/A'),
            'version' => mikrotik_monitor_prop($resource, 'version', 'N/A'),
            'cpu_load' => mikrotik_monitor_prop($resource, 'cpu-load', '0'),
            'free_memory' => mikrotik_monitor_formatBytes((int) mikrotik_monitor_prop($resource, 'free-memory', 0)),
        ];
    } catch (Exception $e) {
        $data['ok'] = false;
        $data['error'] = 'Router API unavailable: ' . $e->getMessage();
    }

    mikrotik_monitor_json($data);
}

function mikrotik_monitor_interface_map($client)
{
    $map = [];
    foreach (mikrotik_monitor_rows($client, '/interface/print') as $interface) {
        $name = mikrotik_monitor_prop($interface, 'name');
        if ($name === '') {
            continue;
        }
        $map[$name] = [
            'tx' => (int) mikrotik_monitor_prop($interface, 'tx-byte', 0),
            'rx' => (int) mikrotik_monitor_prop($interface, 'rx-byte', 0),
        ];
    }
    return $map;
}

function mikrotik_monitor_pppoe_data()
{
    global $routes;
    _admin();
    $routerId = (int) ($routes['2'] ?? 0);
    $rows = [];
    $error = '';

    try {
        $client = mikrotik_monitor_client($routerId);
        $active = [];
        foreach (mikrotik_monitor_rows($client, '/ppp/active/print') as $session) {
            $name = mikrotik_monitor_prop($session, 'name');
            if ($name === '') {
                continue;
            }
            $active[$name] = $session;
        }

        $interfaceMap = mikrotik_monitor_interface_map($client);
        $seen = [];
        foreach (mikrotik_monitor_rows($client, '/ppp/secret/print') as $secret) {
            $name = mikrotik_monitor_prop($secret, 'name');
            if ($name === '') {
                continue;
            }
            $session = $active[$name] ?? null;
            $interfaceName = '<pppoe-' . $name . '>';
            $traffic = $interfaceMap[$interfaceName] ?? ['tx' => 0, 'rx' => 0];
            $disabled = mikrotik_monitor_bool(mikrotik_monitor_prop($secret, 'disabled'));
            $rows[] = [
                'username' => $name,
                'address' => mikrotik_monitor_prop($session, 'address', ''),
                'uptime' => mikrotik_monitor_prop($session, 'uptime', '-'),
                'service' => mikrotik_monitor_prop($secret, 'service', mikrotik_monitor_prop($session, 'service', 'pppoe')),
                'profile' => mikrotik_monitor_prop($secret, 'profile', ''),
                'caller_id' => mikrotik_monitor_prop($session, 'caller-id', ''),
                'tx' => mikrotik_monitor_formatBytes($traffic['tx']),
                'rx' => mikrotik_monitor_formatBytes($traffic['rx']),
                'total' => mikrotik_monitor_formatBytes($traffic['tx'] + $traffic['rx']),
                'tx_raw' => $traffic['tx'],
                'rx_raw' => $traffic['rx'],
                'status' => $disabled ? 'disabled' : ($session ? 'online' : 'offline'),
                'comment' => mikrotik_monitor_prop($secret, 'comment', ''),
            ];
            $seen[$name] = true;
        }

        foreach ($active as $name => $session) {
            if (isset($seen[$name])) {
                continue;
            }
            $interfaceName = '<pppoe-' . $name . '>';
            $traffic = $interfaceMap[$interfaceName] ?? ['tx' => 0, 'rx' => 0];
            $rows[] = [
                'username' => $name,
                'address' => mikrotik_monitor_prop($session, 'address', ''),
                'uptime' => mikrotik_monitor_prop($session, 'uptime', '-'),
                'service' => mikrotik_monitor_prop($session, 'service', 'pppoe'),
                'profile' => '',
                'caller_id' => mikrotik_monitor_prop($session, 'caller-id', ''),
                'tx' => mikrotik_monitor_formatBytes($traffic['tx']),
                'rx' => mikrotik_monitor_formatBytes($traffic['rx']),
                'total' => mikrotik_monitor_formatBytes($traffic['tx'] + $traffic['rx']),
                'tx_raw' => $traffic['tx'],
                'rx_raw' => $traffic['rx'],
                'status' => 'online',
                'comment' => '',
            ];
        }
    } catch (Exception $e) {
        $error = 'Router API unavailable: ' . $e->getMessage();
        $customers = ORM::for_table('tbl_customers')->where('service_type', 'PPPoE')->order_by_asc('username')->limit(500)->find_many();
        foreach ($customers as $customer) {
            $rows[] = [
                'username' => $customer['pppoe_username'] ?: $customer['username'],
                'address' => $customer['pppoe_ip'],
                'uptime' => '-',
                'service' => 'pppoe',
                'profile' => '',
                'caller_id' => '',
                'tx' => '0 B',
                'rx' => '0 B',
                'total' => '0 B',
                'tx_raw' => 0,
                'rx_raw' => 0,
                'status' => $customer['status'] === 'Active' ? 'offline' : 'disabled',
                'comment' => $customer['fullname'],
            ];
        }
    }

    $stats = mikrotik_monitor_table_stats($rows);
    mikrotik_monitor_json(['ok' => $error === '', 'error' => $error, 'stats' => $stats, 'rows' => $rows]);
}

function mikrotik_monitor_hotspot_data()
{
    global $routes;
    _admin();
    $routerId = (int) ($routes['2'] ?? 0);
    $rows = [];
    $error = '';
    $serverCount = 0;

    try {
        $client = mikrotik_monitor_client($routerId);
        $active = [];
        foreach (mikrotik_monitor_rows($client, '/ip/hotspot/active/print') as $session) {
            $name = mikrotik_monitor_prop($session, 'user');
            if ($name === '') {
                continue;
            }
            $active[$name] = $session;
        }

        $serverCount = count(mikrotik_monitor_rows($client, '/ip/hotspot/print'));
        $seen = [];
        foreach (mikrotik_monitor_rows($client, '/ip/hotspot/user/print') as $user) {
            $name = mikrotik_monitor_prop($user, 'name');
            if ($name === '') {
                continue;
            }
            $session = $active[$name] ?? null;
            $rx = (int) mikrotik_monitor_prop($session, 'bytes-in', 0);
            $tx = (int) mikrotik_monitor_prop($session, 'bytes-out', 0);
            $disabled = mikrotik_monitor_bool(mikrotik_monitor_prop($user, 'disabled'));
            $rows[] = [
                'username' => $name,
                'address' => mikrotik_monitor_prop($session, 'address', ''),
                'mac' => mikrotik_monitor_prop($session, 'mac-address', mikrotik_monitor_prop($user, 'mac-address', '')),
                'uptime' => mikrotik_monitor_prop($session, 'uptime', '-'),
                'server' => mikrotik_monitor_prop($session, 'server', mikrotik_monitor_prop($user, 'server', '')),
                'profile' => mikrotik_monitor_prop($user, 'profile', ''),
                'rx' => mikrotik_monitor_formatBytes($rx),
                'tx' => mikrotik_monitor_formatBytes($tx),
                'total' => mikrotik_monitor_formatBytes($rx + $tx),
                'rx_raw' => $rx,
                'tx_raw' => $tx,
                'status' => $disabled ? 'disabled' : ($session ? 'online' : 'offline'),
                'comment' => mikrotik_monitor_prop($user, 'comment', ''),
                'session_time' => mikrotik_monitor_prop($session, 'session-time-left', ''),
            ];
            $seen[$name] = true;
        }

        foreach ($active as $name => $session) {
            if (isset($seen[$name])) {
                continue;
            }
            $rx = (int) mikrotik_monitor_prop($session, 'bytes-in', 0);
            $tx = (int) mikrotik_monitor_prop($session, 'bytes-out', 0);
            $rows[] = [
                'username' => $name,
                'address' => mikrotik_monitor_prop($session, 'address', ''),
                'mac' => mikrotik_monitor_prop($session, 'mac-address', ''),
                'uptime' => mikrotik_monitor_prop($session, 'uptime', '-'),
                'server' => mikrotik_monitor_prop($session, 'server', ''),
                'profile' => '',
                'rx' => mikrotik_monitor_formatBytes($rx),
                'tx' => mikrotik_monitor_formatBytes($tx),
                'total' => mikrotik_monitor_formatBytes($rx + $tx),
                'rx_raw' => $rx,
                'tx_raw' => $tx,
                'status' => 'online',
                'comment' => '',
                'session_time' => mikrotik_monitor_prop($session, 'session-time-left', ''),
            ];
        }
    } catch (Exception $e) {
        $error = 'Router API unavailable: ' . $e->getMessage();
        $customers = ORM::for_table('tbl_customers')->where('service_type', 'Hotspot')->order_by_asc('username')->limit(500)->find_many();
        foreach ($customers as $customer) {
            $rows[] = [
                'username' => $customer['username'],
                'address' => '',
                'mac' => '',
                'uptime' => '-',
                'server' => '',
                'profile' => '',
                'rx' => '0 B',
                'tx' => '0 B',
                'total' => '0 B',
                'rx_raw' => 0,
                'tx_raw' => 0,
                'status' => $customer['status'] === 'Active' ? 'offline' : 'disabled',
                'comment' => $customer['fullname'],
                'session_time' => '',
            ];
        }
    }

    $stats = mikrotik_monitor_table_stats($rows);
    $stats['servers'] = $serverCount;
    mikrotik_monitor_json(['ok' => $error === '', 'error' => $error, 'stats' => $stats, 'rows' => $rows]);
}

function mikrotik_monitor_table_stats($rows)
{
    $stats = ['total' => count($rows), 'online' => 0, 'offline' => 0, 'disabled' => 0];
    foreach ($rows as $row) {
        $status = $row['status'] ?? 'offline';
        if (!isset($stats[$status])) {
            $stats[$status] = 0;
        }
        $stats[$status]++;
    }
    return $stats;
}

function mikrotik_monitor_disconnect()
{
    _admin();
    $admin = Admin::_info();
    mikrotik_monitor_require_admin($admin);

    if (!Csrf::check(_post('csrf_token'))) {
        mikrotik_monitor_json(['ok' => false, 'message' => 'Invalid CSRF token.'], 403);
    }

    $routerId = (int) _post('router');
    $username = alphanumeric(_post('username'), '_-@.:');
    $type = _post('type');

    if (!$routerId || $username === '' || !in_array($type, ['hotspot', 'pppoe'], true)) {
        mikrotik_monitor_json(['ok' => false, 'message' => 'Invalid disconnect request.'], 422);
    }

    try {
        $client = mikrotik_monitor_client($routerId);
        if ($type === 'hotspot') {
            Mikrotik::removeHotspotActiveUser($client, $username);
        } else {
            Mikrotik::removePpoeActive($client, $username);
        }
        _log('[' . $admin['username'] . ']: disconnected ' . $type . ' session for ' . $username, 'Mikrotik Monitor', $admin['id']);
        mikrotik_monitor_json(['ok' => true, 'message' => 'Session disconnected.']);
    } catch (Exception $e) {
        mikrotik_monitor_json(['ok' => false, 'message' => 'Disconnect failed: ' . $e->getMessage()], 500);
    }
}

function mikrotik_monitor_fetchLogs($routerId)
{
    if (!$routerId) {
        return [];
    }

    try {
        $mikrotik = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routerId);
        if (!$mikrotik) {
            return [];
        }

        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $request = new Request('/log/print');
        $response = $client->sendSync($request);

        $logs = [];
        foreach ($response as $entry) {
            $logs[] = $entry->getIterator()->getArrayCopy();
        }
        return $logs;
    } catch (Exception $e) {
        _log('Error fetching logs: ' . $e->getMessage());
        sendTelegram('Mikrotik Monitor Error fetching logs.\nReport: ' . $e->getMessage());
        return [];
    }
}

function mikrotik_monitor_getLogs()
{
    header('Content-Type: application/json');
    $routerId = $_GET['routerId'] ?? null;

    $logs = mikrotik_monitor_fetchLogs($routerId);
    echo json_encode($logs);
}
