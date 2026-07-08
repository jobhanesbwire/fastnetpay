<?php

class graph_customers_insight
{
    public function getWidget()
    {
        global $CACHE_PATH,$ui;
        $query = ORM::for_table('tbl_user_recharges')->where('status', 'on');
        $u_act = Tenant::scopeIfTenant($query)->count();
        if (empty($u_act)) {
            $u_act = '0';
        }
        $ui->assign('u_act', $u_act);

        $query = ORM::for_table('tbl_user_recharges');
        $u_all = Tenant::scopeIfTenant($query)->count();
        if (empty($u_all)) {
            $u_all = '0';
        }
        $ui->assign('u_all', $u_all);


        $query = ORM::for_table('tbl_customers');
        $c_all = Tenant::scopeIfTenant($query)->count();
        if (empty($c_all)) {
            $c_all = '0';
        }
        $ui->assign('c_all', $c_all);
        return $ui->fetch('widget/graph_customers_insight.tpl');
    }
}
