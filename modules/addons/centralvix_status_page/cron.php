<?php
/**
 * Cron manual — CentralVix Status Page
 * php /path/to/whmcs/modules/addons/centralvix_status_page/cron.php
 */

$root = realpath(dirname(__FILE__, 4));
if (!$root || !is_file($root . '/init.php')) {
    fwrite(STDERR, "init.php não encontrado.\n");
    exit(1);
}

require_once $root . '/init.php';
require_once __DIR__ . '/lib/Bootstrap.php';
CentralVixStatusModule::autoload();

CentralVixStatusModule::setDbSetting('last_cron_check', (string) time());
$results = CentralVixStatusMonitorService::runAllChecks();
CentralVixStatusMaintenanceService::processScheduled();

echo json_encode([
    'success' => true,
    'checked' => count($results),
    'time' => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
