<?php

/**
 * FASTNETPAY local performance profiler.
 */

_admin();
$ui->assign('_title', 'FASTNETPAY Performance');
$ui->assign('_system_menu', 'performance');
$ui->assign('_admin', $admin);

if (!$admin || $admin['user_type'] !== 'SuperAdmin') {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

PerformanceProfiler::installSchema();

$ui->assign('summary', PerformanceProfiler::summary());
$ui->assign('samples', PerformanceProfiler::latestSamples(120));
$ui->assign('cache_status', PerformanceProfiler::cacheStatus());
$ui->assign('index_warnings', PerformanceProfiler::indexWarnings());
$ui->assign('cron_health', PerformanceProfiler::cronHealth());
$ui->assign('asset_notes', PerformanceProfiler::assetNotes());
$ui->display('admin/performance.tpl');
