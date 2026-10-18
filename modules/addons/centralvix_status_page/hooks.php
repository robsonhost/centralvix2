<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/Bootstrap.php';
CentralVixStatusModule::autoload();

add_hook('AdminAreaPage', 1, function () {
    if (empty($_GET['cvix_status_ajax'])) {
        return;
    }
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'addonmodules.php') {
        return;
    }
    if (($_GET['module'] ?? '') !== 'centralvix_status_page') {
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_status_ajax']);
    $file = __DIR__ . '/ajax/' . $action . '.php';
    if (!is_file($file)) {
        CentralVixStatusModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    require $file;
    exit;
});

add_hook('AfterCronJob', 1, function () {
    require_once __DIR__ . '/lib/Bootstrap.php';
    CentralVixStatusModule::autoload();
    $last = (int) CentralVixStatusModule::dbSetting('last_cron_check', '0');
    $interval = max(1, (int) CentralVixStatusModule::setting('check_interval', 5)) * 60;
    if (time() - $last < $interval) {
        return;
    }
    CentralVixStatusModule::setDbSetting('last_cron_check', (string) time());
    CentralVixStatusMonitorService::runAllChecks();
    CentralVixStatusMaintenanceService::processScheduled();
});
