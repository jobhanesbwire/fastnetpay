{include file="sections/header.tpl"}

{function showWidget pos=0}
    {foreach $widgets as $w}
        {if $w['position'] == $pos}
            {$w['content']}
        {/if}
    {/foreach}
{/function}

{assign dtipe value="dashboard_`$tipeUser`"}

<div class="fastnetpay-dashboard">
    <section class="fnp-dashboard-hero">
        <div class="fnp-dashboard-hero-copy">
            <span class="fnp-dashboard-kicker"><i class="fa fa-wifi"></i> FASTNETPAY</span>
            <h1>Network Overview</h1>
            <p>{Lang::dateFormat($start_date)} - {Lang::dateFormat($current_date)}</p>
        </div>
        <div class="fnp-dashboard-actions">
            {if !in_array($_admin['user_type'],['Report'])}
                <a href="{Text::url('customers/add')}" class="fnp-dashboard-action">
                    <i class="fa fa-user-plus"></i>
                    <span>{Lang::T('Add Customer')}</span>
                </a>
                <a href="{Text::url('plan/recharge')}" class="fnp-dashboard-action">
                    <i class="fa fa-bolt"></i>
                    <span>{Lang::T('Recharge Customer')}</span>
                </a>
            {/if}
            <a href="{Text::url('reports')}" class="fnp-dashboard-action">
                <i class="fa fa-line-chart"></i>
                <span>{Lang::T('Reports')}</span>
            </a>
            <a href="{Text::url('dashboard&refresh')}" class="fnp-dashboard-action">
                <i class="fa fa-refresh"></i>
                <span>{Lang::T('Refresh')}</span>
            </a>
        </div>
    </section>

    {assign rows explode(".", $_c[$dtipe])}
    {assign pos 1}
    {foreach $rows as $cols}
        {if $cols == 12}
            <div class="row fnp-dashboard-row">
                <div class="col-md-12 fnp-dashboard-col">
                    {showWidget widgets=$widgets pos=$pos}
                </div>
            </div>
            {assign pos value=$pos+1}
        {else}
            {assign colss explode(",", $cols)}
            <div class="row fnp-dashboard-row">
                {foreach $colss as $c}
                    <div class="col-md-{$c} fnp-dashboard-col">
                        {showWidget widgets=$widgets pos=$pos}
                    </div>
                    {assign pos value=$pos+1}
                {/foreach}
            </div>
        {/if}
    {/foreach}
</div>

{if $_c['new_version_notify'] != 'disable'}
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            $.getJSON("./version.json?" + Math.random(), function(data) {
                var localVersion = data.version;
                $('#version').html('Version: ' + localVersion);
                $.getJSON(
                    "https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/version.json?" +
                    Math
                    .random(),
                    function(data) {
                        var latestVersion = data.version;
                        if (localVersion !== latestVersion) {
                            $('#version').html('Latest Version: ' + latestVersion);
                            if (getCookie(latestVersion) != 'done') {
                                Swal.fire({
                                    icon: 'info',
                                    title: "New Version Available\nVersion: " + latestVersion,
                                    toast: true,
                                    position: 'bottom-right',
                                    showConfirmButton: true,
                                    showCloseButton: true,
                                    timer: 30000,
                                    confirmButtonText: '<a href="{Text::url('community')}#latestVersion" style="color: white;">Update Now</a>',
                                    timerProgressBar: true,
                                    didOpen: (toast) => {
                                        toast.addEventListener('mouseenter', Swal.stopTimer)
                                        toast.addEventListener('mouseleave', Swal
                                            .resumeTimer)
                                    }
                                });
                                setCookie(latestVersion, 'done', 7);
                            }
                        }
                    });
            });

        });
    </script>
{/if}

{include file="sections/footer.tpl"}
