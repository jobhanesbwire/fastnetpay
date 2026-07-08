<?php

class default_info_row
{
    public function getWidget()
    {
        global $config,$ui;

        if ($config['enable_balance'] == 'yes'){
            $query = ORM::for_table('tbl_customers')->whereGte('balance', 0);
            $cb = Tenant::scopeIfTenant($query)->sum('balance');
            $ui->assign('cb', $cb);
        }


        return $ui->fetch('widget/default_info_row.tpl');
    }
}
