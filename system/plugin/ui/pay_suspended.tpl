<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{$_title}</title>
    <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #9b59b6;
            --danger: #e74c3c;
            --danger-dark: #c0392b;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            height: 100vh;
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background-color: #fff;
            border-radius: 16px;
            padding: 40px 30px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .illustration {
            max-width: 200px;
            width: 100%;
            margin-bottom: 25px;
            border-radius: 12px;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .pay-button {
            background-color: var(--danger);
            color: #fff;
            padding: 14px 30px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .pay-button:hover,
        .pay-button:focus {
            background-color: var(--danger-dark);
        }

        @media (max-width: 500px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 22px;
            }

            .pay-button {
                width: 100%;
                padding: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <img src="system/plugin/ui/disconnected.png" alt="Service Suspended" class="illustration" />
        {if empty($_c['pay_default_message_header'])}
        <h1>{Lang::T('Internet Service Suspended')}</h1>
        {else}
        {$_c['pay_default_message_header']}
        {/if}
        {if empty($_c['pay_default_message'])}
        <p>{Lang::T('Your internet service has been suspended due to late payment or non payment.')}</p>
        <p>{Lang::T('Please click the button below to make a payment and restore your service.')}</p>
        {else}
        {$_c['pay_default_message']}
        {/if}
        {if $_c['pay_button'] != 'yes'}
        <a href="{$_url}plugin/pay_check" class="pay-button">{Lang::T('Pay Now')}</a>
        {/if}
    </div>
</body>

</html>