<?php

// Allow requests from any origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

register_menu("Payment Page Settings", true, "pay_setup", 'SETTINGS', '', '', "");

function pay_setup()
{
    global $ui, $admin, $config;
    $ui->assign('_title', Lang::T("Payment Page Settings"));
    $ui->assign('_system_menu', 'settings');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pay_button = $_POST['pay_button'] ?: 'no';
        $pay_custom_message = htmlspecialchars($_POST['pay_custom_message']) ?: '';
        $_POST['pay_custom_message'] = $pay_custom_message;
        $_POST['pay_button'] = $pay_button;
        // Update or insert settings in the database
        foreach ($_POST as $key => $value) {
            $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
            if ($d) {
                $d->value = $value;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = $key;
                $d->value = $value;
                $d->save();
            }
        }
        r2(U . 'plugin/pay_setup', 's', Lang::T('Settings Saved Successfully'));
    }

    $ui->display('pay_setup.tpl');
}


function pay_generate_verification_code()
{
    return rand(100000, 999999);
}

function pay_check()
{
    global $config, $ui;

    if ($config['maintenance_mode']) {
        displayMaintenanceMessage();
        die();
    }
    session_start();
    $ui->assign('_title', Lang::T("Check Your Account Status"));

    $attemptLimit = 5;
    $timeFrame = 10 * 60; // 10 minutes
    $verificationCodeExpiry = 5 * 60; // 5 minutes

    // Initialize or clean up old attempts
    $_SESSION['attempts'] = (!isset($_SESSION['attempts'])) ? [] : array_filter($_SESSION['attempts'], fn($timestamp) => $timestamp >= time() - $timeFrame);

    if (isset($_POST['check']) || isset($_POST['resend_code'])) {
        if (count($_SESSION['attempts']) >= $attemptLimit) {
            $ui->assign('notify', Lang::T("Too many attempts. Please try again later."));
            $ui->assign('notify_t', 'e');
        } else {
            $account = $_POST['account'];
            // Use ORM's first() method to fetch the first matching record
            $customerDetails = ORM::for_table('tbl_customers')
                ->where_any_is([
                    ['username' => $account],
                    ['phonenumber' => $account],
                    ['email' => $account],
                    ['id' => $account]
                ])
                ->find_one();

            $_SESSION['attempts'][] = time();

            if ($customerDetails) {
                // Generate and send verification code
                $verificationCode = pay_generate_verification_code();
                $message = "Hello, \n\n Your verification code is: $verificationCode \n\n" . $config['CompanyName'];
                $phone = $customerDetails->phonenumber;
                $sendMethods = ['sendSMS', 'sendWhatsapp'];

                foreach ($sendMethods as $method) {
                    try {
                        Message::$method($phone, $message);
                        _log("Verification Code Sent to: $phone Via: $method Message: $message");
                    } catch (Exception $e) {
                        // Log the error and display an error message to the user
                        _log('Failed to send verification code to: ' . $phone . ' ' . 'Via: ' . $method . ' ' . 'Message: ' . $message . ' ' . 'Error: ' . $e->getMessage());
                        $ui->assign('notify', Lang::T("Failed to send verification code via $method. Please try again later."));
                        $ui->assign('notify_t', 'e');
                    }
                }

                // Store verification code, timestamp, and customer ID in session
                $_SESSION['verification_code'] = $verificationCode;
                $_SESSION['verification_code_timestamp'] = time();
                $_SESSION['customer_id'] = $customerDetails->id;

                $ui->assign('method', 'verify');
                $ui->assign('notify', Lang::T("Verification code sent to your phone number."));
                $ui->assign('notify_t', 's');
                $ui->assign('id', $customerDetails->id);
                $ui->assign('customerDetails', $customerDetails);
            } else {
                $ui->assign('notify', Lang::T("No customer found with the provided information."));
                $ui->assign('notify_t', 'e');
            }
        }
    } elseif (isset($_POST['verify_code'])) {
        $inputCode = $_POST['verification_code'];

        if (isset($_SESSION['verification_code'], $_SESSION['verification_code_timestamp'])) {
            $storedCode = $_SESSION['verification_code'];
            $codeTimestamp = $_SESSION['verification_code_timestamp'];

            if (time() - $codeTimestamp <= $verificationCodeExpiry) {
                if ($inputCode == $storedCode && isset($_SESSION['customer_id'])) {
                    $customerDetails = ORM::for_table('tbl_customers')
                        ->find_one($_SESSION['customer_id']);

                    if ($customerDetails) {
                        $ui->assign('method', '');
                        $ui->assign('notify', Lang::T("Account verified successfully."));
                        $ui->assign('notify_t', 's');
                        $bills = User::_billing($_SESSION['customer_id']);
                        foreach ($bills as $bill) {
                        }
                        $plan = pay_getPlan($bill->plan_id);
                        $amount = $plan->price;
                        $currency = $config['currency_code'];
                        $ui->assign('currency', $currency);
                        $ui->assign('amount', $amount);
                        $ui->assign('_bills', $bills);
                        $ui->assign('customerDetails', $customerDetails);

                        // Clear verification code after successful verification
                        unset($_SESSION['verification_code'], $_SESSION['verification_code_timestamp'], $_SESSION['customer_id']);
                    } else {
                        $ui->assign('notify', Lang::T("Customer details not found."));
                        $ui->assign('notify_t', 'e');
                    }
                } else {
                    $ui->assign('method', 'verify');
                    $ui->assign('customerDetails', 'verify');
                    $ui->assign('id', $_SESSION['customer_id']);
                    $ui->assign('notify', Lang::T("Invalid verification code."));
                    $ui->assign('notify_t', 'e');
                }
            } else {
                $ui->assign('notify', Lang::T("Verification code expired."));
                $ui->assign('notify_t', 'e');
            }
        } else {
            $ui->assign('notify', Lang::T("Verification code not found."));
            $ui->assign('notify_t', 'e');
        }
    }

    $ui->display('pay_check.tpl');
}

function pay_suspended()
{
    global $ui, $config;
    $ui->assign('_title', Lang::T("Internet Suspended"));

    switch ($config['pay_template']) {
        case 'default':
            $ui->display('pay_suspended.tpl');
            break;
        default:
            $payLink = APP_URL . '/?_route=plugin/pay_check';
            header('Content-Type: text/html; charset=utf-8');
            $customMessage = str_replace('[[pay_link]]', $payLink, $config['pay_custom_message']);
            echo html_entity_decode($customMessage); 
            break;
    }
}

function pay_now()
{
    global $config;

    if ($config['maintenance_mode']) {
        displayMaintenanceMessage();
        die();
    }
    if (isset($_POST['pay'])) {
        $payment_data = pay_validateAndPreparePaymentData($_POST);
        $customer = ORM::for_table('tbl_customers')->where('username', $payment_data['username'])->find_one();
        $token = User::generateToken($customer['id'], 1);
        if (!empty($token['token'])) {
            $tur = ORM::for_table('tbl_user_recharges')
                ->where('customer_id', $customer['id'])
                ->where('namebp', $payment_data['plan_name'])
                ->find_one();
            if ($tur) {
                $url = APP_URL . '?_route=home&recharge=' . $tur['id'] . '&uid=' . urlencode($token['token']);
                header("Location: $url");
            }
        }
    }
}

function pay_validateAndPreparePaymentData($post_data)
{
    $username = $post_data['username'];
    $plan_name = $post_data['plan_name'];
    return [
        'username' => $username,
        'plan_name' => $plan_name,
    ];
}

function pay_getEmailAddress($phone)
{
    $serverHost = $_SERVER['HTTP_HOST'];

    $email = ($serverHost === 'localhost') ? "$phone@$serverHost.com" : "$phone@$serverHost";
    return $email;
}

function pay_getPlan($planid)
{
    return ORM::for_table('tbl_plans')
        ->where('id', $planid)
        ->find_one();
}
function pay_formatPhoneNumber($phone)
{
    global $config;
    $countryCode = $config['country_code_phone'];
    if (substr($phone, 0, 1) == '+') {
        $phone = str_replace('+', '', $phone);
    }
    if (substr($phone, 0, 1) == '9') {
        $phone = preg_replace('/^9/', $countryCode . '9', $phone);
    }

    if (substr($phone, 0, 1) == '8') {
        $phone = preg_replace('/^8/', $countryCode . '8', $phone);
    }

    if (substr($phone, 0, 1) == '0') {
        $phone = preg_replace('/^0/', $countryCode, $phone);
    }
    if (substr($phone, 0, 1) == '7') {
        $phone = preg_replace('/^7/', $countryCode . '7', $phone);
    }

    if (substr($phone, 0, 1) == '1') {
        $phone = preg_replace('/^1/', $countryCode . '1', $phone);
    }

    return $phone;
}
