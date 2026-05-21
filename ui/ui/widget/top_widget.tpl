<div class="fnp-metrics-grid">
    {if in_array($_admin['user_type'],['SuperAdmin','Admin', 'Report'])}
        <a href="{Text::url('reports/by-date')}" class="fnp-metric-card" data-tone="sky">
            <span class="fnp-metric-icon"><i class="ion ion-clock"></i></span>
            <span class="fnp-metric-label">{Lang::T('Income Today')}</span>
            <strong><sup>{$_c['currency_code']}</sup>{number_format($iday,0,$_c['dec_point'],$_c['thousands_sep'])}</strong>
            <span class="fnp-metric-link">{Lang::T('View Report')} <i class="fa fa-angle-right"></i></span>
        </a>
        <a href="{Text::url('reports/by-period')}" class="fnp-metric-card" data-tone="green">
            <span class="fnp-metric-icon"><i class="ion ion-android-calendar"></i></span>
            <span class="fnp-metric-label">{Lang::T('Income This Month')}</span>
            <strong><sup>{$_c['currency_code']}</sup>{number_format($imonth,0,$_c['dec_point'],$_c['thousands_sep'])}</strong>
            <span class="fnp-metric-link">{Lang::T('View Report')} <i class="fa fa-angle-right"></i></span>
        </a>
    {/if}
    <a href="{Text::url('plan/list')}" class="fnp-metric-card" data-tone="gold">
        <span class="fnp-metric-icon"><i class="ion ion-person"></i></span>
        <span class="fnp-metric-label">{Lang::T('Active')}/{Lang::T('Expired')}</span>
        <strong>{$u_act}<span class="fnp-metric-divider">/</span>{$u_all-$u_act}</strong>
        <span class="fnp-metric-link">{Lang::T('Open Services')} <i class="fa fa-angle-right"></i></span>
    </a>
    <a href="{Text::url('customers/list')}" class="fnp-metric-card" data-tone="coral">
        <span class="fnp-metric-icon"><i class="ion ion-android-people"></i></span>
        <span class="fnp-metric-label">{Lang::T('Customers')}</span>
        <strong>{$c_all}</strong>
        <span class="fnp-metric-link">{Lang::T('Manage Customers')} <i class="fa fa-angle-right"></i></span>
    </a>
</div>

<section class="fnp-router-section">
    <div class="fnp-router-section-head">
        <div>
            <h2>{Lang::T('Router Statistics')}</h2>
            <p>{Lang::T('Live billing summary by MikroTik router')}</p>
        </div>
        {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
            <a href="{Text::url('routers/list')}" class="btn btn-default btn-sm">
                <i class="fa fa-sitemap"></i> {Lang::T('Manage Routers')}
            </a>
        {/if}
    </div>

    {if isset($router_stats) && count($router_stats) > 0}
        <div class="fnp-router-grid">
            {foreach $router_stats as $router}
                <article class="fnp-router-card">
                    <div class="fnp-router-card-head">
                        <div class="fnp-router-name">
                            <span class="fnp-router-dot {if $router.online}is-online{/if}"></span>
                            <span>{Lang::htmlspecialchars($router.name)}</span>
                        </div>
                        <span class="fnp-router-status {if !$router.online}is-offline{/if}">
                            {if $router.online}{Lang::T('Online')}{else}{Lang::T('Offline')}{/if}
                        </span>
                    </div>

                    <div class="fnp-router-stats">
                        <div class="fnp-router-stat">
                            <span>{Lang::T('Active Users')}</span>
                            <strong>{$router.active_users}</strong>
                        </div>
                        <div class="fnp-router-stat">
                            <span>{Lang::T('Total Users')}</span>
                            <strong>{$router.total_users}</strong>
                        </div>
                        <div class="fnp-router-stat">
                            <span>{Lang::T('Hotspot Users')}</span>
                            <strong>{$router.hotspot_users}</strong>
                        </div>
                        <div class="fnp-router-stat">
                            <span>{Lang::T('PPPoE Users')}</span>
                            <strong>{$router.pppoe_users}</strong>
                        </div>
                    </div>

                    <div class="fnp-router-income">
                        <div>
                            <span>{Lang::T('Income Today')}</span>
                            <strong>{$_c['currency_code']} {number_format($router.income_today,0,$_c['dec_point'],$_c['thousands_sep'])}</strong>
                        </div>
                        <div>
                            <span>{Lang::T('This Month')}</span>
                            <strong>{$_c['currency_code']} {number_format($router.income_month,0,$_c['dec_point'],$_c['thousands_sep'])}</strong>
                        </div>
                    </div>

                    <div class="fnp-router-spark" aria-hidden="true">
                        <svg viewBox="0 0 100 40" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="fnp-router-gradient-{$router@iteration}" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#41a146" stop-opacity=".30"></stop>
                                    <stop offset="100%" stop-color="#41a146" stop-opacity="0"></stop>
                                </linearGradient>
                            </defs>
                            <polyline points="0,38 {$router.spark_points} 100,38" fill="url(#fnp-router-gradient-{$router@iteration})" stroke="none"></polyline>
                            <polyline points="{$router.spark_points}" fill="none" stroke="#41a146" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></polyline>
                        </svg>
                    </div>
                </article>
            {/foreach}
        </div>
    {else}
        <div class="fnp-router-empty">
            <i class="fa fa-info-circle"></i> {Lang::T('No routers have been added yet. Add a MikroTik router to see live network statistics here.')}
        </div>
    {/if}
</section>
