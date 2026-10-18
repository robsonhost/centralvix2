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
$services = isset($_POST['affected_services']) ? (array) $_POST['affected_services'] : [];
$_POST['affected_services'] = $services;
$_POST['started_at'] = CentralVixStatusSecurity::parseDateTimeInput($_POST['started_at'] ?? '');
$id = CentralVixStatusIncidentService::saveIncident($_POST, $id);
if (!empty($_POST['update_message'])) {
    CentralVixStatusIncidentService::addUpdate($id, $_POST['status'] ?? 'investigating', $_POST['update_message']);
}
CentralVixStatusModule::logAction('save_incident', 0, 'success', (string) $id);
CentralVixStatusModule::json(['success' => true, 'id' => $id, 'message' => 'Incidente salvo.']);
