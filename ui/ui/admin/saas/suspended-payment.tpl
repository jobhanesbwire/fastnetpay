<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FASTNETPAY SaaS Payment</title>
    <link rel="shortcut icon" href="{$app_url}/favicon.ico">
    <link rel="stylesheet" href="{$app_url}/ui/ui/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/assets/css/fastnetpay-theme.css">
    <style>
        body { background: #f1f1f1; min-height: 100vh; display: flex; align-items: center; }
        .fnp-suspended-wrap { width: 100%; padding: 24px 12px; }
        .fnp-suspended-card { max-width: 880px; margin: 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 22px 60px rgba(31,41,51,.14); overflow: hidden; border: 1px solid rgba(65,161,70,.14); }
        .fnp-suspended-hero { padding: 28px; color: #fff; background: linear-gradient(135deg,#41a146,#256f2f); }
        .fnp-suspended-hero h1 { margin: 0 0 8px; font-weight: 900; font-size: 26px; }
        .fnp-suspended-hero p { margin: 0; opacity: .95; }
        .fnp-suspended-body { padding: 28px; }
        .fnp-pay-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 12px; margin: 18px 0; }
        .fnp-pay-metric { background: #f8faf8; border: 1px solid rgba(65,161,70,.12); border-radius: 12px; padding: 14px; }
        .fnp-pay-metric span { display: block; color: #6b7280; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .fnp-pay-metric b { display: block; color: #1f2933; font-size: 20px; margin-top: 4px; }
        .fnp-reference { padding: 16px; background: #fff8df; border: 1px dashed #f9c02b; border-radius: 12px; font-size: 18px; font-weight: 900; letter-spacing: .04em; }
        .fnp-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        @media (max-width: 680px) { .fnp-pay-grid { grid-template-columns: 1fr; } .fnp-suspended-hero,.fnp-suspended-body { padding: 22px; } }
    </style>
</head>
<body>
<div class="fnp-suspended-wrap">
    <div class="fnp-suspended-card">
        <div class="fnp-suspended-hero">
            <h1>FASTNETPAY SaaS Payment Required</h1>
            <p>{$context.message|escape}</p>
        </div>
        <div class="fnp-suspended-body">
            <div class="row">
                <div class="col-md-7">
                    <h3 style="margin-top:0">{$context.tenant.name|escape}</h3>
                    <p class="text-muted">Invoice {$context.invoice.invoice_number|escape} · {$context.invoice.period_start|escape} to {$context.invoice.period_end|escape}</p>
                </div>
                <div class="col-md-5 text-right">
                    <span class="label label-danger">{$context.tenant.status|escape}</span>
                    <h2 style="margin:8px 0 0">Ksh {$context.balance_due|string_format:"%.2f"}</h2>
                    <p class="text-muted">Balance due</p>
                </div>
            </div>

            <div class="fnp-pay-grid">
                <div class="fnp-pay-metric"><span>Hotspot Users</span><b>{$context.usage.hotspot}</b></div>
                <div class="fnp-pay-metric"><span>PPPoE Users</span><b>{$context.usage.pppoe}</b></div>
                <div class="fnp-pay-metric"><span>Paid So Far</span><b>Ksh {$context.amount_paid|string_format:"%.2f"}</b></div>
                <div class="fnp-pay-metric"><span>Due Date</span><b>{$context.invoice.due_date|escape}</b></div>
                <div class="fnp-pay-metric"><span>Grace Deadline</span><b>{$context.invoice.grace_until|escape}</b></div>
                <div class="fnp-pay-metric"><span>Support</span><b>{$context.settings.support_phone|escape}</b></div>
            </div>

            <h4>Payment Details</h4>
            <p>{$context.settings.instructions|escape}</p>
            <div class="fnp-reference">{$context.account_reference|escape}</div>
            <p class="help-block" style="margin-top:10px">
                Paybill: <strong>{$context.settings.paybill_number|default:$context.settings.shortcode|escape}</strong>
                {if $context.settings.till_number} · Till: <strong>{$context.settings.till_number|escape}</strong>{/if}
            </p>

            <div class="fnp-actions">
                <a class="btn btn-success" href="{Text::url('tenant-payment')}"><i class="fa fa-refresh"></i> I Have Paid / Check Status</a>
                <a class="btn btn-default" href="{Text::url('logout')}">Logout</a>
                {if $context.settings.support_phone}<a class="btn btn-warning" href="tel:{$context.settings.support_phone|escape}">Contact Support</a>{/if}
            </div>
        </div>
    </div>
</div>
</body>
</html>
