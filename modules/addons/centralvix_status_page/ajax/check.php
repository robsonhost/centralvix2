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
if (!$serviceId) {
    CentralVixStatusModule::json(['success' => false, 'message' => 'Serviço inválido']);
}
$service = CentralVixStatusService::getService($serviceId);
if (!$service) {
    CentralVixStatusModule::json(['success' => false, 'message' => 'Serviço não encontrado']);
}
$result = CentralVixStatusMonitorService::checkService($service);
CentralVixStatusModule::logAction('manual_check', $serviceId, $result['status'], $result['message']);
CentralVixStatusModule::json(['success' => true, 'result' => $result]);
