<?php
register_menu("System Info", true, "system_info", 'SETTINGS', '');

/**
 * Bismillahir Rahmanir Raheem
 * 
 * PHP Mikrotik Billing (https://github.com/paybilling/phpnuxbill/)
 *
 * Server Information Plugin For PHPNuxBill 
 *
 * @author: Focuslinks Digital Solutions <focuslinkstech@gmail.com>
 * Website: https://focuslinkstech.com.ng/
 * GitHub: https://github.com/Focuslinkstech/
 * Telegram: https://t.me/focuslinkstech/
 *
 **/

 $system_info_version = '2.1';
 $productName = 'Server Information Plugin';

function system_info()
{
    global $ui, $_app_stage, $system_info_version, $productName;
    _admin();
    $ui->assign('_title', 'System Information');
    $ui->assign('_system_menu', 'settings');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);
    if (!function_exists('shell_exec') || !function_exists('exec')) {
        $ui->assign('message', '<em>' . Lang::T("SHELL_EXEC function is not enabled on your server. Some functions may not work as expected.") . '</em>');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reload']) && $_POST['reload'] === 'true') {
        $output = [];
        $retcode = 0;

        if ($_app_stage == 'Demo') {
            $output['error'] = Lang::T('You cannot perform this action in Demo mode');
            $retcode = 1;
        } elseif (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $output['error'] = Lang::T('You do not have permission to access this page');
            $retcode = 1;
        } elseif (!function_exists('shell_exec') || !function_exists('exec')) {
            $output['error'] = Lang::T('SHELL_EXEC function is not enabled on your server');
            $retcode = 1;
        } else {
            $os = strtoupper(PHP_OS);

            if (strpos($os, 'WIN') === 0) {
                // Windows OS
                exec('net stop freeradius', $output, $retcode);
                exec('net start freeradius', $output, $retcode);
            } else {
                // Linux OS
                exec('sudo systemctl restart freeradius.service 2>&1', $output, $retcode);
            }
        }

        $ui->assign('output', $output);
        $ui->assign('returnCode', $retcode);
    }

    function system_info_get_server_memory_usage()
    {
        // Check if shell_exec or exec is enabled
        if (!function_exists('shell_exec') || !function_exists('exec')) {
            return [
                'total' => null,
                'free' => null,
                'used' => null,
                'used_percentage' => null,
            ];
        }

        $os = strtoupper(substr(PHP_OS, 0, 3));

        if ($os === 'WIN') {
            // Windows system
            $output = [];
            exec('wmic OS get TotalVisibleMemorySize, FreePhysicalMemory /Value', $output);

            $total_memory = null;
            $free_memory = null;

            foreach ($output as $line) {
                if (strpos($line, 'TotalVisibleMemorySize') !== false) {
                    $total_memory = intval(preg_replace('/[^0-9]/', '', $line));
                } elseif (strpos($line, 'FreePhysicalMemory') !== false) {
                    $free_memory = intval(preg_replace('/[^0-9]/', '', $line));
                }

                if ($total_memory !== null && $free_memory !== null) {
                    break;
                }
            }

            if ($total_memory !== null && $free_memory !== null) {
                $total_memory = round($total_memory / 1024); // Convert KB to MB
                $free_memory = round($free_memory / 1024); // Convert KB to MB
                $used_memory = $total_memory - $free_memory;
                $memory_usage_percentage = $total_memory > 0 ? round($used_memory / $total_memory * 100) : 0;

                return [
                    'total' => $total_memory,
                    'free' => $free_memory,
                    'used' => $used_memory,
                    'used_percentage' => $memory_usage_percentage,
                ];
            }
        } elseif ($os === 'DAR') {
            // macOS system
            $output = shell_exec('vm_stat');
            if ($output === null) {
                return [
                    'total' => null,
                    'free' => null,
                    'used' => null,
                    'used_percentage' => null,
                ];
            }

            $lines = explode("\n", trim($output));
            $page_size = 4096;

            // Extract memory statistics
            $pages_free = 0;
            $pages_active = 0;
            $pages_inactive = 0;
            $pages_wired = 0;

            foreach ($lines as $line) {
                if (preg_match('/Pages free:\s+(\d+)\./', $line, $matches)) {
                    $pages_free = intval($matches[1]);
                } elseif (preg_match('/Pages active:\s+(\d+)\./', $line, $matches)) {
                    $pages_active = intval($matches[1]);
                } elseif (preg_match('/Pages inactive:\s+(\d+)\./', $line, $matches)) {
                    $pages_inactive = intval($matches[1]);
                } elseif (preg_match('/Pages wired down:\s+(\d+)\./', $line, $matches)) {
                    $pages_wired = intval($matches[1]);
                }
            }

            // Calculate memory usage
            $free_memory = $pages_free * $page_size / 1024 / 1024;
            $used_memory = ($pages_active + $pages_inactive + $pages_wired) * $page_size / 1024 / 1024; 
            $total_memory = $free_memory + $used_memory;
            $memory_usage_percentage = $total_memory > 0 ? round($used_memory / $total_memory * 100) : 0;

            return [
                'total' => round($total_memory),
                'free' => round($free_memory),
                'used' => round($used_memory),
                'used_percentage' => $memory_usage_percentage,
            ];
        } else {
            // Linux system
            $free = shell_exec('free -m');
            if ($free === null) {
                return [
                    'total' => null,
                    'free' => null,
                    'used' => null,
                    'used_percentage' => null,
                ];
            }

            $free = trim($free);
            $free_arr = explode("\n", $free);
            $mem = explode(" ", $free_arr[1]);
            $mem = array_filter($mem);
            $mem = array_merge($mem);

            if (count($mem) >= 6) {
                $total_memory = intval($mem[1]);
                $used_memory = intval($mem[2]);
                $free_memory = intval($mem[3]);
                $memory_usage_percentage = $total_memory > 0 ? round($used_memory / $total_memory * 100) : 0;

                return [
                    'total' => $total_memory,
                    'free' => $free_memory,
                    'used' => $used_memory,
                    'used_percentage' => $memory_usage_percentage,
                ];
            }
        }

        return [
            'total' => null,
            'free' => null,
            'used' => null,
            'used_percentage' => null,
        ];
    }
    function system_info_getSystemInfo()
    {
        global $ui;

        $db = ORM::getDb();
        $serverInfo = $db->getAttribute(PDO::ATTR_SERVER_VERSION);
        $databaseName = $db->query('SELECT DATABASE()')->fetchColumn();
        $serverName = gethostname();
        $shellExecEnabled = function_exists('shell_exec');
        // Fallback: Let's use $_SERVER['SERVER_NAME'] if gethostname() is not available
        if (!$serverName) {
            $serverName = $_SERVER['SERVER_NAME'];
        }

        // Retrieve the current time from the database
        $currentTime = $db->query('SELECT CURRENT_TIMESTAMP AS current_time_alias')->fetchColumn();

        $systemInfo = [
            'Server Name' => $serverName,
            'Operating System' => php_uname('s'),
            'System Distro' => system_info_getSystemDistro(),
            'PHP Version' => phpversion(),
            'Server Software' => $_SERVER['SERVER_SOFTWARE'],
            'Server IP Address' => $_SERVER['SERVER_ADDR'],
            'Server Port' => $_SERVER['SERVER_PORT'],
            'Remote IP Address' => $_SERVER['REMOTE_ADDR'],
            'Remote Port' => $_SERVER['REMOTE_PORT'],
            'Database Server' => $serverInfo,
            'Database Name' => $databaseName,
            'System Time' => date("F j, Y g:i a"),
            'Database Time' => date("F j, Y g:i a", strtotime($currentTime)),
            'Shell Exec Enabled' => $shellExecEnabled ? 'Yes' : 'No',

            // Add more system information here
        ];

        return $systemInfo;
    }
    function system_info_get_disk_usage()
    {
        // Check if shell_exec or exec is enabled
        if (!function_exists('shell_exec') || !function_exists('exec')) {
            return [
                'total' => null,
                'used' => null,
                'free' => null,
                'used_percentage' => null,
            ];
        }

        $os = strtoupper(substr(PHP_OS, 0, 3));

        if ($os === 'WIN') {
            // Windows system
            $output = [];
            exec('wmic logicaldisk where "DeviceID=\'C:\'" get Size,FreeSpace /format:list', $output);

            if (!empty($output)) {
                $total_disk = 0;
                $free_disk = 0;

                foreach ($output as $line) {
                    if (strpos($line, 'Size=') === 0) {
                        $total_disk = intval(substr($line, 5));
                    } elseif (strpos($line, 'FreeSpace=') === 0) {
                        $free_disk = intval(substr($line, 10));
                    }
                }

                if ($total_disk > 0) {
                    $used_disk = $total_disk - $free_disk;
                    $disk_usage_percentage = round(($used_disk / $total_disk) * 100, 2);

                    return [
                        'total' => system_info_format_bytes($total_disk),
                        'used' => system_info_format_bytes($used_disk),
                        'free' => system_info_format_bytes($free_disk),
                        'used_percentage' => $disk_usage_percentage . '%',
                    ];
                }
            }
        } else {
            // Linux and macOS systems
            $disk = shell_exec('df -k /');
            if ($disk === null) {
                return [
                    'total' => null,
                    'used' => null,
                    'free' => null,
                    'used_percentage' => null,
                ];
            }

            $disk = trim($disk);
            $disk_arr = explode("\n", $disk);
            $disk = explode(" ", preg_replace('/\s+/', ' ', $disk_arr[1]));
            $disk = array_filter($disk);
            $disk = array_merge($disk);

            if (count($disk) >= 5) {
                $total_disk = intval($disk[1]) * 1024;
                $used_disk = intval($disk[2]) * 1024;
                $free_disk = intval($disk[3]) * 1024;
                $disk_usage_percentage = rtrim($disk[4], '%');

                return [
                    'total' => system_info_format_bytes($total_disk),
                    'used' => system_info_format_bytes($used_disk),
                    'free' => system_info_format_bytes($free_disk),
                    'used_percentage' => "$disk_usage_percentage%",
                ];
            }
        }

        return [
            'total' => null,
            'used' => null,
            'free' => null,
            'used_percentage' => null,
        ];
    }

    function system_info_format_bytes($bytes)
    {
        if ($bytes === null) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    function system_info_getSystemDistro()
    {
        $distro = '';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
            $distro = shell_exec('lsb_release -d');
            if ($distro) {
                $distro = trim(substr($distro, strpos($distro, ':') + 1));
            }
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $distro = system_info_getWindowsVersion();
        }

        // We can add more conditions for different operating systems if needed but only Windows and Linux for now

        return $distro;
    }

    function system_info_getWindowsVersion()
    {
        $version = '';

        if (function_exists('shell_exec')) {

            $output = shell_exec('ver');
            if ($output) {
                $lines = explode("\n", $output);
                if (isset($lines[0])) {
                    $version = trim($lines[0]);
                }
            }
        }

        if (empty($version) && function_exists('php_uname')) {

            $version = php_uname('v');
        }

        if (empty($version)) {

            if (isset($_SERVER['SERVER_SOFTWARE'])) {
                $version = $_SERVER['SERVER_SOFTWARE'];
            } elseif (isset($_SERVER['WINDIR'])) {
                $version = $_SERVER['WINDIR'];
            }
        }

        return $version;
    }

    function system_info_generateServiceTable()
    {
        function system_info_check_service($service_name)
        {
            if (empty($service_name)) {
                return false;
            }

            if (!function_exists('shell_exec') || !function_exists('exec')) {
                return false;
            }

            $os = strtoupper(PHP_OS);

            if (strpos($os, 'WIN') === 0) {
                // Windows OS
                $command = sprintf('sc query "%s" | findstr RUNNING', $service_name);
                exec($command, $output, $result_code);
                return $result_code === 0 || !empty($output);
            } else {
                // Linux OS
                $command = sprintf("pgrep %s", escapeshellarg($service_name));
                exec($command, $output, $result_code);
                return $result_code === 0;
            }
        }


        $services_to_check = ["FreeRADIUS", "MySQL", "MariaDB", "Cron", "SSHd"];

        $table = [
            'title' => 'Service Status',
            'rows' => []
        ];

        foreach ($services_to_check as $service_name) {
            $running = system_info_check_service(strtolower($service_name));
            $class = $running ? "label pull-right bg-green" : "label pull-right bg-red";
            $label = $running ? "running" : "not running";

            $value = sprintf('<small class="%s">%s</small>', $class, $label);

            $table['rows'][] = [$service_name, $value];
        }

        return $table;
    }

    $systemInfo = system_info_getSystemInfo();
    $ui->assign('systemInfo', $systemInfo);
    $ui->assign('cpu_info', system_info_getCpuInfo());
    $ui->assign('xheader', '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">');
    $ui->assign('disk_usage', system_info_get_disk_usage());
    $ui->assign('memory_usage', system_info_get_server_memory_usage());
    $ui->assign('serviceTable', system_info_generateServiceTable());
    $ui->assign('version', $system_info_version);
    $ui->assign('productName', $productName);
    $ui->display('system_info.tpl');
}

function system_info_getCpuInfo()
{
    $cpuInfo = [];
    $os = strtoupper(substr(PHP_OS, 0, 3));

    if ($os === 'LIN') {
        // Linux: Use lscpu or /proc/cpuinfo
        if ($cpu = shell_exec('lscpu')) {
            preg_match('/Model name:\s*(.+)/', $cpu, $matches);
            $cpuInfo['model'] = $matches[1] ?? 'N/A';

            preg_match('/CPU\(s\):\s*(\d+)/', $cpu, $matches);
            $cpuInfo['cores'] = isset($matches[1]) && $matches[1] > 0 ? $matches[1] : 1;
        } elseif (file_exists('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/model name\s*:\s*(.+)/', $cpuinfo, $matches);
            $cpuInfo['model'] = $matches[1][0] ?? 'N/A';
            preg_match_all('/processor\s*:\s*\d+/', $cpuinfo, $matches);
            $cpuInfo['cores'] = count($matches[0]) > 0 ? count($matches[0]) : 1;
        }
    } elseif ($os === 'WIN') {
        // Windows: Use wmic command
        if ($cpu = shell_exec('wmic cpu get name,NumberOfCores')) {
            $cpu = explode("\n", trim($cpu));
            if (isset($cpu[1])) {
                $cpuData = preg_split('/\s+/', trim($cpu[1]));
                $cpuInfo['model'] = implode(' ', array_slice($cpuData, 0, -1));
                $cpuInfo['cores'] = end($cpuData);
            }
        }
    } elseif ($os === 'DAR') {
        if ($cpu = shell_exec('sysctl -n machdep.cpu.brand_string')) {
            $cpuInfo['model'] = trim($cpu);
        }
        if ($cores = shell_exec('sysctl -n hw.ncpu')) {
            $cpuInfo['cores'] = intval(trim($cores));
        }
    }

    $cpuInfo['model'] = $cpuInfo['model'] ?? 'N/A';
    $cpuInfo['cores'] = $cpuInfo['cores'] ?? 1;

    // Get CPU usage (cross-platform)
    $load = sys_getloadavg();
    $cpuInfo['load_1min'] = $load[0];
    $cpuInfo['load_5min'] = $load[1];
    $cpuInfo['load_15min'] = $load[2];
    $cpuInfo['usage_percentage'] = round(($load[0] / $cpuInfo['cores']) * 100, 2);

    return $cpuInfo;
}