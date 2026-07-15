<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ISP Portal Not Found - FASTNETPAY</title>
    <link rel="icon" href="{$app_url}/ui/ui/images/fastnetpay-wifi-favicon.svg" type="image/svg+xml">
    <style>
        :root {
            --fnp-primary: #41a146;
            --fnp-secondary: #f9c02b;
            --fnp-bg: #f1f1f1;
            --fnp-dark: #18212f;
            --fnp-muted: #667085;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--fnp-dark);
            background:
                radial-gradient(circle at top left, rgba(65, 161, 70, .16), transparent 30%),
                radial-gradient(circle at bottom right, rgba(249, 192, 43, .18), transparent 34%),
                var(--fnp-bg);
        }
        .portal-card {
            width: min(100%, 560px);
            padding: 32px;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .14);
            text-align: center;
            border: 1px solid rgba(65, 161, 70, .12);
        }
        .mark {
            width: 64px;
            height: 64px;
            display: inline-grid;
            place-items: center;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--fnp-primary), #2f7f35);
            color: #fff;
            font-size: 30px;
            margin-bottom: 18px;
            box-shadow: 0 14px 32px rgba(65, 161, 70, .24);
        }
        h1 {
            margin: 0 0 10px;
            font-size: 28px;
            line-height: 1.15;
        }
        p {
            margin: 0 auto 16px;
            color: var(--fnp-muted);
            line-height: 1.6;
            max-width: 440px;
        }
        .host {
            display: inline-block;
            max-width: 100%;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(249, 192, 43, .18);
            color: #775600;
            font-size: 13px;
            word-break: break-word;
        }
        .footer {
            margin-top: 22px;
            font-size: 12px;
            color: #98a2b3;
        }
    </style>
</head>
<body>
    <main class="portal-card">
        <div class="mark">WiFi</div>
        <h1>ISP Portal Not Found</h1>
        <p>This FASTNETPAY tenant portal is not active or has not been assigned yet. Please check the portal address from your ISP.</p>
        {if $_unknown_tenant_host}
            <div class="host">{$_unknown_tenant_host|escape}</div>
        {/if}
        <div class="footer">FASTNETPAY secure ISP billing platform</div>
    </main>
</body>
</html>
