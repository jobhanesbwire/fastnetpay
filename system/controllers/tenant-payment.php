<?php

/**
 * Suspended tenant SaaS invoice payment screen.
 */

if (!class_exists('Tenant') || !class_exists('SaasBilling') || !Tenant::isTenantRequest()) {
    r2(getUrl('admin'), 'e', 'Tenant payment page is available from an ISP tenant domain.');
}

SaasBilling::installSchema();
$tenant = Tenant::current();
if (!$tenant) {
    r2(getUrl('admin'), 'e', 'Tenant not found.');
}

$context = SaasBilling::suspendedTenantPaymentContext((int) $tenant['id']);
if ((string) $tenant['status'] !== 'suspended' && (float) $context['balance_due'] <= 0) {
    r2(getUrl('dashboard'), 's', 'Your SaaS account is active.');
}

$ui->assign('_title', 'FASTNETPAY SaaS Payment');
$ui->assign('context', $context);
$ui->display('admin/saas/suspended-payment.tpl');
