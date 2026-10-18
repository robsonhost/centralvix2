<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixStatusModule::requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixStatusModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixStatusModule::validateCsrf($_POST['token'] ?? '')) {
    CentralVixStatusModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$results = CentralVixStatusMonitorService::runAllChecks();
CentralVixStatusMaintenanceService::processScheduled();
CentralVixStatusModule::logAction('run_all_checks', 0, 'success', (string) count($results));
CentralVixStatusModule::json(['success' => true, 'checked' => count($results), 'results' => $results]);
