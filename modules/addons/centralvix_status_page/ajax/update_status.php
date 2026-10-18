<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixStatusModule::requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixStatusModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixStatusModule::validateCsrf($_POST['token'] ?? '')) {
    CentralVixStatusModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$serviceId = (int) ($_POST['service_id'] ?? 0);
$status = preg_replace('/[^a-z_]/', '', $_POST['status'] ?? 'operational');
CentralVixStatusService::updateServiceStatus($serviceId, $status, null, 'Manual');
CentralVixStatusModule::logAction('update_status', $serviceId, $status);
CentralVixStatusModule::json(['success' => true]);
