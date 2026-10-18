<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/Security.php';
CentralVixReguaModule::autoload();

add_hook('AdminAreaPage', 1, function () {
    if (empty($_GET['cvix_regua_ajax'])) {
        return;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'addonmodules.php') {
        return;
    }
    if (($_GET['module'] ?? '') !== 'centralvix_regua_cobranca') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_regua_ajax']);
    $file = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($file)) {
        CentralVixReguaModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $file;
    exit;
});

add_hook('AfterCronJob', 1, function () {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixReguaModule::autoload();
    $last = (int) CentralVixReguaModule::dbSetting('last_cron_run', '0');
    $interval = max(15, (int) CentralVixReguaModule::setting('cron_interval', 60)) * 60;
    if (time() - $last < $interval) {
        return;
    }
    CentralVixReguaModule::setDbSetting('last_cron_run', (string) time());
    CentralVixReguaExecutionService::run(false);
});

add_hook('InvoicePaid', 1, function ($vars) {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixReguaModule::autoload();
    $invoiceId = (int) ($vars['invoiceid'] ?? 0);
    if ($invoiceId) {
        CentralVixReguaNotificationService::sendPaymentConfirmation($invoiceId);
    }
});
