<?php


class top_widget
{
    public function getWidget()
    {
        global $ui, $current_date, $start_date;

        $iday = ORM::for_table('tbl_transactions')
            ->where('recharged_on', $current_date)
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->sum('price');

        if ($iday == '') {
            $iday = '0.00';
        }
        $ui->assign('iday', $iday);

        $imonth = ORM::for_table('tbl_transactions')
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->where_gte('recharged_on', $start_date)
            ->where_lte('recharged_on', $current_date)->sum('price');
        if ($imonth == '') {
            $imonth = '0.00';
        }
        $ui->assign('imonth', $imonth);

        $u_act = ORM::for_table('tbl_user_recharges')->where('status', 'on')->count();
        if (empty($u_act)) {
            $u_act = '0';
        }
        $ui->assign('u_act', $u_act);

        $u_all = ORM::for_table('tbl_user_recharges')->count();
        if (empty($u_all)) {
            $u_all = '0';
        }
        $ui->assign('u_all', $u_all);


        $c_all = ORM::for_table('tbl_customers')->count();
        if (empty($c_all)) {
            $c_all = '0';
        }
        $ui->assign('c_all', $c_all);
        $ui->assign('router_stats', $this->getRouterStats($current_date, $start_date));
        return $ui->fetch('widget/top_widget.tpl');
    }

    private function getRouterStats($current_date, $start_date)
    {
        $routers = ORM::for_table('tbl_routers')
            ->order_by_asc('name')
            ->find_many();

        $stats = [];
        foreach ($routers as $router) {
            $routerName = $router['name'];
            if ($routerName == '') {
                continue;
            }

            $activeUsers = ORM::for_table('tbl_user_recharges')
                ->where('routers', $routerName)
                ->where('status', 'on')
                ->count();

            $totalRow = ORM::for_table('tbl_user_recharges')
                ->select_expr('COUNT(DISTINCT username)', 'total')
                ->where('routers', $routerName)
                ->find_one();
            $totalUsers = $totalRow ? (int) $totalRow->total : 0;

            $hotspotUsers = ORM::for_table('tbl_user_recharges')
                ->where('routers', $routerName)
                ->where('type', 'Hotspot')
                ->count();

            $pppoeUsers = ORM::for_table('tbl_user_recharges')
                ->where('routers', $routerName)
                ->where('type', 'PPPOE')
                ->count();

            $incomeToday = $this->sumRouterIncome($routerName, $current_date, $current_date);
            $incomeMonth = $this->sumRouterIncome($routerName, $start_date, $current_date);
            $sparkValues = $this->getRouterSparkValues($routerName, $current_date);

            $enabled = (string) $router['enabled'] === '1';
            $status = strtolower((string) $router['status']);
            $isOnline = $enabled && ($status == '' || in_array($status, ['online', 'up', 'active', '1'], true));

            $stats[] = [
                'name' => $routerName,
                'enabled' => $enabled,
                'online' => $isOnline,
                'active_users' => (int) $activeUsers,
                'total_users' => (int) $totalUsers,
                'hotspot_users' => (int) $hotspotUsers,
                'pppoe_users' => (int) $pppoeUsers,
                'income_today' => (float) $incomeToday,
                'income_month' => (float) $incomeMonth,
                'spark_points' => $this->buildSparklinePoints($sparkValues),
            ];
        }

        return $stats;
    }

    private function sumRouterIncome($routerName, $startDate, $endDate)
    {
        $sum = ORM::for_table('tbl_transactions')
            ->where('routers', $routerName)
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->where_gte('recharged_on', $startDate)
            ->where_lte('recharged_on', $endDate)
            ->sum('price');

        return $sum == '' ? 0 : $sum;
    }

    private function getRouterSparkValues($routerName, $current_date)
    {
        $values = [];
        $end = strtotime($current_date);
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days", $end));
            $values[] = (float) $this->sumRouterIncome($routerName, $day, $day);
        }

        return $values;
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
