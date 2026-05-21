<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{$_title}</title>
  <link rel="stylesheet" href="ui/ui/styles/sweetalert2.min.css" />
  <link rel="stylesheet" href="ui/ui/styles/plugins/pace.css" />
  <script src="ui/ui/scripts/sweetalert2.all.min.js"></script>
  <link rel="shortcut icon" href="ui/ui/images/logo.png" type="image/x-icon" />
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap");

    body {
      font-family: "Roboto", sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      background: linear-gradient(135deg, #71b7e6, #9b59b6);
    }

    .container {
      background-color: #fff;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      text-align: center;
      max-width: 500px;
      width: 75%;
    }

    .company-name {
      font-size: 24px;
      font-weight: 700;
      color: #333;
      margin-bottom: 20px;
    }

    .illustration {
      width: 100%;
      max-width: 300px;
      margin: 0 auto 20px;
      border-radius: 10px;
    }

    h1 {
      color: #333;
      font-size: 28px;
      margin-bottom: 20px;
    }

    p {
      color: #666;
      font-size: 18px;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 20px;
      text-align: left;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }

    .form-group input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }

    .form-group input:focus {
      outline: none;
      border-color: #9b59b6;
    }

    .form-group select,
    .form-group input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #9b59b6;
    }

    .pay-button {
      background-color: #e74c3c;
      color: #fff;
      padding: 15px 30px;
      border: none;
      border-radius: 30px;
      font-size: 18px;
      text-decoration: none;
      display: inline-block;
      transition: background-color 0.3s ease;
      cursor: pointer;
    }

    .pay-button:hover {
      background-color: #c0392b;
    }

    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.7);
      z-index: 9999;
      display: none;
      justify-content: center;
      align-items: center;
    }

    .loading-overlay .spinner {
      border: 16px solid #f3f3f3;
      border-radius: 50%;
      border-top: 16px solid #3498db;
      width: 120px;
      height: 120px;
      animation: spin 2s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    .account-table {
      width: 100%;
      margin-top: 30px;
      border-collapse: collapse;
    }

    .account-table th,
    .account-table td {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: left;
    }

    .account-table th {
      background-color: #f2f2f2;
      font-weight: bold;
    }

    .flex-container {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .flex-container input {
      flex: 1;
    }

    .flex-container button {
      padding: 10px 15px;
    }

    .hidden {
      display: none;
    }

    @media (max-width: 600px) {
      .container {
        padding: 20px;
        border-radius: 10px;
      }

      .pay-button {
        width: 100%;
        padding: 11px 30px;
      }

      .account-table th,
      .account-table td {
        font-size: 14px;
        padding: 10px;
      }

      .flex-container {
        flex-direction: column;
        align-items: stretch;
      }

      .flex-container button {
        width: 100%;
      }

      .form-group select,
      .form-group input {
        width: 92%;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="company-name">{$_c['CompanyName']}</div>

    {if isset($customerDetails) && $customerDetails !== '' && isset($method)
    && $method == ''}
    <form action="{$_url}plugin/pay_now" method="post">
      <input type="hidden" name="email" value="{$customerDetails.email}" />
      <input type="hidden" name="username" value="{$customerDetails.username}" />
      <input type="hidden" name="phone" value="{$customerDetails.phonenumber}" />
      <table class="account-table">
        <tr>
          <th>{Lang::T('Account Number')}</th>
          <td>{$customerDetails.id}</td>
        </tr>
        <tr>
          <th>{Lang::T('Account Holder')}</th>
          <td>{$customerDetails.fullname}</td>
        </tr>
        <tr>
          <th>{Lang::T('Account Status')}</th>
          <td>{$customerDetails.status}</td>
        </tr>
        <tr>
          <th>{Lang::T('Outstanding Balance')}</th>
          <td>{$customerDetails.balance}</td>
        </tr>
        <tr>
          <th>{Lang::T('Service Type')}</th>
          <td>{$customerDetails.service_type}</td>
        </tr>
        {foreach $_bills as $_bill}
        <tr>
          <th>{Lang::T('Service Plan')}</th>
          <td>{$_bill['namebp']}</td>
        </tr>
        <!-- <tr>
            <th>Routers</th>
            <td>{$_bill['routers']}</td>
          </tr> -->
        <tr>
          <th>Service Status</th>
          {if $_bill['status'] != 'on'}
          <td>{Lang::T('Expired')}</td>
          {else}
          <td>{Lang::T('Active')}</td>
          {/if}
        </tr>
        <tr>
          <th>Next Billing Date</th>
          <td>
            {if $_bill['time'] ne
            ''}{Lang::dateAndTimeFormat($_bill['expiration'],$_bill['time'])}{/if}&nbsp;
          </td>
        </tr>
        <tr>
          <th>{Lang::T('Total Due')}</th>
          <td>{$currency}{$amount}</td>
        </tr>
        <input type="hidden" name="routername" value="{$_bill['routers']}" />
        <input type="hidden" name="plan_name" value="{$_bill['namebp']}" />
        <input type="hidden" name="planid" value="{$_bill['plan_id']}" />
        {/foreach}
      </table>
      <br />
      <button type="submit" name="pay" class="pay-button">
        {Lang::T('Proceed')}
      </button>
    </form>
    {elseif isset($method) && $method == 'verify' && isset($customerDetails)
    && $customerDetails !== ''}
    <form id="payment-form" action="" method="post">
      <input type="hidden" name="account" value="{$id}" />
      <div class="form-group">
        <label for="verification_code">{Lang::T('Verification Code:')}</label>
        <div class="flex-container">
          <input type="text" id="verification_code" name="verification_code"
            placeholder="Enter 6 digit verification code" required />
          <button type="submit" name="verify_code" id="verify-code-btn" class="pay-button">
            {Lang::T('Verify')}
          </button>
          <button type="submit" name="resend_code" id="resend-code-btn" class="pay-button hidden" formnovalidate>
            {Lang::T('Resend Code')}
          </button>
          <span id="timer">{Lang::T('Resend Code in 60s')}</span>
        </div>
      </div>
    </form>
    {else}
    <form id="payment-form" action="" method="post">
      <div class="form-group">
        <label for="account">{Lang::T('Account Details')}</label>
        <input type="text" id="account" name="account" placeholder="Username/Phone/Email/Account ID" required />
      </div>
      <button type="submit" name="check" value="check" id="check-status-btn" class="pay-button">
        {Lang::T('Proceed')}
      </button>
    </form>
    {/if}
  </div>

  <div class="loading-overlay">
    <div class="spinner"></div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="ui/ui/scripts/pace.min.js"></script>
  <script>
    $(document).ready(function () {
      $("#payment-form").on("submit", function (event) {
        // Show the loading overlay
        $(".loading-overlay").css("display", "flex");
      });

      // Timer for resend code button
      let timer = 60;
      let interval = setInterval(function () {
        timer--;
        $('#timer').text('Resend Code in ' + timer + 's');
        if (timer <= 0) {
          clearInterval(interval);
          $('#resend-code-btn').removeClass('hidden');
          $('#timer').text('');
        }
      }, 1000);

      // Display SweetAlert toast notification if notify is set
      {if isset($notify)}
      Swal.fire({
        icon: '{if $notify_t == "s"}success{else}error{/if}',
        title: '{$notify|escape:"html"}',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });
      {/if}
    });
  </script>
</body>

</html>