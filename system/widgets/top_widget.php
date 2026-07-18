<?php


class top_widget
{
    public function getWidget()
    {
        global $ui, $current_date, $start_date;

        $data = $this->readCache($current_date, $start_date);
        if ($data === null) {
            $data = $this->buildDashboardData($current_date, $start_date);
            $this->writeCache($current_date, $start_date, $data);
        }

        $ui->assign('iday', $data['iday']);
        $ui->assign('imonth', $data['imonth']);
        $ui->assign('u_act', $data['u_act']);
        $ui->assign('u_all', $data['u_all']);
        $ui->assign('c_all', $data['c_all']);
        $ui->assign('router_stats', $data['router_stats']);
        return $ui->fetch('widget/top_widget.tpl');
    }

    private function buildDashboardData($current_date, $start_date)
    {
        $iday = ORM::for_table('tbl_transactions')
            ->where('recharged_on', $current_date)
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator');
        $iday = Tenant::scopeIfTenant($iday)->sum('price');

        $imonth = ORM::for_table('tbl_transactions')
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->where_gte('recharged_on', $start_date)
            ->where_lte('recharged_on', $current_date);
        $imonth = Tenant::scopeIfTenant($imonth)->sum('price');

        $uActQuery = ORM::for_table('tbl_user_recharges')->where('status', 'on');
        $uAllQuery = ORM::for_table('tbl_user_recharges');
        $cAllQuery = ORM::for_table('tbl_customers');

        return [
            'iday' => $iday == '' ? '0.00' : $iday,
            'imonth' => $imonth == '' ? '0.00' : $imonth,
            'u_act' => (int) Tenant::scopeIfTenant($uActQuery)->count(),
            'u_all' => (int) Tenant::scopeIfTenant($uAllQuery)->count(),
            'c_all' => (int) Tenant::scopeIfTenant($cAllQuery)->count(),
            'router_stats' => $this->getRouterStats($current_date, $start_date),
        ];
    }

    private function getRouterStats($current_date, $start_date)
    {
        $routers = ORM::for_table('tbl_routers')
            ->order_by_asc('name');
        $routers = Tenant::scopeIfTenant($routers)->find_array();

        $userStats = $this->getRouterUserStats();
        $incomeStats = $this->getRouterIncomeStats($current_date, $start_date);
        $sparkStats = $this->getRouterSparkStats($current_date);

        $stats = [];
        foreach ($routers as $router) {
            $routerName = $router['name'];
            if ($routerName == '') {
                continue;
            }

            $userRow = $userStats[$routerName] ?? [
                'active_users' => 0,
                'total_users' => 0,
                'hotspot_users' => 0,
                'pppoe_users' => 0,
            ];
            $incomeRow = $incomeStats[$routerName] ?? [
                'income_today' => 0,
                'income_month' => 0,
            ];
            $sparkValues = $sparkStats[$routerName] ?? array_fill(0, 7, 0);

            $enabled = (string) $router['enabled'] === '1';
            $status = strtolower((string) $router['status']);
            $isOnline = $enabled && ($status == '' || in_array($status, ['online', 'up', 'active', '1'], true));

            $stats[] = [
                'name' => $routerName,
                'enabled' => $enabled,
                'online' => $isOnline,
                'active_users' => (int) $userRow['active_users'],
                'total_users' => (int) $userRow['total_users'],
                'hotspot_users' => (int) $userRow['hotspot_users'],
                'pppoe_users' => (int) $userRow['pppoe_users'],
                'income_today' => (float) $incomeRow['income_today'],
                'income_month' => (float) $incomeRow['income_month'],
                'spark_points' => $this->buildSparklinePoints($sparkValues),
            ];
        }

        return $stats;
    }

    private function getRouterUserStats()
    {
        $params = [];
        $sql = "SELECT routers,
                SUM(CASE WHEN status = 'on' THEN 1 ELSE 0 END) AS active_users,
                COUNT(DISTINCT username) AS total_users,
                SUM(CASE WHEN type = 'Hotspot' THEN 1 ELSE 0 END) AS hotspot_users,
                SUM(CASE WHEN UPPER(type) = 'PPPOE' THEN 1 ELSE 0 END) AS pppoe_users
            FROM tbl_user_recharges
            WHERE routers IS NOT NULL AND routers <> ''";
        $sql .= $this->tenantSql('tbl_user_recharges', $params);
        $sql .= " GROUP BY routers";

        $rows = ORM::for_table('tbl_user_recharges')->raw_query($sql, $params)->find_array();
        $stats = [];
        foreach ($rows as $row) {
            $stats[(string) $row['routers']] = [
                'active_users' => (int) ($row['active_users'] ?? 0),
                'total_users' => (int) ($row['total_users'] ?? 0),
                'hotspot_users' => (int) ($row['hotspot_users'] ?? 0),
                'pppoe_users' => (int) ($row['pppoe_users'] ?? 0),
            ];
        }

        return $stats;
    }

    private function getRouterIncomeStats($current_date, $start_date)
    {
        $params = [$current_date, $start_date, $current_date, $start_date, $current_date];
        $sql = "SELECT routers,
                SUM(CASE WHEN recharged_on = ? THEN price ELSE 0 END) AS income_today,
                SUM(CASE WHEN recharged_on >= ? AND recharged_on <= ? THEN price ELSE 0 END) AS income_month
            FROM tbl_transactions
            WHERE routers IS NOT NULL AND routers <> ''
                AND method <> 'Customer - Balance'
                AND method <> 'Recharge Balance - Administrator'
                AND recharged_on >= ?
                AND recharged_on <= ?";
        $sql .= $this->tenantSql('tbl_transactions', $params);
        $sql .= " GROUP BY routers";

        $rows = ORM::for_table('tbl_transactions')->raw_query($sql, $params)->find_array();
        $stats = [];
        foreach ($rows as $row) {
            $stats[(string) $row['routers']] = [
                'income_today' => (float) ($row['income_today'] ?? 0),
                'income_month' => (float) ($row['income_month'] ?? 0),
            ];
        }

        return $stats;
    }

    private function getRouterSparkStats($current_date)
    {
        $start = date('Y-m-d', strtotime('-6 days', strtotime($current_date)));
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $labels[] = date('Y-m-d', strtotime("-{$i} days", strtotime($current_date)));
        }

        $params = [$start, $current_date];
        $sql = "SELECT routers, recharged_on, SUM(price) AS total
            FROM tbl_transactions
            WHERE routers IS NOT NULL AND routers <> ''
                AND method <> 'Customer - Balance'
                AND method <> 'Recharge Balance - Administrator'
                AND recharged_on >= ?
                AND recharged_on <= ?";
        $sql .= $this->tenantSql('tbl_transactions', $params);
        $sql .= " GROUP BY routers, recharged_on";

        $rows = ORM::for_table('tbl_transactions')->raw_query($sql, $params)->find_array();
        $stats = [];
        foreach ($rows as $row) {
            $routerName = (string) $row['routers'];
            if (!isset($stats[$routerName])) {
                $stats[$routerName] = array_fill(0, 7, 0);
            }
            $index = array_search($row['recharged_on'], $labels, true);
            if ($index !== false) {
                $stats[$routerName][$index] = (float) ($row['total'] ?? 0);
            }
        }

        return $stats;
    }

    private function tenantSql($table, &$params)
    {
        if (
            class_exists('Tenant')
            && Tenant::isTenantRequest()
            && Tenant::hasColumn($table, 'tenant_id')
        ) {
            $params[] = (int) Tenant::currentId();
            return ' AND tenant_id = ?';
        }

        return '';
    }

    private function readCache($current_date, $start_date)
    {
        $file = $this->cacheFile($current_date, $start_date);
        if (!is_file($file) || (time() - filemtime($file)) > 60) {
            return null;
        }

        $data = @unserialize((string) file_get_contents($file), ['allowed_classes' => false]);
        return is_array($data) ? $data : null;
    }

    private function writeCache($current_date, $start_date, $data)
    {
        $file = $this->cacheFile($current_date, $start_date);
        @file_put_contents($file, serialize($data), LOCK_EX);
    }

    private function cacheFile($current_date, $start_date)
    {
        global $CACHE_PATH;
        $tenant = class_exists('Tenant') && Tenant::currentId() ? Tenant::currentId() : 'main';
        $key = preg_replace('/[^A-Za-z0-9_-]/', '_', $tenant . '_' . $start_date . '_' . $current_date);
        return rtrim($CACHE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'dashboard_top_widget_' . $key . '.temp';
    }

    private function buildSparklinePoints($values)
    {
        $count = count($values);
        if ($count === 0) {
            return '';
        }

        $max = max($values);
        $width = 100;
        $height = 36;
        $baseline = 32;
        $step = $count > 1 ? $width / ($count - 1) : $width;
        $points = [];

        foreach ($values as $index => $value) {
            $x = $index * $step;
            $y = $max > 0 ? $height - (($value / $max) * 28) : $baseline;
            $points[] = number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
        }

        return implode(' ', $points);
    }
}
