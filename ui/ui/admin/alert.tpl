<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{ucwords(Lang::T($type))} - {$_c['CompanyName']}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="icon" href="{$app_url}/ui/ui/images/fastnetpay-wifi-favicon.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fastnetpay-theme.css?2026.5.22" />
    <meta http-equiv="refresh" content="{$time}; url={$url}">
</head>

<body class="fnp-alert-page">
    <main class="fnp-alert-card">
        <section class="fnp-alert-top" data-type="{$type}">
            <span class="fnp-alert-icon">
                {if $type eq 'success' || $type eq 's'}
                    <i class="fa fa-check"></i>
                {elseif $type eq 'warning' || $type eq 'w'}
                    <i class="fa fa-exclamation"></i>
                {elseif $type eq 'info' || $type eq 'i'}
                    <i class="fa fa-info"></i>
                {else}
                    <i class="fa fa-times"></i>
                {/if}
            </span>
            <h1>{ucwords(Lang::T($type))}</h1>
        </section>
        <section class="fnp-alert-body">
            <div class="fnp-alert-message">{$text}</div>
            <div class="fnp-alert-actions">
                <a href="{$url}" id="button" class="btn btn-primary">
                    {Lang::T('Continue')} ({$time})
                </a>
                <a href="javascript:history.back()" onclick="history.back(); return false;" class="btn btn-default">
                    {Lang::T('Go Back')}
                </a>
            </div>
        </section>
    </main>

    <script>
        var time = {$time};
        timer();

        function timer() {
            setTimeout(function() {
                time--;
                if (time > -1) {
                    document.getElementById("button").innerHTML = "{Lang::T('Continue')} (" + time + ")";
                    timer();
                }
            }, 1000);
        }
    </script>
</body>

</html>
