<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>FASTNETPAY SuperAdmin Verification</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="icon" href="{$app_url}/ui/ui/images/fastnetpay-wifi-favicon.svg" type="image/svg+xml" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/modern-AdminLTE.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fastnetpay-theme.css?2026.7.8">
</head>
<body class="hold-transition login-page fastnetpay-login-page">
    <div class="login-box">
        <div class="login-logo">
            <b>FASTNET</b>PAY
        </div>
        <div class="login-box-body fnp-auth-card">
            <div class="fnp-auth-icon"><i class="fa fa-shield"></i></div>
            <h3>SuperAdmin Verification</h3>
            <p class="login-box-msg">Enter the SMS OTP sent to your SuperAdmin phone number.</p>

            {if isset($notify)}
                <div class="alert alert-{if $notify_t eq 's'}success{else}danger{/if}">{$notify|escape}</div>
            {/if}

            <form action="{Text::url('admin/2fa-post')}" method="post">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <div class="form-group has-feedback">
                    <input type="text" class="form-control" name="otp" maxlength="6" minlength="6" inputmode="numeric" placeholder="6-digit code" required autofocus>
                    <span class="fa fa-key form-control-feedback"></span>
                </div>
                <button type="submit" class="btn btn-success btn-block btn-flat">Verify & Continue</button>
            </form>

            <form action="{Text::url('admin/2fa-resend')}" method="post" class="fnp-resend-otp">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <button type="submit" class="btn btn-link btn-block">Resend OTP</button>
            </form>
        </div>
    </div>
</body>
</html>
