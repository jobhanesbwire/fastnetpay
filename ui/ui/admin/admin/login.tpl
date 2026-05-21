<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{Lang::T('Login')} - {$_c['CompanyName']}</title>
    <link rel="icon" href="{$app_url}/ui/ui/images/fastnetpay-wifi-favicon.svg" type="image/svg+xml" />

    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fastnetpay-theme.css?2026.5.21" />
</head>

<body class="hold-transition login-page fastnetpay-login-page">
    <main class="fnp-admin-auth-shell">
        <section class="fnp-admin-auth-brand">
            <span class="fastnetpay-login-badge"><i class="fa fa-wifi"></i></span>
            <div>
                <strong>{$_c['CompanyName']}</strong>
                <span>Admin Console</span>
            </div>
        </section>

        <section class="login-box-body">
            <p class="login-box-msg">{Lang::T('Enter Admin Area')}</p>
            {if isset($notify)}
                {$notify}
            {/if}
            <form action="{Text::url('admin/post')}" method="post">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <div class="form-group has-feedback">
                    <input type="text" required class="form-control" name="username" placeholder="{Lang::T('Username')}">
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input type="password" required class="form-control" name="password" placeholder="{Lang::T('Password')}">
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-flat">
                    <i class="fa fa-sign-in"></i> {Lang::T('Login')}
                </button>
                <a href="{Text::url('login')}" class="back-link">{Lang::T('Go Back')}</a>
            </form>
        </section>
    </main>
</body>

</html>
