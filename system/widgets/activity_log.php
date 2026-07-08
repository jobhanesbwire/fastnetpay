<?php


class activity_log
{
    public function getWidget()
    {
        global $config, $ui, $current_date, $start_date;
        $query = ORM::for_table('tbl_logs')->limit(5)->order_by_desc('id');
        $dlog = Tenant::scopeIfTenant($query)->findArray();
        $ui->assign('dlog', $dlog);
        // $log = ORM::for_table('tbl_logs')->count();
        // $ui->assign('log', $log);
        return $ui->fetch('widget/activity_log.tpl');
    }
}
