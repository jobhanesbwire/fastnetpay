<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="icon" href="{$app_url}/ui/ui/images/fastnetpay-wifi-favicon.svg" type="image/svg+xml" />

    <script>
        var appUrl = '{$app_url}';
    </script>

    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/select2.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/select2-bootstrap.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/sweetalert2.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/plugins/pace.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/summernote/summernote.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/phpnuxbill.css?2025.2.4" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/7.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fastnetpay-theme.css?2026.5.23" />

    <script src="{$app_url}/ui/ui/scripts/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
    <style>

    </style>
    {if isset($xheader)}
        {$xheader}
    {/if}

</head>

<body class="hold-transition modern-skin-dark sidebar-mini {if $_kolaps}sidebar-collapse{/if}">
    <div class="wrapper">
        <header class="main-header">
            <a href="{Text::url('dashboard')}" class="logo">
                <span class="logo-mini"><b>F</b>NP</span>
                <span class="logo-lg">{$_c['CompanyName']}</span>
            </a>
            <nav class="navbar navbar-static-top">
                <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button" onclick="return setKolaps()">
                    <span class="sr-only">Toggle navigation</span>
                </a>
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav fnp-navbar-actions">
                        <li>
                            <button id="openSearch" type="button" class="fnp-nav-icon" aria-label="{Lang::T('Search Users')}">
                                <i class="fa fa-search"></i>
                            </button>
                        </li>
                        <li>
                            <button id="themeToggle" type="button" class="fnp-theme-toggle" aria-label="Toggle dark mode" aria-pressed="false">
                                <span class="fnp-toggle-track">
                                    <i class="fa fa-moon-o" id="toggleIcon"></i>
                                </span>
                            </button>
                        </li>
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle fnp-profile-trigger" data-toggle="dropdown">
                                <img src="{if $_admin['photo'] eq '/admin.default.png'}{$app_url}/{$UPLOAD_PATH}/admin.default.png{else}{$app_url}/{$UPLOAD_PATH}{$_admin['photo']}.thumb.jpg{/if}"
                                    onerror="this.src='{$app_url}/{$UPLOAD_PATH}/admin.default.png'" class="user-image"
                                    alt="Avatar">
                                <span class="hidden-xs fnp-profile-name">{$_admin['fullname']}</span>
                                <i class="fa fa-angle-down hidden-xs"></i>
                            </a>
                            <ul class="dropdown-menu fnp-profile-menu">
                                <li class="user-header">
                                    <img src="{if $_admin['photo'] eq '/admin.default.png'}{$app_url}/{$UPLOAD_PATH}/admin.default.png{else}{$app_url}/{$UPLOAD_PATH}{$_admin['photo']}.thumb.jpg{/if}"
                                        onerror="this.src='{$app_url}/{$UPLOAD_PATH}/admin.default.png'" class="img-circle"
                                        alt="Avatar">
                                    <p>
                                        {$_admin['fullname']}
                                        <small><i class="fa fa-shield"></i> {Lang::T($_admin['user_type'])}</small>
                                    </p>
                                </li>
                                <li class="user-body">
                                    <div class="row">
                                        <div class="col-xs-7 text-center text-sm">
                                            <a href="{Text::url('settings/change-password')}"><i
                                                    class="ion ion-settings"></i>
                                                {Lang::T('Change Password')}</a>
                                        </div>
                                        <div class="col-xs-5 text-center text-sm">
                                            <a href="{Text::url('settings/users-view/', $_admin['id'])}">
                                                <i class="ion ion-person"></i> {Lang::T('My Account')}</a>
                                        </div>
                                    </div>
                                </li>
                                <li class="user-footer">
                                    <a href="{Text::url('logout')}" class="btn btn-danger btn-flat btn-block"><i
                                            class="ion ion-power"></i> {Lang::T('Logout')}</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div id="searchOverlay" class="search-overlay" data-search-url="{Text::url('search_user')}">
                    <div class="search-container" role="search">
                        <div class="fnp-search-head">
                            <span><i class="fa fa-search"></i> {Lang::T('Search Users')}</span>
                            <button type="button" id="closeSearch" class="cancelButton" aria-label="{Lang::T('Cancel')}">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <input type="text" id="searchTerm" class="searchTerm"
                            placeholder="{Lang::T('Search Users')}" autocomplete="off">
                        <div id="searchResults" class="search-results"></div>
                    </div>
                </div>
            </nav>
        </header>
        <aside class="main-sidebar">
            <section class="sidebar">
                <ul class="sidebar-menu" data-widget="tree">
                    <li {if $_system_menu eq 'dashboard'}class="active"{/if}>
                        <a href="{Text::url('dashboard')}">
                            <i class="ion ion-monitor"></i>
                            <span>{Lang::T('Dashboard')}</span>
                        </a>
                    </li>
                    {$_MENU_AFTER_DASHBOARD}

                    <li class="{if ($_routes[0] eq 'customers') || ($_routes[0] eq 'plan' && $_routes[1] eq 'list' && _req('status') eq 'off')}active{/if} treeview">
                        <a href="#">
                            <i class="fa fa-users"></i> <span>Clients</span>
                            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'customers' && $_routes[1] eq '' && _req('filter') eq 'all'}class="active"{/if}>
                                <a href="{Text::url('customers&filter=all')}">All Clients</a>
                            </li>
                            <li {if $_routes[0] eq 'customers' && $_routes[1] eq 'hotspot'}class="active"{/if}>
                                <a href="{Text::url('customers/hotspot&filter=all')}">Hotspot Clients</a>
                            </li>
                            <li {if $_routes[0] eq 'customers' && $_routes[1] eq 'pppoe'}class="active"{/if}>
                                <a href="{Text::url('customers/pppoe&filter=all')}">PPPoE Clients</a>
                            </li>
                            <li {if $_routes[0] eq 'customers' && $_routes[1] eq '' && (_req('filter') eq '' || _req('filter') eq 'Active')}class="active"{/if}>
                                <a href="{Text::url('customers&filter=Active')}">Active Clients</a>
                            </li>
                            <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'list' && _req('status') eq 'off'}class="active"{/if}>
                                <a href="{Text::url('plan/list&status=off')}">Expired Clients</a>
                            </li>
                            <li {if $_routes[0] eq 'customers' && $_routes[1] eq 'add'}class="active"{/if}>
                                <a href="{Text::url('customers/add')}">Add Client</a>
                            </li>
                            {$_MENU_CUSTOMERS}
                            {$_MENU_AFTER_CUSTOMERS}
                        </ul>
                    </li>

                    {if !in_array($_admin['user_type'],['Report'])}
                        <li class="{if ($_routes[0] eq 'plan' && (in_array($_routes[1], ['recharge','refill','deposit','voucher','add-voucher']) || ($_routes[1] eq 'list' && _req('status') neq 'off'))) || $_routes[0] eq 'coupons'}active{/if} treeview">
                            <a href="#">
                                <i class="fa fa-refresh"></i> <span>Sales &amp; Recharge</span>
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'recharge'}class="active"{/if}>
                                    <a href="{Text::url('plan/recharge')}">Recharge Client</a>
                                </li>
                                {if $_c['disable_voucher'] != 'yes'}
                                    <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'refill'}class="active"{/if}>
                                        <a href="{Text::url('plan/refill')}">Refill Client</a>
                                    </li>
                                {/if}
                                {if $_c['enable_balance'] == 'yes'}
                                    <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'deposit'}class="active"{/if}>
                                        <a href="{Text::url('plan/deposit')}">Refill Balance</a>
                                    </li>
                                {/if}
                                <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'list' && _req('status') neq 'off'}class="active"{/if}>
                                    <a href="{Text::url('plan/list&status=on')}">Active Subscriptions</a>
                                </li>
                                <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'voucher'}class="active"{/if}>
                                    <a href="{Text::url('plan/voucher')}">Vouchers</a>
                                </li>
                                <li {if $_routes[0] eq 'plan' && $_routes[1] eq 'add-voucher'}class="active"{/if}>
                                    <a href="{Text::url('plan/add-voucher')}">Generate Vouchers</a>
                                </li>
                                {if $_c['enable_coupons'] == 'yes'}
                                    <li {if $_routes[0] eq 'coupons'}class="active"{/if}>
                                        <a href="{Text::url('coupons')}">{Lang::T('Coupons')}</a>
                                    </li>
                                {/if}
                                {$_MENU_SERVICES}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_SERVICES}

                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if $_routes[0] eq 'services' || $_routes[0] eq 'bandwidth'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-cube"></i> <span>Packages &amp; Bandwidth</span>
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'services' && $_routes[1] eq 'hotspot'}class="active"{/if}>
                                    <a href="{Text::url('services/hotspot')}">Hotspot Plans</a>
                                </li>
                                <li {if $_routes[0] eq 'services' && $_routes[1] eq 'pppoe'}class="active"{/if}>
                                    <a href="{Text::url('services/pppoe')}">PPPoE Plans</a>
                                </li>
                                <li {if $_routes[0] eq 'services' && $_routes[1] eq 'vpn'}class="active"{/if}>
                                    <a href="{Text::url('services/vpn')}">VPN Plans</a>
                                </li>
                                <li {if $_routes[0] eq 'bandwidth'}class="active"{/if}>
                                    <a href="{Text::url('bandwidth/list')}">Bandwidth Profiles</a>
                                </li>
                                {if $_c['enable_balance'] == 'yes'}
                                    <li {if $_routes[0] eq 'services' && $_routes[1] eq 'balance'}class="active"{/if}>
                                        <a href="{Text::url('services/balance')}">{Lang::T('Customer Balance')}</a>
                                    </li>
                                {/if}
                                {$_MENU_PLANS}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_PLANS}

                    {if !in_array($_admin['user_type'],['Report'])}
                        <li class="{if $_routes[0] eq 'paymentgateway' || ($_routes[0] eq 'plugin' && $_routes[1] eq 'pay_setup') || ($_routes[0] eq 'reports' && in_array($_routes[1], ['mpesa-logs','transactions']))}active{/if} treeview">
                            <a href="#">
                                <i class="fa fa-credit-card"></i> <span>Payments</span>
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'paymentgateway' && $_routes[1] eq ''}class="active"{/if}>
                                    <a href="{Text::url('paymentgateway')}">Payment Gateways</a>
                                </li>
                                <li {if $_routes[0] eq 'paymentgateway' && $_routes[1] eq 'mpesastkpush'}class="active"{/if}>
                                    <a href="{Text::url('paymentgateway/mpesastkpush')}">MPESA STK Push</a>
                                </li>
                                <li {if $_routes[0] eq 'plugin' && $_routes[1] eq 'pay_setup'}class="active"{/if}>
                                    <a href="{Text::url('plugin/pay_setup')}">Payment Page Settings</a>
                                </li>
                                <li {if $_routes[0] eq 'reports' && $_routes[1] eq 'mpesa-logs'}class="active"{/if}>
                                    <a href="{Text::url('reports/mpesa-logs')}">MPESA Logs</a>
                                </li>
                                <li {if $_routes[0] eq 'reports' && $_routes[1] eq 'transactions'}class="active"{/if}>
                                    <a href="{Text::url('reports/transactions')}">Transactions</a>
                                </li>
                                {$_MENU_AFTER_PAYMENTGATEWAY}
                            </ul>
                        </li>
                    {/if}

                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if ($_routes[0] eq 'plugin' && in_array($_routes[1], ['mikrotik_monitor_ui','mikrotik_monitor_pppoe','mikrotik_monitor_hotspot','mikrotik_import_ui','mikrotik_import_start_ui'])) || in_array($_routes[0], ['routers','pool','odp','radius'])}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-wifi"></i> <span>MikroTik</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'plugin' && $_routes[1] eq 'mikrotik_monitor_ui'}class="active"{/if}>
                                    <a href="{Text::url('plugin/mikrotik_monitor_ui')}">Network Dashboard</a>
                                </li>
                                <li {if $_routes[0] eq 'routers' && !in_array($_routes[1], ['provision','provision-preview','provision-run','provision-logs'])}class="active"{/if}>
                                    <a href="{Text::url('routers')}">Routers</a>
                                </li>
                                <li {if $_routes[0] eq 'routers' && in_array($_routes[1], ['provision','provision-preview','provision-run','provision-logs'])}class="active"{/if}>
                                    <a href="{Text::url('routers/provision')}">Provisioning Wizard</a>
                                </li>
                                <li {if $_routes[0] eq 'plugin' && $_routes[1] eq 'mikrotik_monitor_hotspot'}class="active"{/if}>
                                    <a href="{Text::url('plugin/mikrotik_monitor_hotspot')}">Hotspot Monitor</a>
                                </li>
                                <li {if $_routes[0] eq 'plugin' && $_routes[1] eq 'mikrotik_monitor_pppoe'}class="active"{/if}>
                                    <a href="{Text::url('plugin/mikrotik_monitor_pppoe')}">PPPoE Monitor</a>
                                </li>
                                <li {if $_routes[0] eq 'pool' && $_routes[1] eq 'list'}class="active"{/if}>
                                    <a href="{Text::url('pool/list')}">IP Pools</a>
                                </li>
                                <li {if $_routes[0] eq 'pool' && $_routes[1] eq 'port'}class="active"{/if}>
                                    <a href="{Text::url('pool/port')}">Port Pools</a>
                                </li>
                                <li {if $_routes[0] eq 'odp' && $_routes[1] eq ''}class="active"{/if}>
                                    <a href="{Text::url('odp')}">ODP / Fiber Points</a>
                                </li>
                                <li {if $_routes[0] eq 'plugin' && in_array($_routes[1], ['mikrotik_import_ui','mikrotik_import_start_ui'])}class="active"{/if}>
                                    <a href="{Text::url('plugin/mikrotik_import_ui')}">MikroTik Import</a>
                                </li>
                                {if $_c['radius_enable']}
                                    <li {if $_routes[0] eq 'radius' && $_routes[1] eq 'nas-list'}class="active"{/if}>
                                        <a href="{Text::url('radius/nas-list')}">{Lang::T('Radius NAS')}</a>
                                    </li>
                                    {$_MENU_RADIUS}
                                {/if}
                                {$_MENU_NETWORK}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_NETWORKS}
                    {$_MENU_AFTER_RADIUS}

                    <li class="{if $_routes[0] eq 'message' || ($_routes[0] eq 'plugin' && $_routes[1] eq 'talksasa') || ($_routes[0] eq 'logs' && $_routes[1] eq 'message') || ($_routes[0] eq 'settings' && $_routes[1] eq 'notifications')}active{/if} treeview">
                        <a href="#">
                            <i class="ion ion-android-chat"></i> <span>SMS &amp; Notifications</span>
                            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'message' && ($_routes[1] eq '' || $_routes[1] eq 'send')}class="active"{/if}>
                                <a href="{Text::url('message/send')}">Send Single SMS</a>
                            </li>
                            <li {if $_routes[0] eq 'message' && $_routes[1] eq 'send_bulk'}class="active"{/if}>
                                <a href="{Text::url('message/send_bulk')}">Bulk SMS</a>
                            </li>
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[0] eq 'plugin' && $_routes[1] eq 'talksasa'}class="active"{/if}>
                                    <a href="{Text::url('plugin/talksasa')}">TALKSASA SMS</a>
                                </li>
                            {/if}
                            <li {if $_routes[0] eq 'logs' && $_routes[1] eq 'message'}class="active"{/if}>
                                <a href="{Text::url('logs/message')}">Message Logs</a>
                            </li>
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[0] eq 'settings' && $_routes[1] eq 'notifications'}class="active"{/if}>
                                    <a href="{Text::url('settings/notifications')}">User Notifications</a>
                                </li>
                            {/if}
                            {$_MENU_MESSAGE}
                        </ul>
                    </li>
                    {$_MENU_AFTER_MESSAGE}

                    {if in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
                        <li class="{if $_routes[0] eq 'reports' && !in_array($_routes[1], ['mpesa-logs','transactions'])}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-clipboard"></i> <span>{Lang::T('Reports')}</span>
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'reports' && ($_routes[1] eq '' || $_routes[1] eq 'daily-report')}class="active"{/if}>
                                    <a href="{Text::url('reports/daily-report')}">Daily Reports</a>
                                </li>
                                <li {if $_routes[0] eq 'reports' && $_routes[1] eq 'activation'}class="active"{/if}>
                                    <a href="{Text::url('reports/activation')}">Activation History</a>
                                </li>
                                <li {if $_routes[0] eq 'reports' && $_routes[1] eq 'by-period'}class="active"{/if}>
                                    <a href="{Text::url('reports/by-period')}">Income Reports</a>
                                </li>
                                <li {if $_routes[0] eq 'reports' && $_routes[1] eq 'clients'}class="active"{/if}>
                                    <a href="{Text::url('reports/clients')}">Client Reports</a>
                                </li>
                                <li {if $_routes[0] eq 'reports' && $_routes[1] eq 'routers'}class="active"{/if}>
                                    <a href="{Text::url('reports/routers')}">Router Reports</a>
                                </li>
                                {$_MENU_REPORTS}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_REPORTS}

                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                    <li class="{if ($_routes[0] eq 'plugin' && in_array($_routes[1], ['speedtest','system_info'])) || in_array($_routes[0], ['maps','pages','widgets'])}active{/if} treeview">
                        <a href="#">
                            <i class="fa fa-wrench"></i> <span>Tools</span>
                            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                        </a>
                        <ul class="treeview-menu">
                            <li {if $_routes[0] eq 'plugin' && $_routes[1] eq 'speedtest'}class="active"{/if}>
                                <a href="{Text::url('plugin/speedtest')}">Internet Speedtest</a>
                            </li>
                            <li {if $_routes[0] eq 'maps'}class="active"{/if}>
                                <a href="{Text::url('maps/customer')}">Maps</a>
                            </li>
                            <li {if $_routes[0] eq 'pages'}class="active"{/if}>
                                <a href="{Text::url('pages/Announcement')}">Static Pages</a>
                            </li>
                            <li {if $_routes[0] eq 'widgets'}class="active"{/if}>
                                <a href="{Text::url('widgets')}">Widgets</a>
                            </li>
                            <li {if $_routes[0] eq 'plugin' && $_routes[1] eq 'system_info'}class="active"{/if}>
                                <a href="{Text::url('plugin/system_info')}">System Info</a>
                            </li>
                            {$_MENU_TOOLS}
                        </ul>
                    </li>
                    {/if}
                    {$_MENU_MAPS}
                    {$_MENU_PAGES}

                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if $_routes[0] eq 'pluginmanager'}active{/if} treeview">
                            <a href="#">
                                <i class="glyphicon glyphicon-tasks"></i> <span>Plugins</span>
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'pluginmanager'}class="active"{/if}>
                                    <a href="{Text::url('pluginmanager')}">{Lang::T('Plugin Manager')}</a>
                                </li>
                            </ul>
                        </li>
                    {/if}

                    <li class="{if ($_routes[0] eq 'settings' && !in_array($_routes[1], ['notifications','docs'])) || $_routes[0] eq 'customfield'}active{/if} treeview">
                        <a href="#">
                            <i class="ion ion-gear-a"></i> <span>{Lang::T('Settings')}</span>
                            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                        </a>
                        <ul class="treeview-menu">
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[1] eq 'app'}class="active"{/if}><a href="{Text::url('settings/app')}">{Lang::T('General Settings')}</a></li>
                                <li {if $_routes[1] eq 'localisation'}class="active"{/if}><a href="{Text::url('settings/localisation')}">{Lang::T('Localisation')}</a></li>
                                <li {if $_routes[0] eq 'customfield'}class="active"{/if}><a href="{Text::url('customfield')}">{Lang::T('Custom Fields')}</a></li>
                                <li {if $_routes[1] eq 'miscellaneous'}class="active"{/if}><a href="{Text::url('settings/miscellaneous')}">{Lang::T('Miscellaneous')}</a></li>
                                <li {if $_routes[1] eq 'maintenance'}class="active"{/if}><a href="{Text::url('settings/maintenance')}">{Lang::T('Maintenance Mode')}</a></li>
                                <li {if $_routes[1] eq 'devices'}class="active"{/if}><a href="{Text::url('settings/devices')}">{Lang::T('Devices')}</a></li>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin','Agent'])}
                                <li {if $_routes[1] eq 'users'}class="active"{/if}><a href="{Text::url('settings/users')}">{Lang::T('Administrator Users')}</a></li>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <li {if $_routes[1] eq 'dbstatus'}class="active"{/if}><a href="{Text::url('settings/dbstatus')}">{Lang::T('Backup/Restore')}</a></li>
                            {/if}
                        </ul>
                    </li>

                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li class="{if $_routes[0] eq 'logs' && $_routes[1] neq 'message'}active{/if} treeview">
                            <a href="#">
                                <i class="ion ion-clock"></i> <span>System / Logs</span>
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu">
                                <li {if $_routes[0] eq 'logs' && ($_routes[1] eq '' || $_routes[1] eq 'list') && _req('q') eq ''}class="active"{/if}><a href="{Text::url('logs/list')}">FASTNETPAY Logs</a></li>
                                <li {if $_routes[0] eq 'logs' && $_routes[1] eq 'list' && _req('q') eq 'error'}class="active"{/if}><a href="{Text::url('logs/list&q=error')}">Error Logs</a></li>
                                <li {if $_routes[0] eq 'logs' && $_routes[1] eq 'list' && _req('q') eq 'security'}class="active"{/if}><a href="{Text::url('logs/list&q=security')}">Security Logs</a></li>
                                {$_MENU_LOGS}
                            </ul>
                        </li>
                    {/if}
                    {$_MENU_AFTER_LOGS}

                    {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <li {if $_routes[0] eq 'settings' && $_routes[1] eq 'docs'}class="active"{/if}>
                            <a href="{if $_c['docs_clicked'] != 'yes'}{Text::url('settings/docs')}{else}{$app_url}/docs{/if}">
                                <i class="ion ion-ios-bookmarks"></i>
                                <span class="text">{Lang::T('Documentation')}</span>
                                {if $_c['docs_clicked'] != 'yes'}
                                    <span class="pull-right-container"><small class="label pull-right bg-green">New</small></span>
                                {/if}
                            </a>
                        </li>
                        <li {if $_system_menu eq 'community'}class="active"{/if}>
                            <a href="{Text::url('community')}">
                                <i class="ion ion-chatboxes"></i>
                                <span class="text">Community</span>
                            </a>
                        </li>
                    {/if}
                    {$_MENU_AFTER_COMMUNITY}
                </ul>
            </section>
        </aside>

        {if $_c['maintenance_mode'] == 1}
            <div class="notification-top-bar">
                <p>{Lang::T('The website is currently in maintenance mode, this means that some or all functionality may be
                unavailable to regular users during this time.')}<small> &nbsp;&nbsp;<a
                            href="{Text::url('settings/maintenance')}">{Lang::T('Turn Off')}</a></small></p>
            </div>
        {/if}

        <div class="content-wrapper">
            {if $_system_menu neq 'dashboard'}
                <section class="content-header">
                    <h1>
                        {$_title}
                    </h1>
                </section>
            {/if}

            <section class="content">
                {if isset($notify)}
                    <script>
                        // Display SweetAlert toast notification
                        (window.fnpToast || Swal).fire({
                            icon: '{if $notify_t == "s"}success{else}error{/if}',
                            title: '{$notify}',
                            customClass: { popup: 'fnp-toast' }
                        });
                    </script>
{/if}
