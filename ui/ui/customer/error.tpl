<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{ucwords(Lang::T("Error"))} - {$_c['CompanyName']}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="icon" href="{$app_url}/ui/ui/images/fastnetpay-wifi-favicon.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fastnetpay-theme.css?2026.5.21" />
</head>

<body class="fnp-error-page">
    <main class="fnp-error-card">
        <section class="fnp-error-top">
            <div class="fnp-error-illustration" aria-hidden="true">
                <span></span><span></span><span></span>
                <span></span><span></span><span></span>
            </div>
            <span class="fnp-error-icon"><i class="fa fa-exclamation-circle"></i></span>
            <h1>{Lang::T("Internal Error")}</h1>
        </section>
        <section class="fnp-error-body">
            <div class="fnp-error-message">
                {Lang::T("Sorry, the software failed to process the request, if it still happening, please tell")}
                {$_c['CompanyName']}.
            </div>
            <div class="fnp-error-actions">
                <a href="javascript:history.back()" onclick="history.back(); return false;" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> {Lang::T('Go Back')}
                </a>
                <a href="{Text::url('home')}" class="btn btn-primary">
                    <i class="fa fa-home"></i> {Lang::T('Dashboard')}
                </a>
                <a href="javascript:location.reload()" onclick="location.reload(); return false;" class="btn btn-warning">
                    <i class="fa fa-refresh"></i> {Lang::T('Reload')}
                </a>
                {if $_c['fastnetpay_footer_support_email'] neq ''}
                    <a href="mailto:{Lang::htmlspecialchars($_c['fastnetpay_footer_support_email'])}" class="btn btn-info">
                        <i class="fa fa-envelope"></i> {Lang::T('Contact Support')}
                    </a>
                {/if}
            </div>
            {if $_app_stage neq 'Live' && isset($error_debug)}
                <pre class="fnp-debug-details">{$error_debug}</pre>
            {/if}
        </section>
    </main>

    {if $_c['tawkto'] != ''}
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {},
                Tawk_LoadStart = new Date();
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src='https://embed.tawk.to/{$_c['tawkto']}';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
    {/if}
</body>

</html>
