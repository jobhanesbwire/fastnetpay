<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{if isset($error_title)}{$error_title}{else}Error{/if} - {$_c['CompanyName']}</title>
    <link rel="icon" href="{$app_url}/ui/ui/images/fastnetpay-wifi-favicon.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fastnetpay-theme.css?2026.5.22" />
</head>

<body class="fnp-error-page">
    <main class="fnp-error-card">
        <section class="fnp-error-top">
            <div class="fnp-error-illustration" aria-hidden="true">
                <span></span><span></span><span></span>
                <span></span><span></span><span></span>
            </div>
            <span class="fnp-error-icon"><i class="fa fa-exclamation-triangle"></i></span>
            <h1>{if isset($error_title)}{$error_title}{else}{Lang::T('Something needs attention')}{/if}</h1>
        </section>
        <section class="fnp-error-body">
            <div class="fnp-error-message">
                {if isset($error_summary)}
                    {$error_summary}
                {elseif isset($error_message)}
                    {$error_message}
                {else}
                    {Lang::T('The request could not be completed. Please reload the page or return to the dashboard.')}
                {/if}
            </div>

            <div class="fnp-error-help">
                <strong>{Lang::T('Mikrotik troubleshooting')}:</strong>
                <ul>
                    <li>{Lang::T('Make sure you use API Port, Default 8728')}</li>
                    <li>{Lang::T('Make sure Username and Password are correct')}</li>
                    <li>{Lang::T('Make sure your hosting not blocking port to external')}</li>
                    <li>{Lang::T('Make sure your Mikrotik is accessible from FASTNETPAY')}</li>
                </ul>
            </div>

            <div class="fnp-error-actions">
                <a href="javascript:history.back()" onclick="history.back(); return false;" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> {Lang::T('Go Back')}
                </a>
                <a href="{Text::url('dashboard')}" class="btn btn-primary">
                    <i class="fa fa-dashboard"></i> {Lang::T('Dashboard')}
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

            <div class="fnp-error-actions fnp-error-secondary-actions">
                <a href="./update.php?step=4" class="btn btn-link">{Lang::T('Update')} Database</a>
                <a href="{Text::url('community#update')}" class="btn btn-link">{Lang::T('Update FASTNETPAY')}</a>
                <a href="https://github.com/hotspotbilling/phpnuxbill/discussions" target="_blank" rel="nofollow noreferrer noopener"
                    class="btn btn-link">{Lang::T('Ask Github Community')}</a>
                <a href="https://t.me/phpnuxbill" target="_blank" rel="nofollow noreferrer noopener"
                    class="btn btn-link">{Lang::T('Ask Telegram Community')}</a>
            </div>

            {if $_app_stage neq 'Live' && isset($error_debug)}
                <pre class="fnp-debug-details">{$error_debug}</pre>
            {/if}
        </section>
    </main>
</body>

</html>
