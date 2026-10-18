<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
CentralVixStatusModule::requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixStatusModule::json(['success' => false, 'message' => 'POST required'], 405);
}
if (!CentralVixStatusModule::validateCsrf($_POST['token'] ?? '')) {
    CentralVixStatusModule::json(['success' => false, 'message' => 'CSRF inválido'], 403);
}
$id = (int) ($_POST['id'] ?? 0);
$_POST['start_at'] = CentralVixStatusSecurity::parseDateTimeInput($_POST['start_at'] ?? '');
$_POST['end_at'] = CentralVixStatusSecurity::parseDateTimeInput($_POST['end_at'] ?? '');
$id = CentralVixStatusMaintenanceService::save($_POST, $id);
CentralVixStatusModule::logAction('save_maintenance', 0, 'success', (string) $id);
CentralVixStatusModule::json(['success' => true, 'id' => $id, 'message' => 'Manutenção salva.']);
